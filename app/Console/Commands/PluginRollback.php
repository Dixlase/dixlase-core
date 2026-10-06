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
use App\Console\Traits\TakesExtensionBackup;
use App\Models\Plugin;
use App\Services\Extension\ExtensionSourceSnapshot;
use App\Services\Extension\ExtensionUpdateRecorder;
use App\Services\PluginMigrationRepository;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Roll a plugin back to the state captured before its last update.
 *
 * Plugin counterpart of {@see ThemeRollback}. Restores the source tree AND
 * the prebuilt resources/assets from the automatic pre-update backup
 * ({@see TakesExtensionBackup}), refreshes the public asset symlink, and
 * delegates the schema half to dls:plugin:migrate:rollback. No npm is run.
 */
class PluginRollback extends Command
{
    use AutoScansExtensionAfterUpdate;
    use TakesExtensionBackup;

    protected $signature = 'dls:plugin:rollback
        {slug : Plugin slug to roll back}
        {--to= : Restore a specific backup timestamp (default: the newest)}
        {--force : Skip confirmation}
        {--applied-by= : Member id to record as the one who ran the rollback (the admin screen passes it; CLI runs record none)}';

    protected $description = 'Roll a plugin back to the state captured before its last update';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $plugin = Plugin::query()->where('slug', $slug)->first();
        if (! $plugin) {
            $this->error("Plugin '{$slug}' is not installed.");

            return self::FAILURE;
        }

        $kind = ExtensionSourceSnapshot::KIND_PLUGIN;
        $dir = $plugin->directory;

        $to = (string) $this->option('to');
        $backupPath = $this->resolveExtensionBackupPath($kind, $dir, $to);
        if ($backupPath === null) {
            $this->error($to !== ''
                ? "No backup '{$to}' found for plugin '{$slug}'."
                : "No backup found for plugin '{$slug}'. Nothing to roll back to.");
            $available = $this->listExtensionBackups($kind, $dir);
            if ($available !== []) {
                $this->line('Available backups: '.implode(', ', $available));
            }

            return self::FAILURE;
        }

        $livePath = base_path("plugins/{$dir}");

        $this->warn('Rollback is a DESTRUCTIVE operation that replaces the current plugin tree.');
        $this->line("Plugin: {$slug} ({$dir})");
        $this->line('Backup: '.basename($backupPath));

        if (! $this->option('force') && ! $this->confirm('Continue with rollback?', false)) {
            $this->info('Rollback cancelled.');

            return self::SUCCESS;
        }

        // For the audit entry and version-history row (dixlase-core#454).
        $before = ExtensionUpdateRecorder::snapshot($plugin);
        $restoredVersion = $this->readBackupMetadata($backupPath)['version'] ?? null;

