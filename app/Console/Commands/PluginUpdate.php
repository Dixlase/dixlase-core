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

namespace App\Console\Commands;

use App\Console\Traits\AutoScansExtensionAfterUpdate;
use App\Console\Traits\BuildsExtensionAssets;
use App\Console\Traits\TakesExtensionBackup;
use App\Exceptions\ExtensionUpdateBlockedException;
use App\Models\Plugin;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSnapshot;
use App\Services\Extension\ExtensionUpdateRecorder;
use App\Services\PluginMigrationRepository;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginUpdate extends Command
{
    use AutoScansExtensionAfterUpdate;
    use BuildsExtensionAssets;
    use TakesExtensionBackup;

    protected $signature = 'dls:plugin:update
        {slug : Plugin slug to update}
        {--force : Skip confirmation}
        {--build : Force a front-end asset rebuild even when compiled assets already exist}
        {--skip-build : Skip the npm install / build step entirely}
        {--skip-backup : Skip the automatic pre-update backup that dls:plugin:rollback restores from}
        {--applied-by= : Member id to record as the one who ran the update (the admin screen passes it; CLI runs record none)}';

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

        // Removing a source nulls source_id, but a rebuild of
        // extension_sources outside the foreign key can leave it dangling.
        if ($plugin->source === null) {
            $this->error("Plugin '{$slug}' is linked to extension source #{$plugin->source_id}, which no longer exists. Link it to a registered source with `dls:plugin:install {$plugin->directory} --source=<id>` (see dls:source:list).");

            return self::FAILURE;
        }

        $this->info("Checking for updates for '{$slug}' (current: v{$plugin->version})...");

        $snapshotPath = null;
        // Persistent pre-update backup (kept for dls:plugin:rollback); null
        // when --skip-backup, in which case $snapshotPath guards this run.
        $backupPath = null;
        // For the audit entry and version-history row (dixlase-core#454).
        $before = ExtensionUpdateRecorder::snapshot($plugin);
        $targetVersion = null;
        $downloadedSha256 = null;
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

            // A repository without a tagged release answers with a pseudo
            // release from its default branch; that is not an update
            // (security review D13).
            if (($release->metadata['source'] ?? null) === 'default_branch') {
                $this->error('The source has no tagged release, only its default branch. Only a tagged release can be applied as an update.');

                return self::FAILURE;
            }

            if (! version_compare($release->version, $plugin->version, '>')) {
                $this->info("Already at the latest version (v{$plugin->version}).");

                return self::SUCCESS;
            }

            $this->info("Update available: v{$plugin->version} → v{$release->version}");
            $targetVersion = $release->version;

            if (! $this->option('force') && ! $this->confirm('Proceed with update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }

            $zipPath = $manager->download($slug, 'plugin', $release->version, $plugin->source_id);
            $this->info("Downloaded v{$release->version}");

            // Foundation for future ZIP-signature verification: capture the
            // SHA-256 of what we actually downloaded, so a later release
            // that checks signatures can retro-audit against the
            // authority's published hash for this (slug, version).
            // Logged for the operator + carried into the backup sidecar
            // via takeExtensionBackup()'s metadata array below.
            $downloadedSha256 = hash_file('sha256', $zipPath) ?: null;
            if ($downloadedSha256 !== null) {
                $this->line("Downloaded SHA-256: {$downloadedSha256}");
            }

            // Capture the pre-update state so a failed apply — or a later
            // operator-run `dls:plugin:rollback` — can restore it. By default
            // this is a PERSISTENT backup (kept, retention-pruned, restores
            // the prebuilt resources/assets without npm); --skip-backup falls
            // back to a transient snapshot that only guards this run.
            $livePath = base_path("plugins/{$plugin->directory}");
            if (is_dir($livePath)) {
                if ($this->option('skip-backup')) {
                    $snapshotPath = $snapshotter->capture(
                        ExtensionSourceSnapshot::KIND_PLUGIN,
                        $plugin->directory,
                        $livePath,
                    );
                    $this->info("Snapshot captured at {$snapshotPath}");
                } else {
                    // Record the pre-update version + migration batch on the
                    // backup sidecar so dls:plugin:rollback can compute an
                    // exact --step for the schema half instead of blindly
                    // reverting the current latest batch (which would over-
                    // rollback when the just-undone update was schema-neutral).
                    $backupPath = $this->takeExtensionBackup(
                        ExtensionSourceSnapshot::KIND_PLUGIN,
                        $plugin->directory,
                        $livePath,
                        [
                            'version' => (string) $plugin->version,
                            'max_batch' => $this->currentPluginMigrationBatch($plugin->slug),
                            // Records the ZIP hash of the update being
                            // applied on top of this backup; null-safe
                            // because a corrupt / unreadable ZIP would
                            // have failed the download step earlier.
                            'downloaded_sha256' => $downloadedSha256,
                        ],
                    );
                    $this->info("Backup taken at {$backupPath}");
                }
            }

            $this->extractUpdate($zipPath, $plugin);

            // Scan the new files before anything else runs: a version whose
            // health check resolves to Blocked (or that cannot be scanned)
            // throws here and is rolled back by the catch below, before its
            // migrations, seeders or npm build touch anything.
            // The archive must be the version that was asked for: anything
            // else (a default-branch zipball, a mislabelled asset) is
            // refused and rolled back (security review D13).
            $this->refuseVersionMismatch(base_path("plugins/{$plugin->directory}/plugin.json"), $release->version);

            $this->refuseBlockedUpdate('plugin', $slug);

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

            // Run the plugin's UpdateSeeder if the class exists — the
            // opt-in convention for update-time seed logic. Kept
            // separate from DatabaseSeeder (which runs on install)
            // so an install-time seeder that inserts demo data does
            // not accidentally re-run on every update and duplicate
            // rows. Extension authors write UpdateSeeder for the
            // subset of seed operations that are safe to re-run
            // (typically insertOrIgnore / updateOrCreate against
            // settings tables to backfill newly-declared defaults).
            // Silent no-op when the class is absent — existing
            // plugins are unaffected until they opt in.
            $updateSeederClass = "Plugins\\{$plugin->directory}\\Database\\Seeders\\UpdateSeeder";
            if (class_exists($updateSeederClass)) {
                $this->info('Running plugin UpdateSeeder...');
                $this->call('dls:plugin:seed', [
                    'plugin' => $plugin->directory,
                    '--class' => 'UpdateSeeder',
                    '--force' => true,
                ]);
            }

            $plugin->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
                'update_failed_at' => null,
                'update_failure_reason' => null,
            ]);

            // Successful update — discard the transient snapshot, and prune
            // the persistent backups down to the retention limit (keeping
            // the one just taken so dls:plugin:rollback can restore it).
            if ($snapshotPath !== null) {
                $snapshotter->discard($snapshotPath);
                $snapshotPath = null;
            }
            if ($backupPath !== null) {
                $this->pruneExtensionBackups(
                    ExtensionSourceSnapshot::KIND_PLUGIN,
                    $plugin->directory,
                    (int) config('extension_backups.retention', 3),
                );
            }

            // Rebuild front-end assets that ship with the plugin. Default mode
            // is 'auto' so a release ZIP that already carries prebuilt
            // resources/assets short-circuits the build — production hosts
            // are not guaranteed to have Node, and the release-artifact
            // contract on BuildsExtensionAssets requires build-pipeline
            // extensions to bundle prebuilt output. The extract step above
            // (extractZipReplacingDir) does a wholesale replace of the live
            // tree, so what buildExtensionAssets sees under resources/assets
            // is exactly what the ZIP shipped; if it is missing, the auto
            // branch falls back to a build. --build still forces a rebuild
            // for source / dev installs; --skip-build never builds.
            $this->buildExtensionAssets($livePath, $this->resolveAssetBuildMode());

            // Regenerate the Tailwind plugin-source aggregator: the
            // updated plugin may have added, removed, or moved
            // `declares.tailwind_content` paths in its new plugin.json.
            app(\App\Services\Tailwind\PluginSourceAggregator::class)->regenerate();

            // Backfill any settings defaults the new plugin version added.
            // Plugins that opt into ProvidesSettingsDefaultsInterface get
            // their new keys inserted (insertOrIgnore, so operator-edited
            // values are untouched); plugins that do not opt in are a no-op.
            $sync = app(\App\Services\Extension\ExtensionSettingsDefaultsSync::class)
                ->syncForExtension($livePath, 'plugin');
            if ($sync['synced_keys'] !== []) {
                $this->line(sprintf(
                    'Settings defaults synced into %s: %d key(s).',
                    $sync['table'],
                    count($sync['synced_keys']),
                ));
            }

            // Discard any Blade views compiled against the previous version.
            // Same rationale as PluginRollback / ThemeRollback (which already
            // do this): the swap changed .blade.php files, but the compiled
            // storage/framework/views/*.php cache still points at the pre-swap
            // shape until it is dropped. Paired with view:cache in
            // CompiledViewCacheRebuilder so the next request does not
            // write to storage/framework/views/ mid-navigation (Vite,
            // watching that directory in dev with `npm run dev`, cancels
            // the navigation on a mid-flight write — the "click a menu,
            // land back on the same page" symptom).
            \App\Services\View\CompiledViewCacheRebuilder::rebuild();

            $this->info("Plugin '{$slug}' updated to v{$release->version} successfully.");

            // Refresh the audit so health / permissions / CSP reflect the new
            // version and the "rescan recommended" warning clears. Best-effort:
            // never fails the (already-committed) update. Gated by the
            // extension_auto_scan_after_update security setting.
            $this->autoScanAfterUpdate('plugin', $slug);

            ExtensionUpdateRecorder::succeeded('update', $plugin->refresh(), $before, $this->appliedById(), [
                'downloaded_sha256' => $downloadedSha256,
                'backup' => $backupPath !== null ? basename($backupPath) : null,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Roll back the plugin tree from the pre-update backup (or the
            // transient snapshot when --skip-backup) so the user is not
            // stranded on a half-extracted directory. The persistent backup
            // is a plain directory copy, so ExtensionSourceSnapshot::restore
            // handles it too; keep the backup afterwards so the operator
            // still has a restore point.
            $recoverSource = $backupPath ?? $snapshotPath;
            if ($recoverSource !== null) {
                try {
                    $livePath = base_path("plugins/{$plugin->directory}");
                    $snapshotter->restore($recoverSource, $livePath);
                    $this->warn('Plugin source rolled back from the pre-update backup.');
                    if ($snapshotPath !== null) {
                        $snapshotter->discard($snapshotPath);
                    }
                } catch (\Throwable $restoreError) {
                    $this->error("ROLLBACK FAILED: {$restoreError->getMessage()}");
                    $this->error("Manual recovery required. Backup retained at: {$recoverSource}");
                }
            }

            // A refused version was scanned while it was on disk; scan the
            // restored one so the stored audit matches what is running.
            if ($e instanceof ExtensionUpdateBlockedException && $recoverSource !== null) {
                $this->rescanAfterRefusedUpdate('plugin', $slug);
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

            ExtensionUpdateRecorder::failed('update', $plugin, $before['version'], $targetVersion, $this->appliedById(), $e->getMessage(), [
                'downloaded_sha256' => $downloadedSha256,
            ]);

            $this->error("Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Highest applied migration batch for this plugin, or 0 when the
     * plugin_migrations table is missing (fresh install / test env) or the
     * plugin has no migrations yet. Best-effort — a null answer is fine
     * for the rollback path because it just means "same as current", i.e.
     * step=0 and the delegated migrate:rollback call is skipped.
     */
    protected function currentPluginMigrationBatch(string $slug): int
    {
        try {
            $repository = new PluginMigrationRepository(
                app(ConnectionResolverInterface::class),
                'plugin_migrations',
                $slug,
            );

            return $repository->getLastBatchNumber();
        } catch (\Throwable) {
            return 0;
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

    /**
     * Member id from --applied-by, or null for a CLI run.
     */
    private function appliedById(): ?int
    {
        $id = $this->option('applied-by');

        return is_numeric($id) ? (int) $id : null;
    }
}
