<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Models;

use App\Models\Traits\ReadsExtensionManifest;
use App\Models\Traits\ResolvesExtensionDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Plugin metadata model
 */
class Plugin extends Model
{
    use ReadsExtensionManifest;
    use ResolvesExtensionDirectory;

    /**
     * Boot hook: keep site_plugin_activations in sync with the legacy
     * enabled_at flag for v0.1.0, on enable/disable and when a plugin is
     * created already enabled. When the activation table is missing
     * (during installation) or the primary site has not been seeded yet,
     * the sync is silently skipped. v2 admin UIs will manage activation
     * rows directly per-site.
     */
    protected static function booted(): void
    {
        static::saved(function (Plugin $plugin): void {
            // wasChanged() is false on insert, so a plugin created already
            // enabled would otherwise get no activation row (#494).
            $createdEnabled = $plugin->wasRecentlyCreated && $plugin->enabled_at !== null;

            if (! $createdEnabled && ! $plugin->wasChanged('enabled_at')) {
                return;
            }
            self::syncPrimarySiteActivation($plugin);
        });
    }

    private static function syncPrimarySiteActivation(Plugin $plugin): void
    {
        if (! Schema::hasTable('site_plugin_activations')) {
            return;
        }
        if (! Schema::hasTable('sites')) {
            return;
        }

        $primarySite = Site::primary();
        if ($primarySite === null) {
            return;
        }

        SitePluginActivation::query()->updateOrCreate(
            ['site_id' => $primarySite->id, 'plugin_id' => $plugin->id],
            [
                'is_active' => $plugin->enabled_at !== null,
                'activated_at' => $plugin->enabled_at,
            ]
        );
    }

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'package_name',
        'directory',
        'namespace',
        'slug',
        'version',
        'author',
        'email',
        'url',
        'license',
        'description',
        'installed_at',
        'enabled_at',
        'source_id',
        'source_repo',
        'available_version',
        'last_notified_version',
        'available_version_published_at',
        'release_url',
        'release_notes',
        'last_version_check',
        'update_failed_at',
        'update_failure_reason',
        'signing_key_id',
        'author_id',
        'authority_key_id',
        'installed_from_url',
        'installation_method',
    ];

    /**
     * Cast settings
     */
    protected $casts = [
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'last_version_check' => 'datetime',
        'available_version_published_at' => 'datetime',
        'update_failed_at' => 'datetime',
    ];

    /**
     * Extension source that this plugin was installed from
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Absolute path of this plugin's manifest on disk.
     *
     * The root comes from config, as PluginHelper::getPluginPath() does, so a
     * test can point it at a throwaway directory instead of the real plugins/.
     */
    public function manifestPath(): ?string
    {
        $directory = is_string($this->directory) && $this->directory !== ''
            ? $this->directory
            : static::resolveDirectoryFromSlug((string) $this->slug);

        if ($directory === null || $directory === '') {
            return null;
        }

        $root = trim((string) config('plugins.plugins_directory', 'plugins'), '/');

        return base_path(($root !== '' ? $root : 'plugins')."/{$directory}/plugin.json");
    }

    /**
     * Check if an update is available from the source.
     *
     * Compared against the version in plugin.json on disk, not the recorded
     * one. The recorded version is written only by the install and update
     * paths, so files that arrive another way -- a git deploy, an operator
     * replacing the directory -- leave it stale, and comparing against a
     * stale value offers an update to a version that is already installed.
     * Accepting that offer is destructive: the update path deletes the
     * directory, taking any nested `.git` with it (dixlase-core#521 / #472).
     *
     * Falls back to the recorded version when the manifest cannot be read,
     * so a plugin with no directory on disk keeps the old behaviour.
     */
    public function hasUpdateAvailable(): bool
    {
        $installed = $this->versionOnDisk() ?? $this->version;

        return $this->available_version !== null
            && version_compare($this->available_version, $installed, '>');
    }

    /**
     * Scope to retrieve enabled plugins
     */
    public function scopeEnabled($query)
    {
        return $query->whereNotNull('enabled_at');
    }

    /**
     * Scope for installed plugins
     */
    public function scopeInstalled($query)
    {
        return $query->whereNotNull('installed_at');
    }

    /**
     * Check if plugin is installed
     */
    public function isInstalled(): bool
    {
        return ! is_null($this->installed_at);
    }

    /**
     * Check if plugin is enabled
     */
    public function isEnabled(): bool
    {
        return ! is_null($this->enabled_at);
    }

    /**
     * Kept for backward compatibility (deprecated)
     *
     * @deprecated Use isEnabled() instead
     */
    public function isActivated(): bool
    {
        return $this->isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    protected static function extensionRootDirectory(): string
    {
        return 'plugins';
    }
}
