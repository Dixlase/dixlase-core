<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Console\Commands\Backup;

use App\Helpers\ComposerLocalHelper;
use App\Models\BackupRecord;
use App\Models\CoreVersionHistory;
use App\Models\RestoreRecord;
use App\Services\Backup\CoreRestoreService;
use App\Services\Core\CoreVendorManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Backup restore command
 *
 * Example:
 *   php artisan dls:backup:restore 1
 *   php artisan dls:backup:restore 1 --targets=database
 *   php artisan dls:backup:restore 1 --no-snapshot --force
 *   php artisan dls:backup:restore 1 --refetch-vendor --force
 */
class BackupRestoreCommand extends Command
{
    protected $signature = 'dls:backup:restore
                            {id? : The backup record ID to restore from}
                            {--targets= : Comma-separated subset of targets to restore. Defaults to all targets in the backup.}
                            {--no-snapshot : Skip the pre-restore safety snapshot.}
                            {--refetch-vendor : If the restore winds dependencies back, re-fetch the matching vendor/ from the old release under maintenance mode.}
                            {--rollback-of= : Undo a completed restore (RestoreRecord ID) from its pre-restore snapshot instead of restoring a backup.}
                            {--force : Skip confirmation prompt.}';

    protected $description = 'Restore from a backup (destructive operation)';

