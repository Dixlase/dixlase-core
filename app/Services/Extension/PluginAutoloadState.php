<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Services\Extension;

use App\Helpers\ComposerLocalHelper;
use App\Models\Plugin;
use App\Support\ComposerLocalManifest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps a plugin's `autoload.files` out of the autoloader while the plugin is
 * not enabled.
 *
 * Composer requires every `autoload.files` entry on every request and every
 * artisan call. PSR-4 classes load only when referenced, but those files run
 * eagerly -- so a plugin that is disabled, or uninstalled with its files left
 * on disk, would otherwise still execute code at bootstrap.
 *
 * The generator ({@see ComposerLocalManifest}) has to stay free of the
 * database, so the decision is recorded in a small state file it can read
 * ({@see ComposerLocalManifest::DISABLED_PLUGINS_FILE}). This class writes
 * that file from the plugin lifecycle and from the database, and regenerates
 * the autoloader when a change affects it.
 *
 * Order matters in both directions:
 *
 *   - Enabling regenerates the map first and only then lets the caller mark
 *     the plugin enabled, so the plugin's ServiceProvider never boots without
 *     the helpers it was written against. When the regeneration fails, the
 *     plugin is put back on the list and the caller must not enable it.
 *   - Disabling happens after the plugin is marked disabled. Removing entries
 *     is the safe direction: if the regeneration fails, the files keep
 *     loading exactly as they did before.
 */
class PluginAutoloadState
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?? base_path(), '/');
    }

    /**
     * Rewrite the list from the database: every plugin directory on disk that
     * has no enabled row is withheld.
     *
     * @return bool|null true when the list changed, false when it was already
     *                   in line, null when the database could not be read or
     *                   the list could not be written (the list is left as is)
     */
    public function reconcile(): ?bool
    {
        $enabled = $this->enabledDirectories();
        if ($enabled === null) {
            return null;
        }

        $onDisk = ComposerLocalManifest::detect($this->basePath.'/plugins');

        return $this->replace(array_values(array_diff($onDisk, $enabled)));
    }

    /**
     * Let a plugin's `autoload.files` load, and regenerate the autoloader.
     *
     * Call this before the plugin is marked enabled.
     *
     * @return bool Whether the autoloader now loads the plugin's files (always
     *              true for a plugin that declares none). On false the plugin
     *              is withheld again and must not be enabled.
     */
    public function allow(string $directory): bool
    {
        $withheld = ComposerLocalManifest::disabledPlugins($this->basePath);
        $this->replace(array_values(array_diff($withheld, [$directory])));

        if (! $this->declaresAutoloadFiles($directory)) {
            return true;
        }

        // The list could not be written, so a regeneration would still leave
        // the files out.
        if ($this->isWithheld($directory)) {
            return false;
        }

        // Always regenerate here, even when the list did not change: the map
        // must be known to contain the files before the plugin boots.
        if ($this->regenerate(true)) {
            return true;
        }

        $this->replace(array_merge(ComposerLocalManifest::disabledPlugins($this->basePath), [$directory]));
        // composer.local.json was already rewritten with the files in it;
        // bring it back in line with the list. The dumped map was not replaced.
        $this->regenerate(false);

        return false;
    }

    /**
     * Withhold a plugin's `autoload.files`, and regenerate the autoloader when
     * that changes it.
     *
     * Call this after the plugin is marked disabled or removed from the
     * database.
     *
     * @return bool Whether the autoloader no longer loads the plugin's files.
     *              False leaves the files loading as before; a later
     *              `dls:plugin:sync-autoload` finishes the job.
     */
    public function withhold(string $directory): bool
    {
        $withheld = ComposerLocalManifest::disabledPlugins($this->basePath);
        $changed = $this->replace(array_merge($withheld, [$directory]));

        if ($changed === null) {
            return false;
        }

        if ($changed === false || ! $this->declaresAutoloadFiles($directory)) {
            return true;
        }

        return $this->regenerate(true);
    }

    /**
     * Bring the list in line with the database and regenerate the autoloader
     * from it. What `dls:plugin:sync-autoload` runs.
     *
     * @return array{reconciled: bool|null, regenerated: bool, withheld: list<string>}
     */
    public function synchronise(): array
    {
        $reconciled = $this->reconcile();
        $regenerated = $this->regenerate(true);

        return [
            'reconciled' => $reconciled,
            'regenerated' => $regenerated,
            'withheld' => ComposerLocalManifest::disabledPlugins($this->basePath),
        ];
    }

    public function isWithheld(string $directory): bool
    {
        return in_array($directory, ComposerLocalManifest::disabledPlugins($this->basePath), true);
    }

    /**
     * Whether the plugin's composer.json lists any `autoload.files`. Plugins
     * that do not are unaffected by the list, so toggling them never needs a
     * composer run.
     */
    public function declaresAutoloadFiles(string $directory): bool
    {
        $composerJson = $this->basePath.'/plugins/'.$directory.'/composer.json';
        if (! is_file($composerJson) || ! is_readable($composerJson)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($composerJson), true);
        $files = is_array($decoded) ? ($decoded['autoload']['files'] ?? []) : [];

        return is_array($files) && $files !== [];
    }

    /**
     * Rewrite composer.local.json from the current list and run composer.
     *
     * The list is not re-derived from the database here: the callers above
     * have just changed it deliberately, and on enable the database does not
     * say "enabled" yet.
     */
    protected function regenerate(bool $requireDump): bool
    {
        return ComposerLocalHelper::syncAutoload(reconcile: false, requireDump: $requireDump);
    }

    /**
     * Directory names of the enabled plugins, or null when the database
     * cannot be read (no connection, or a site that is not installed yet).
     *
     * @return list<string>|null
     */
    protected function enabledDirectories(): ?array
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return null;
            }

            return Plugin::query()
                ->whereNotNull('enabled_at')
                ->pluck('directory')
                ->filter(static fn ($directory): bool => is_string($directory) && $directory !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $directories
     * @return bool|null true when written with a different content, false when
     *                   unchanged, null when the write failed
     */
    private function replace(array $directories): ?bool
    {
        $directories = array_values(array_unique($directories));
        sort($directories);

        $file = $this->basePath.'/'.ComposerLocalManifest::DISABLED_PLUGINS_FILE;
        if (is_file($file) && ComposerLocalManifest::disabledPlugins($this->basePath) === $directories) {
            return false;
        }

        if (! ComposerLocalManifest::writeDisabledPlugins($this->basePath, $directories)) {
            Log::warning('Could not write the plugin autoload state file', ['path' => $file]);

            return null;
        }

        return true;
    }
}
