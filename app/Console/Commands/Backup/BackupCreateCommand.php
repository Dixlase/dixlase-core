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
use Illuminate\Console\Command;

/**
 * Backup creation command
 *
 * Example:
 *   php artisan dls:backup:create
 *   php artisan dls:backup:create --targets=database
 *   php artisan dls:backup:create --targets=database,media,private --retention=30
 */
class BackupCreateCommand extends Command
{
    protected $signature = 'dls:backup:create
                            {--targets= : Comma-separated targets (database,media,private,custom,logs). Defaults to all except logs.}
                            {--retention= : Retention period in days. Leave empty to keep indefinitely.}';

    protected $description = 'Create a new backup';

    public function handle(BackupServiceInterface $backupService): int
    {
        $targetsOption = (string) $this->option('targets');
        if ($targetsOption !== '') {
            $targets = array_values(array_filter(array_map('trim', explode(',', $targetsOption))));
        } else {
            $targets = $backupService->getDefaultTargets();
        }

        $available = $backupService->getAvailableTargets();
        $invalid = array_diff($targets, $available);
        if (! empty($invalid)) {
            $this->error('Invalid target(s): '.implode(', ', $invalid));
            $this->line('Available targets: '.implode(', ', $available));

            return self::INVALID;
        }

        $options = [];
        $retention = $this->option('retention');
        if ($retention !== null && $retention !== '') {
            $options['retention_days'] = (int) $retention;
        }

        $this->info('Creating backup with targets: '.implode(', ', $targets));
        if (isset($options['retention_days'])) {
            $this->line("Retention: {$options['retention_days']} days");
        }

        $result = $backupService->backup($targets, $options);

        if (! $result->success) {
            $this->error('Backup failed: '.$result->error);

            return self::FAILURE;
        }

        $this->info('Backup created successfully:');
        $this->line('  ID:       '.$result->backupRecordId);
        $this->line('  Path:     '.$result->filePath);
        $this->line('  Size:     '.$this->formatBytes($result->fileSize ?? 0));
        $this->line('  Duration: '.round($result->duration ?? 0, 2).'s');
        $this->line('  Hash:     '.($result->metadata['hash'] ?? 'N/A'));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), $i > 0 ? 2 : 0).' '.$units[$i];
    }
}
