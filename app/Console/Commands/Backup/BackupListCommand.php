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

use App\Models\BackupRecord;
use Illuminate\Console\Command;

/**
 * バックアップ一覧表示コマンド
 *
 * 例:
 *   php artisan dls:backup:list
 *   php artisan dls:backup:list --limit=50
 *   php artisan dls:backup:list --all
 */
class BackupListCommand extends Command
{
    protected $signature = 'dls:backup:list
                            {--all : Include deleted records.}
                            {--limit=20 : Maximum number of records to show.}';

    protected $description = 'List backup records';

    public function handle(): int
    {
        $query = BackupRecord::query()->orderByDesc('created_at');

        if (! $this->option('all')) {
            $query->whereNotIn('status', [BackupRecord::STATUS_DELETED]);
        }

        $records = $query->limit((int) $this->option('limit'))->get();

        if ($records->isEmpty()) {
            $this->info('No backups found.');

            return self::SUCCESS;
        }

        $rows = $records->map(fn ($r) => [
            'id' => $r->id,
            'created' => $r->created_at?->format('Y-m-d H:i:s') ?? '-',
            'type' => $r->type,
            'targets' => implode(',', $r->targets ?? []),
            'size' => $this->formatBytes((int) $r->file_size),
            'status' => $r->status,
            'verification' => $r->verification_status,
        ])->toArray();

        $this->table(
            ['ID', 'Created', 'Type', 'Targets', 'Size', 'Status', 'Verification'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), $i > 0 ? 2 : 0).' '.$units[$i];
    }
}
