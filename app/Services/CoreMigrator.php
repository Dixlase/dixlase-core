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

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Log;

/**
 * Stock migrator that refuses to take on plugin / theme migration paths.
 *
 * Extension migrations are owned by PluginMigrator / ThemeMigrator
 * (`dls:plugin:install`, `dls:theme:install`) and are recorded in the
 * dedicated `plugin_migrations` / `theme_migrations` ledgers, namespaced
 * by slug. Letting the stock migrator see them as well causes two known
 * classes of breakage on live sites:
 *
 *   1. Duplicate ledger tracking — the same migration recorded in both
 *      `migrations` and `plugin_migrations` / `theme_migrations`.
 *   2. A bare `php artisan migrate` treating already-applied extension
 *      migrations as pending (the core ledger has no record of them) and
 *      trying to re-create tables the installer already created, which
 *      surfaces as SQLSTATE[42S01] "Base table or view already exists"
 *      and aborts the whole run.
 *
 * Core already declares this invariant — `PluginLoaderTrait::loadPluginMigrations()`
 * is an intentional no-op and `CoreUpdater` passes an explicit
 * `--path=database/migrations` — but both only bind code that core itself
 * owns. An extension provider calling Laravel's `loadMigrationsFrom()`
 * directly bypasses all of it, and third-party extensions are outside
 * core's reach entirely. `Migrator::path()` is the single funnel every
 * registration passes through (and `Migrator` exposes no way to remove a
 * path afterwards), so filtering here is the only place the invariant can
 * be enforced at the boundary rather than by convention.
 *
 * This is defence in depth, not a licence to register extension paths:
 * the primary fix is for extension providers not to call
 * `loadMigrationsFrom()` at all.
 */
class CoreMigrator extends Migrator
{
    /**
     * Base directories whose migrations belong to PluginMigrator /
     * ThemeMigrator rather than to the stock migrator.
     *
     * `custom/` is deliberately absent: it currently ships no migrations
     * and no core code applies any, so reserving it here would pre-empt a
     * design decision that has not been made.
     *
     * @var list<string>
     */
    protected const EXTENSION_DIRECTORIES = ['plugins', 'themes'];

    /**
     * Extension migration paths that were dropped during registration.
     *
     * Recorded so a bare `php artisan migrate` can tell the operator why
     * plugin / theme tables are absent, instead of silently skipping them.
     *
     * @var list<string>
     */
    protected array $droppedExtensionPaths = [];

    /**
     * Register a custom migration path, unless it belongs to an extension.
     *
     * @param  string  $path
     * @return void
     */
    public function path($path)
    {
        if ($this->isExtensionPath($path)) {
            $this->droppedExtensionPaths[] = (string) $path;

            Log::warning('Ignored an extension migration path registered with the stock migrator', [
                'path' => $path,
                'reason' => 'Plugin/theme migrations are applied by PluginMigrator / ThemeMigrator and recorded in the plugin_migrations / theme_migrations ledgers.',
                'action' => 'Remove the loadMigrationsFrom() call from the extension service provider; install/update commands apply these migrations.',
            ]);

            return;
        }

        parent::path($path);
    }

    /**
     * Run the pending migrations, first surfacing a console hint when any
     * extension migration paths were dropped during registration.
     *
     * `path()` runs at boot, before the console output is attached, so the
     * guidance cannot be emitted there. Surfacing it here turns the silent
     * skip into an actionable note for an operator running a bare
     * `php artisan migrate`.
     *
     * @param  array<int, string>|string  $paths
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function run($paths = [], array $options = [])
    {
        $this->noteDroppedExtensionPaths();

        return parent::run($paths, $options);
    }

    /**
     * Emit a one-time console note listing how extension migrations are
     * applied, when the stock migrator dropped any extension paths.
     */
    protected function noteDroppedExtensionPaths(): void
    {
        if ($this->droppedExtensionPaths === [] || ! $this->output) {
            return;
        }

        $count = count($this->droppedExtensionPaths);

        $this->output->writeln([
            '',
            '  <comment>Skipped '.$count.' plugin/theme migration path'.($count === 1 ? '' : 's').'.</comment> These are not applied by <info>migrate</info>.',
            '  Apply extension migrations in dependency order instead:',
            '    <info>php artisan dls:plugin:migrate <Plugin> --force</info>',
            '    <info>php artisan dls:theme:migrate --force</info>',
            '  See docs/operations/upgrading.md, section 4.5 "Run migrations in dependency order".',
            '',
        ]);

        // Show once per migrator instance.
        $this->droppedExtensionPaths = [];
    }

    /**
     * Decide whether a path lives under one of the extension roots.
     *
     * Both the raw and the resolved form of each side are compared: the
     * raw form so that a path which does not exist yet still matches, and
     * the resolved form so that a symlinked base path (or a symlinked
     * extension directory) cannot slip past a plain string prefix test.
     * Comparison is done on separator-terminated strings so a sibling
     * directory such as `plugins-archive/` is not mistaken for `plugins/`.
     *
     * @param  string  $path
     */
    protected function isExtensionPath($path): bool
    {
        $candidates = $this->pathForms((string) $path);

        foreach ($this->extensionRoots() as $root) {
            foreach ($candidates as $candidate) {
                if (str_starts_with($candidate, $root)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Separator-terminated roots of every extension directory, in both
     * raw and symlink-resolved form.
     *
     * @return list<string>
     */
    protected function extensionRoots(): array
    {
        $roots = [];

        foreach (self::EXTENSION_DIRECTORIES as $directory) {
            foreach ($this->pathForms(base_path($directory)) as $form) {
                $roots[] = $form;
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * Every comparable form of a path: as given, and resolved through
     * symlinks when it exists on disk. Both are separator-terminated so
     * prefix comparison cannot match a partial directory name.
     *
     * @return list<string>
     */
    protected function pathForms(string $path): array
    {
        $forms = [$this->terminate($path)];

        $resolved = realpath($path);

        if ($resolved !== false) {
            $forms[] = $this->terminate($resolved);
        }

        return array_values(array_unique($forms));
    }

    /**
     * Normalise a directory path to exactly one trailing separator.
     */
    protected function terminate(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }
}
