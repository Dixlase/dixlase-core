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
use App\Models\Plugin;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSnapshot;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginUpdate extends Command
{
    use BuildsExtensionAssets;

    protected $signature = 'dls:plugin:update
        {slug : Plugin slug to update}
        {--force : Skip confirmation}
        {--build : Force a front-end asset rebuild even when compiled assets already exist}
        {--skip-build : Skip the npm install / build step entirely}';

    protected $description = 'Update a plugin to the latest version from its source';

    public function handle(ExtensionSourceManager $manager, ExtensionSourceSnapshot $snapshotter): int
    {
        $slug = $this->argument('slug');

        $plugin = Plugin::query()->where('slug', $slug)->first();
        if (! $plugin) {
            $this->error("Plugin '{$slug}' is not installed.");

            return self::FAILURE;
        }

        if (! $plugin->source_id) {
            $this->error("Plugin '{$slug}' has no linked source. Use dls:plugin:download instead.");

            return self::FAILURE;
        }

        $this->info("Checking for updates for '{$slug}' (current: v{$plugin->version})...");

        $snapshotPath = null;
        // Whether we got far enough into the try block to invoke
        // PluginMigrator::migrate(). Used by the catch handler to decide
        // whether to attempt PluginMigrator::rollback() — without this
        // flag we cannot tell, from inside the catch, whether the
        // failure happened before migrate started (no DB rows to roll
        // back) or after some migrations had been applied.
        $migrationsAttempted = false;
        $migrator = new PluginMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'plugin_migrations',
            $slug,
        );

        try {
            $provider = $manager->makeProvider($plugin->source);
            $release = $provider->getLatestRelease($slug, 'plugin');

            if (! $release) {
                $this->info('No release found from the source.');

                return self::SUCCESS;
            }

            if (! version_compare($release->version, $plugin->version, '>')) {
                $this->info("Already at the latest version (v{$plugin->version}).");

                return self::SUCCESS;
            }

            $this->info("Update available: v{$plugin->version} → v{$release->version}");

            if (! $this->option('force') && ! $this->confirm('Proceed with update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }

            $zipPath = $manager->download($slug, 'plugin', $release->version, $plugin->source_id);
            $this->info("Downloaded v{$release->version}");

            // Capture a snapshot of the live plugin tree so we can roll back
            // a partially-extracted update on failure.
            $livePath = base_path("plugins/{$plugin->directory}");
            $snapshotPath = $snapshotter->capture(
                ExtensionSourceSnapshot::KIND_PLUGIN,
                $plugin->directory,
                $livePath,
            );
            $this->info("Snapshot captured at {$snapshotPath}");

            $this->extractUpdate($zipPath, $plugin);

            // Apply any new migration files shipped with this release.
            // The plugin's migrations live in
            // plugins/<Directory>/database/migrations and are tracked in
            // dls_plugin_migrations by PluginMigrationRepository; the
            // matching install command (dls:plugin:install) runs them at
            // install time but the original update command did not, so
            // schema changes shipped in a plugin update used to land on
            // disk without ever executing against the database.
            // Migrate AFTER extract so the new model / service classes
            // any migration may reference are already in autoload reach.
            $this->info('Running plugin migrations...');
            $migrationsAttempted = true;
            $migrator->migrate($plugin->directory);
            $this->info('Plugin migrations complete.');

            $plugin->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
                'update_failed_at' => null,
                'update_failure_reason' => null,
            ]);

            // Successful update — discard the snapshot to free disk space.
            $snapshotter->discard($snapshotPath);
            $snapshotPath = null;

            // Rebuild front-end assets that ship with the plugin. Default mode
            // is 'force' because update implies the source tree changed and any
            // previously built assets are now stale; users can pass
            // --skip-build to opt out.
            $mode = $this->option('skip-build') ? 'skip' : 'force';
            if ($this->option('build')) {
                // Explicit --build is redundant here but kept for symmetry
                // with install; treat it as the same forced rebuild.
                $mode = 'force';
            }
            $this->buildExtensionAssets($livePath, $mode);

            // Regenerate the Tailwind plugin-source aggregator: the
            // updated plugin may have added, removed, or moved
            // `declares.tailwind_content` paths in its new plugin.json.
            app(\App\Services\Tailwind\PluginSourceAggregator::class)->regenerate();

            $this->info("Plugin '{$slug}' updated to v{$release->version} successfully.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Roll back the plugin tree from the snapshot so the user is not
            // stranded on a half-extracted directory.
            if ($snapshotPath !== null) {
                try {
                    $livePath = base_path("plugins/{$plugin->directory}");
                    $snapshotter->restore($snapshotPath, $livePath);
                    $this->warn('Plugin source rolled back from snapshot.');
                    $snapshotter->discard($snapshotPath);
                } catch (\Throwable $restoreError) {
                    $this->error("ROLLBACK FAILED: {$restoreError->getMessage()}");
                    $this->error("Manual recovery required. Snapshot retained at: {$snapshotPath}");
                }
            }

            // Roll back any plugin migrations that did get applied this
            // run. Best-effort: PluginMigrator::rollback() reverses the
            // migrations recorded in dls_plugin_migrations during this
            // batch, but a migration that failed midway through a
            // multi-statement DDL on MySQL may have left partial effects
            // that are not in the migration table and therefore cannot
            // be auto-reversed. Surface that case loudly so the operator
            // can recover by hand.
            if ($migrationsAttempted) {
                try {
                    $migrator->rollback($plugin->directory);
                    $this->warn('Plugin migrations rolled back.');
                } catch (\Throwable $rollbackError) {
                    $this->error("MIGRATION ROLLBACK FAILED: {$rollbackError->getMessage()}");
                    $this->error('Database may be in an inconsistent state — inspect dls_plugin_migrations and the plugin schema, then recover manually.');
                }
            }

            $plugin->update([
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
     * Replace the on-disk plugin tree with the contents of the freshly
     * downloaded ZIP.
     *
     * The previous implementation called `$zip->extractTo($pluginDir)`
     * directly, which left every old file in place and dumped the new
     * release inside a GitHub-prefixed subdirectory (e.g.
     * `plugins/Foo/Dixlase-plugin-foo-<sha>/...`). The active code
     * therefore stayed on the *old* version even though
     * `plugins.version` had been bumped — a silent "update" that did
     * not actually update.
     *
     * We now stage the ZIP under storage/, locate the GitHub-prefixed
     * top-level directory, replace the live plugin tree with that
     * directory's contents, and discard the staging area. The
     * upstream snapshot+rollback flow continues to protect against
     * partial failures here.
     */
    protected function extractUpdate(string $zipPath, Plugin $plugin): void
    {
        $pluginDir = base_path("plugins/{$plugin->directory}");
        $this->extractZipReplacingDir($zipPath, $pluginDir);
    }

    /**
     * Stage the ZIP, strip the GitHub `<repo>-<sha>/` wrapper, and
     * swap the staged tree in as `$destinationDir`. Used by both
     * extractUpdate() above and (for symmetry with the install flow)
     * may be called by other extension installers.
     */
    protected function extractZipReplacingDir(string $zipPath, string $destinationDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open downloaded ZIP file.');
        }

        // Find the GitHub-style wrapper directory (first entry's first
        // path segment). GitHub release zipballs always wrap their
        // payload in <repo>-<commit-or-tag>/ regardless of release vs.
        // default-branch fallback, so picking the first entry is enough.
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

            // Replace the live tree wholesale. The caller already took a
            // snapshot, so rollback is handled outside this method.
            if (File::isDirectory($destinationDir)) {
                File::deleteDirectory($destinationDir);
            }
            File::ensureDirectoryExists(dirname($destinationDir));
            File::move($newSource, $destinationDir);
        } finally {
            // Always clean up the staging area, including on failure.
            if (File::isDirectory($staging)) {
                File::deleteDirectory($staging);
            }
        }
    }
}