    public function handle(CoreRestoreService $restoreService, CoreVendorManager $vendorManager): int
    {
        if ($this->option('rollback-of') !== null) {
            return $this->rollbackRestore($restoreService, (int) $this->option('rollback-of'));
        }

        $id = (int) $this->argument('id');

        if ($id === 0) {
            $this->error('Give a backup record ID, or --rollback-of=<restore id>.');

            return self::FAILURE;
        }

        $backup = BackupRecord::find($id);
        if (! $backup) {
            $this->error("Backup record not found: {$id}");

            return self::FAILURE;
        }

        if ($backup->status !== BackupRecord::STATUS_COMPLETED) {
            $this->error("Backup is not in completed status (current: {$backup->status})");

            return self::FAILURE;
        }

        $targetsOption = (string) $this->option('targets');
        $targets = $targetsOption !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $targetsOption))))
            : [];

        // A dependency rollback only applies when the operator opted in via
        // --refetch-vendor AND the backup actually winds composer.lock back.
        $dependencyRollback = (bool) $this->option('refetch-vendor')
            && $restoreService->crossesDependencyBoundary($backup);

        $this->warn('Restore is a DESTRUCTIVE operation that will overwrite current state.');
        $this->line('Backup:  #'.$backup->id.' '.$backup->file_name);
        $this->line('Targets: '.implode(', ', empty($targets) ? ($backup->targets ?? []) : $targets));
        $this->line('Snapshot: '.($this->option('no-snapshot') ? 'DISABLED' : 'enabled (auto)'));
        $this->line('Vendor:  '.($dependencyRollback ? 're-fetch from old release (maintenance mode)' : 'unchanged'));

        // Restoring code (core source, plugins, themes) clears each tree and
        // writes it back file by file. Requests landing in that window run a
        // half-written tree, so the site is taken down for it as well, not
        // only for a dependency rollback.
        $maintenance = $dependencyRollback || $restoreService->restoresCode($backup, $targets);

        if (! $this->option('force') && ! $this->confirm('Continue with restore?', false)) {
            $this->info('Restore cancelled.');

            return self::SUCCESS;
        }

        $options = [];
        if ($this->option('no-snapshot')) {
            $options['skip_pre_restore_backup'] = true;
        }

        // A dependency rollback rewinds source + DB and then swaps vendor/.
        // Take the whole operation down: public/index.php serves the
        // maintenance page (with --refresh) before booting the framework,
        // so the site stays safe while vendor/ is incomplete, and the
        // operator's browser returns automatically when we lift it.
        if ($maintenance) {
            $this->warn(($dependencyRollback ? 'Dependency rollback detected' : 'Restoring code').' — entering maintenance mode.');
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
        }

        try {
            $result = $restoreService->restore($backup, $targets, $options);

            if (! $result->success) {
                $this->error('Restore failed: '.$result->error);

                return self::FAILURE;
            }

            if ($dependencyRollback) {
                // Source + DB are now wound back, so the version history
                // reflects the old version — that is the release whose
                // vendor/ we need (the backup excludes vendor/ by design).
                CoreVersionHistory::forgetCurrentVersionCache();
                $version = CoreVersionHistory::currentVersion();

                if ($version === null) {
                    $this->error('Restore succeeded but the target version could not be resolved for vendor re-fetch.');
                    $this->line('Include the database target so version history rewinds, then re-run, or fix vendor/ manually.');
                    if ($result->preRestoreBackupRecordId !== null) {
                        $this->line('Pre-restore snapshot to undo this restore: #'.$result->preRestoreBackupRecordId);
                    }

                    return self::FAILURE;
                }

                try {
                    $vendorManager->refetchAndSwap($version, fn (string $line) => $this->line('[vendor] '.$line));

                    // Re-persist the site-local theme/plugin PSR-4 that the
                    // vendor swap wiped (see CoreUpdater); otherwise extension
                    // admin/settings pages 500 after a --refetch-vendor restore.
                    // Non-fatal.
                    if (! ComposerLocalHelper::syncAutoload()) {
                        $this->warn('[vendor] Extension autoload re-sync failed; run `'.ComposerLocalHelper::RECOVERY_COMMAND.'` if theme/plugin pages error.');
                    }

                    // Same as core update / rollback: rebuild the discovery
                    // manifest so it matches the re-fetched vendor/. See
                    // ComposerLocalHelper::rebuildPackageManifest().
                    if (! ComposerLocalHelper::rebuildPackageManifest()) {
                        $this->warn('[vendor] Package manifest rebuild failed; if the site returns 500, delete bootstrap/cache/packages.php and bootstrap/cache/services.php, then run `php artisan package:discover`.');
                    }
                } catch (\Throwable $e) {
                    $this->error('Vendor re-fetch failed: '.$e->getMessage());
                    $this->line('Source/DB are rolled back but vendor/ still matches the newer release.');
                    if ($result->preRestoreBackupRecordId !== null) {
                        $this->line('To undo this restore: php artisan dls:backup:restore '.$result->preRestoreBackupRecordId.' --force');
                    }

                    return self::FAILURE;
                }
            }

            $this->info('Restore completed successfully:');
            $this->line('  RestoreRecord ID:   '.$result->restoreRecordId);
            if ($result->preRestoreBackupRecordId !== null) {
                $this->line('  Pre-restore snapshot ID: '.$result->preRestoreBackupRecordId);
            }
            $this->line('  Duration:           '.round($result->duration ?? 0, 2).'s');
            $this->line('  Targets:            '.implode(', ', $result->targets));
            if ($dependencyRollback) {
                $this->line('  Vendor:             re-fetched from the restored release');
            }

            return self::SUCCESS;
        } finally {
            if ($maintenance) {
                Artisan::call('up');
            }
        }
    }

    /**
     * Undo a completed restore from its pre-restore snapshot, under
     * maintenance mode when that snapshot holds code. The admin screen
     * dispatches this to a detached process for the same reason it does a
     * code restore.
     */
    private function rollbackRestore(CoreRestoreService $restoreService, int $restoreId): int
    {
        $restore = RestoreRecord::find($restoreId);
        if (! $restore || ! $restore->canRollback() || ! $restore->preRestoreBackup) {
            $this->error("Restore #{$restoreId} cannot be rolled back.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Undo restore #'.$restoreId.'?', false)) {
            $this->info('Rollback cancelled.');

            return self::SUCCESS;
        }

        $maintenance = $restoreService->restoresCode($restore->preRestoreBackup);
        if ($maintenance) {
            $this->warn('Restoring code — entering maintenance mode.');
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
        }

        try {
            $result = $restoreService->rollback($restore);
            if (! $result->success) {
                $this->error('Rollback failed: '.$result->error);

                return self::FAILURE;
            }

            $this->info("Restore #{$restoreId} rolled back.");

            return self::SUCCESS;
        } finally {
            if ($maintenance) {
                Artisan::call('up');
            }
        }
    }
}