        try {
            // Reverse the schema BEFORE restoring the source, so the
            // migrations added by the update are still on disk for
            // migrate:rollback to load and run their down(). Restoring the
            // old source first (as this used to do) deletes those migration
            // files, leaving migrate:rollback nothing to reverse — it would
            // silently roll back 0 migrations while reporting success, and
            // the schema (table/column) + the dls_plugin_migrations rows for
            // the update would survive. This mirrors the ordering the core
            // CoreRollback fix (PR #209) established. If the schema rollback
            // throws, we abort before touching the source, so the tree and
            // the schema both stay at the post-update version (consistent
            // and retryable).
            $this->rollbackSchemaFromBackupMetadata($plugin->slug, $dir, $backupPath);

            $asidePath = $this->restoreExtensionBackupInto($backupPath, $livePath);
            $this->info('Plugin source + prebuilt assets restored from backup (no npm run).');

            // Point the public asset symlink at the restored resources/assets.
            Artisan::call('dls:plugin:symlink', ['action' => 'create', 'plugin' => $dir]);

            // Keep the recorded version in step with the restored plugin.json.
            $this->syncPluginVersion($plugin, $livePath, $before['version']);

            // Discard compiled Blade against the (now-replaced) source and
            // pre-compile the restored version so the next request does not
            // write to storage/framework/views/ mid-navigation (Vite cancels
            // it in dev — see the helper's class docblock).
            \App\Services\View\CompiledViewCacheRebuilder::rebuild();

            $this->info("Plugin '{$slug}' rolled back to backup ".basename($backupPath).'.');
            $this->line("Previous (pre-rollback) tree kept at: {$asidePath}");
            $this->line('To undo this rollback, move that directory back into place.');

            // A rollback changes files just like an update — re-scan so the
            // audit reflects the restored version and the "rescan recommended"
            // warning clears. Best-effort; gated by extension_auto_scan_after_update.
            $this->autoScanAfterUpdate('plugin', $slug);

            ExtensionUpdateRecorder::succeeded('rollback', $plugin->refresh(), $before, $this->appliedById(), [
                'backup' => basename($backupPath),
            ]);

            // Consume the restore point now it has been applied, mirroring
            // dls:core:rollback. Once the newest backup is gone the admin
            // rollback button hides (unless an older backup remains to step
            // back to); the next update creates a fresh backup and it returns.
            $this->discardExtensionBackup($backupPath);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            ExtensionUpdateRecorder::failed('rollback', $plugin, $before['version'], is_string($restoredVersion) ? $restoredVersion : null, $this->appliedById(), $e->getMessage(), [
                'backup' => basename($backupPath),
            ]);

            $this->error("Rollback failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Reverse the schema changes introduced *between* the backup being
     * restored and the current live state — exactly the schema half of the
     * source rollback that just happened, no more.
     *
     * Reads `max_batch` from the backup's metadata sidecar, counts the
     * dls_plugin_migrations rows in the batches above it, and passes that
     * count as --step to dls:plugin:migrate:rollback (--step counts files,
     * not batches — dixlase-core#455). When the count is
     * zero (the just-undone update was schema-neutral, so no batches were
     * added), the delegated command is skipped entirely — reverting the
     * current batch here would over-rollback into an *earlier* update's
     * migrations and leave source@vN + schema@vN-1.
     *
     * When the sidecar is missing (pre-fix backups from before PR #123),
     * falls back to the pre-fix behaviour with a loud warning so the
     * operator knows to inspect plugin_migrations afterwards.
     */
    private function rollbackSchemaFromBackupMetadata(?string $pluginSlug, string $directoryName, string $backupPath): void
    {
        $backupMeta = $this->readBackupMetadata($backupPath);

        if ($backupMeta === null) {
            $this->warn(sprintf(
                "Backup '%s' has no migration metadata (older format). Delegating a whole-batch schema rollback; verify dls_plugin_migrations after the operation.",
                basename($backupPath),
            ));
            Artisan::call('dls:plugin:migrate:rollback', ['plugin' => $directoryName, '--force' => true]);

            return;
        }

        $backupBatch = (int) ($backupMeta['max_batch'] ?? 0);
        // --step counts migration files, not batches: an update that added
        // two migrations put both in one batch, and passing the batch delta
        // (1) reversed only the newer one (dixlase-core#455). Count the
        // ledger rows above the backup's batch instead.
        $stepsBack = $this->pluginMigrationsSinceBatch($pluginSlug, $backupBatch);

        if ($stepsBack === 0) {
            $this->line('No schema rollback needed — backup was taken at the current migration batch.');

            return;
        }

        Artisan::call('dls:plugin:migrate:rollback', [
            'plugin' => $directoryName,
            '--step' => $stepsBack,
            '--force' => true,
        ]);
    }

    /**
     * Ledger rows for this plugin in the batches after $batch, or 0 when the
     * ledger cannot be read.
     */
    private function pluginMigrationsSinceBatch(?string $slug, int $batch): int
    {
        if ($slug === null || $slug === '') {
            return 0;
        }

        try {
            return (new PluginMigrationRepository(
                app(ConnectionResolverInterface::class),
                'plugin_migrations',
                $slug,
            ))->countSinceBatch($batch);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Sync the DB version column to the version declared in the restored
     * plugin.json so a subsequent update detects the correct baseline.
     *
     * The version just rolled back from still exists upstream, so it stays
     * on offer: clearing available_version (as this used to) made the
     * screen say "up to date" until the next check (dixlase-core#454).
     */
    private function syncPluginVersion(Plugin $plugin, string $livePath, ?string $rolledBackFrom): void
    {
        $jsonPath = $livePath.'/plugin.json';
        if (! is_file($jsonPath)) {
            return;
        }

        $data = json_decode((string) File::get($jsonPath), true);
        $version = is_array($data) ? ($data['version'] ?? null) : null;
        if (is_string($version) && $version !== '') {
            $offer = is_string($rolledBackFrom) && version_compare($rolledBackFrom, $version, '>')
                ? $rolledBackFrom
                : null;
            $plugin->update(['version' => $version, 'available_version' => $offer]);
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
