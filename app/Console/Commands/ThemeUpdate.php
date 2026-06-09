<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Console\Commands;

use App\Console\Traits\BuildsExtensionAssets;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSnapshot;
use App\Services\ThemeMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeUpdate extends Command
{
    use BuildsExtensionAssets;

    protected $signature = 'dls:theme:update
        {slug : Theme slug to update}
        {--force : Skip confirmation}
        {--build : Force a front-end asset rebuild even when compiled assets already exist}
        {--skip-build : Skip the npm install / build step entirely}';

    protected $description = 'Update a theme to the latest version from its source';

    public function handle(ExtensionSourceManager $manager, ExtensionSourceSnapshot $snapshotter): int
    {
        $slug = $this->argument('slug');

        $theme = Theme::query()->where('slug', $slug)->first();
        if (! $theme) {
            $this->error("Theme '{$slug}' is not installed.");

            return self::FAILURE;
        }

        if (! $theme->source_id) {
            $this->error("Theme '{$slug}' has no linked source. Use dls:theme:download instead.");

            return self::FAILURE;
        }

        $this->info("Checking for updates for '{$slug}' (current: v{$theme->version})...");

        $snapshotPath = null;
        // Whether we got far enough into the try block to invoke
        // ThemeMigrator::migrate(). Used by the catch handler to decide
        // whether to attempt ThemeMigrator::rollback() — without this
        // flag we cannot tell, from inside the catch, whether the
        // failure happened before migrate started (no DB rows to roll
        // back) or after some migrations had been applied.
        $migrationsAttempted = false;
        $migrator = new ThemeMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'theme_migrations',
            $slug,
        );

        try {
            $provider = $manager->makeProvider($theme->source);
            $release = $provider->getLatestRelease($slug, 'theme');

            if (! $release) {
                $this->info('No release found from the source.');

                return self::SUCCESS;
            }

            if (! version_compare($release->version, $theme->version, '>')) {
                $this->info("Already at the latest version (v{$theme->version}).");

                return self::SUCCESS;
            }

            $this->info("Update available: v{$theme->version} → v{$release->version}");

            if (! $this->option('force') && ! $this->confirm('Proceed with update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }

            $zipPath = $manager->download($slug, 'theme', $release->version, $theme->source_id);
            $this->info("Downloaded v{$release->version}");

            // Capture a snapshot of the live theme tree so we can roll back
            // a partially-extracted update on failure.
            $livePath = base_path("themes/{$theme->directory}");
            if (is_dir($livePath)) {
                $snapshotPath = $snapshotter->capture(
                    ExtensionSourceSnapshot::KIND_THEME,
                    $theme->directory,
                    $livePath,
                );
                $this->info("Snapshot captured at {$snapshotPath}");
            }

            $this->extractUpdate($zipPath, $theme);

            // Apply any new migration files shipped with this release.
            // The theme's migrations live in
            // themes/<Directory>/database/migrations and are tracked in
            // dls_theme_migrations by ThemeMigrationRepository; the
            // matching install command (dls:theme:install) runs them at
            // install time but the original update command did not, so
            // schema changes shipped in a theme update used to land on
            // disk without ever executing against the database.
            // Migrate AFTER extract so the new model / service classes
            // any migration may reference are already in autoload reach.
            $this->info('Running theme migrations...');
            $migrationsAttempted = true;
            $migrator->migrate($theme->directory);
            $this->info('Theme migrations complete.');

            $theme->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
                'update_failed_at' => null,
                'update_failure_reason' => null,
            ]);

            // Successful update — discard the snapshot to free disk space.
            if ($snapshotPath !== null) {
                $snapshotter->discard($snapshotPath);
                $snapshotPath = null;
            }

            // Rebuild front-end assets that ship with the theme. Default mode
            // is 'force' because update implies the source tree changed and any
            // previously built assets are now stale; users can pass
            // --skip-build to opt out.
            $mode = $this->option('skip-build') ? 'skip' : 'force';
            if ($this->option('build')) {
                $mode = 'force';
            }
            if (is_dir($livePath)) {
                $this->buildExtensionAssets($livePath, $mode);
                // Refresh the public symlink so the freshly built output is
                // reachable from the web root (the symlink target is the
                // resources/assets directory inside the theme).
                Artisan::call('dls:theme:symlink', [
                    'action' => 'create',
                    'theme' => $theme->directory,
                ]);
            }

            $this->info("Theme '{$slug}' updated to v{$release->version} successfully.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Roll back the theme tree from the snapshot so the user is not
            // stranded on a half-extracted directory.
            if ($snapshotPath !== null) {
                try {
                    $livePath = base_path("themes/{$theme->directory}");
                    $snapshotter->restore($snapshotPath, $livePath);
                    $this->warn('Theme source rolled back from snapshot.');
                    $snapshotter->discard($snapshotPath);
                } catch (\Throwable $restoreError) {
                    $this->error("ROLLBACK FAILED: {$restoreError->getMessage()}");
                    $this->error("Manual recovery required. Snapshot retained at: {$snapshotPath}");
                }
            }

            // Roll back any theme migrations that did get applied this
            // run. Best-effort: ThemeMigrator::rollback() reverses the
            // migrations recorded in dls_theme_migrations during this
            // batch, but a migration that failed midway through a
            // multi-statement DDL on MySQL may have left partial effects
            // that are not in the migration table and therefore cannot
            // be auto-reversed. Surface that case loudly so the operator
            // can recover by hand.
            if ($migrationsAttempted) {
                try {
                    $migrator->rollback($theme->directory);
                    $this->warn('Theme migrations rolled back.');
                } catch (\Throwable $rollbackError) {
                    $this->error("MIGRATION ROLLBACK FAILED: {$rollbackError->getMessage()}");
                    $this->error('Database may be in an inconsistent state — inspect dls_theme_migrations and the theme schema, then recover manually.');
                }
            }

            $theme->update([
                'update_failed_at' => now(),
                'update_failure_reason' => $this->truncateReason($e->getMessage()),
            ]);

            $this->error("Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Cap stored failure reasons so a verbose stack-trace string does not
     * bloat the row. Display surfaces will truncate further as needed.
     */
    protected function truncateReason(string $message): string
    {
        $message = trim($message);
        if (mb_strlen($message) <= 1000) {
            return $message;
        }

        return mb_substr($message, 0, 997).'...';
    }

    /**
     * Replace the on-disk theme tree with the contents of the freshly
     * downloaded ZIP.
     *
     * Two bugs in the previous implementation:
     *
     *   1. Wrong destination — `resource_path("views/themes/...")`
     *      points at `resources/views/themes/`, but Dixlase themes
     *      actually live under `base_path("themes/...")` (= the path
     *      used at install time, by the theme symlink command, by the
     *      asset build trait, etc). The update therefore wrote files
     *      to a directory the running app never reads.
     *
     *   2. ZIP was extracted with `extractTo($themeDir)` directly,
     *      leaving every old file in place and dumping the new
     *      release inside a GitHub-prefixed subdirectory.
     *
     * Together those two bugs meant `dls:theme:update` bumped
     * `themes.version` and rebuilt assets from the *old* source tree,
     * giving a silent "update" that never touched the live theme.
     *
     * The fix stages the ZIP, strips the GitHub `<repo>-<sha>/`
     * wrapper, and swaps it in at base_path("themes/<directory>")
     * (the correct location). The upstream snapshot+rollback flow
     * continues to protect against partial failures here.
     */
    protected function extractUpdate(string $zipPath, Theme $theme): void
    {
        $themeDir = base_path("themes/{$theme->directory}");
        $this->extractZipReplacingDir($zipPath, $themeDir);
    }

    /**
     * Stage the ZIP, strip the GitHub `<repo>-<sha>/` wrapper, and
     * swap the staged tree in as `$destinationDir`. See
     * PluginUpdate::extractZipReplacingDir() for the same logic; we
     * keep a copy here so each console command stays self-contained.
     */
    protected function extractZipReplacingDir(string $zipPath, string $destinationDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open downloaded ZIP file.');
        }

        $topLevel = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }
            $first = explode('/', $entry, 2)[0];
            if ($first !== '') {
                $topLevel = $first;
                break;
            }
        }

        if ($topLevel === null) {
            $zip->close();
            throw new \RuntimeException('Could not determine top-level directory inside ZIP.');
        }

        $staging = storage_path('app/private/extension-update/staging/'.uniqid('extract-', true));
        File::ensureDirectoryExists($staging);

        try {
            $zip->extractTo($staging);
            $zip->close();

            $newSource = $staging.'/'.$topLevel;
            if (! File::isDirectory($newSource)) {
                throw new \RuntimeException("Extracted top-level directory '{$topLevel}' not found in staging.");
            }

            if (File::isDirectory($destinationDir)) {
                File::deleteDirectory($destinationDir);
            }
            File::ensureDirectoryExists(dirname($destinationDir));
            File::move($newSource, $destinationDir);
        } finally {
            if (File::isDirectory($staging)) {
                File::deleteDirectory($staging);
            }
        }
    }
}
