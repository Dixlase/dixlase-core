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

use App\Contracts\Backup\BackupServiceInterface;
use App\Models\BackupRecord;
use Illuminate\Console\Command;

/**
 * 期限切れバックアップ削除コマンド
 *
 * retention_until が現在時刻を過ぎているバックアップを削除する。
 * cron 等で定期実行することを想定。
 *
 * 例:
 *   php artisan dls:backup:cleanup
 *   php artisan dls:backup:cleanup --dry-run
 */
class BackupCleanupCommand extends Command
{
    protected $signature = 'dls:backup:cleanup
                            {--dry-run : Show what would be deleted without actually deleting.}';

    protected $description = 'Delete backups whose retention period has expired';

    public function handle(BackupServiceInterface $backupService): int
    {
        $expired = BackupRecord::query()
            ->whereNotNull('retention_until')
            ->where('retention_until', '<=', now())
            ->whereNotIn('status', [BackupRecord::STATUS_DELETED])
            ->orderBy('id')
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired backups to clean up.');

            return self::SUCCESS;
        }

        $this->info('Found '.$expired->count().' expired backup(s):');
        foreach ($expired as $record) {
            $this->line(sprintf(
                '  #%d  %s  (retention_until: %s)',
                $record->id,
                $record->file_name,
                $record->retention_until?->format('Y-m-d H:i:s') ?? '-',
            ));
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run: no records were actually deleted.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;
        foreach ($expired as $record) {
            if ($backupService->delete($record)) {
                $deleted++;
            } else {
                $failed++;
                $this->error('Failed to delete backup #'.$record->id);
            }
        }

        $this->info("Deleted {$deleted} backup(s).".($failed > 0 ? " ({$failed} failed)" : ''));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
