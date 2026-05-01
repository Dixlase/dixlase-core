<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Contracts\Backup\RestoreServiceInterface;
use App\Models\BackupRecord;
use Illuminate\Console\Command;

/**
 * バックアップ復元コマンド
 *
 * 例:
 *   php artisan dls:backup:restore 1
 *   php artisan dls:backup:restore 1 --targets=database
 *   php artisan dls:backup:restore 1 --no-snapshot --force
 */
class BackupRestoreCommand extends Command
{
    protected $signature = 'dls:backup:restore
                            {id : The backup record ID to restore from}
                            {--targets= : Comma-separated subset of targets to restore. Defaults to all targets in the backup.}
                            {--no-snapshot : Skip the pre-restore safety snapshot.}
                            {--force : Skip confirmation prompt.}';

    protected $description = 'Restore from a backup (destructive operation)';

    public function handle(RestoreServiceInterface $restoreService): int
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

        $this->warn('Restore is a DESTRUCTIVE operation that will overwrite current state.');
        $this->line('Backup:  #'.$backup->id.' '.$backup->file_name);
        $this->line('Targets: '.implode(', ', empty($targets) ? ($backup->targets ?? []) : $targets));
        $this->line('Snapshot: '.($this->option('no-snapshot') ? 'DISABLED' : 'enabled (auto)'));

        if (! $this->option('force') && ! $this->confirm('Continue with restore?', false)) {
            $this->info('Restore cancelled.');

            return self::SUCCESS;
        }

        $options = [];
        if ($this->option('no-snapshot')) {
            $options['skip_pre_restore_backup'] = true;
        }

        $result = $restoreService->restore($backup, $targets, $options);

        if (! $result->success) {
            $this->error('Restore failed: '.$result->error);

            return self::FAILURE;
        }

        $this->info('Restore completed successfully:');
        $this->line('  RestoreRecord ID:   '.$result->restoreRecordId);
        if ($result->preRestoreBackupRecordId !== null) {
            $this->line('  Pre-restore snapshot ID: '.$result->preRestoreBackupRecordId);
        }
        $this->line('  Duration:           '.round($result->duration ?? 0, 2).'s');
        $this->line('  Targets:            '.implode(', ', $result->targets));

        return self::SUCCESS;
    }
}
