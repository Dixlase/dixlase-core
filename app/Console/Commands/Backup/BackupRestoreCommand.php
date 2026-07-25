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

use App\Models\BackupRecord;
use App\Models\CoreVersionHistory;
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
                            {id : The backup record ID to restore from}
                            {--targets= : Comma-separated subset of targets to restore. Defaults to all targets in the backup.}
                            {--no-snapshot : Skip the pre-restore safety snapshot.}
                            {--refetch-vendor : If the restore winds dependencies back, re-fetch the matching vendor/ from the old release under maintenance mode.}
                            {--force : Skip confirmation prompt.}';

    protected $description = 'Restore from a backup (destructive operation)';

    public function handle(CoreRestoreService $restoreService, CoreVendorManager $vendorManager): int
    {
        $id = (int) $this->argument('id');

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
        if ($dependencyRollback) {
            $this->warn('Dependency rollback detected — entering maintenance mode.');
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
            if ($dependencyRollback) {
                Artisan::call('up');
            }
        }
    }
}
