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

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backup creation command
 *
 * Example:
 *   php artisan dls:backup:create
 *   php artisan dls:backup:create --targets=database
 *   php artisan dls:backup:create --targets=database,media,private --retention=30
 *   php artisan dls:backup:create --targets=database --format=json
 *
 * With --format=json the command writes exactly one JSON object to stdout
 * and everything else to stderr, so a tool can read the record id without
 * parsing prose. The field names are a contract: see
 * docs/operations/maintenance/backup-commands.md.
 */
class BackupCreateCommand extends Command
{
    private const FORMAT_TEXT = 'text';

    private const FORMAT_JSON = 'json';

    protected $signature = 'dls:backup:create
                            {--targets= : Comma-separated targets (database,media,private,custom,logs). Defaults to all except logs.}
                            {--retention= : Retention period in days. Leave empty to keep indefinitely.}
                            {--format=text : Output format: text, or json for one machine-readable object on stdout.}';

    protected $description = 'Create a new backup';

    public function handle(BackupServiceInterface $backupService): int
    {
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, [self::FORMAT_TEXT, self::FORMAT_JSON], true)) {
            $this->error("Unknown format: {$format}. Use text or json.");

            return self::INVALID;
        }
        $json = $format === self::FORMAT_JSON;

        $targetsOption = (string) $this->option('targets');
        if ($targetsOption !== '') {
            $targets = array_values(array_filter(array_map('trim', explode(',', $targetsOption))));
        } else {
            $targets = $backupService->getDefaultTargets();
        }

        $available = $backupService->getAvailableTargets();
        $invalid = array_diff($targets, $available);
        if (! empty($invalid)) {
            $message = 'Invalid target(s): '.implode(', ', $invalid);
            if ($json) {
                $this->emitJson(BackupResultDTO::failure($message), $targets);
                $this->stderr('Available targets: '.implode(', ', $available));
            } else {
                $this->error($message);
                $this->line('Available targets: '.implode(', ', $available));
            }

            return self::INVALID;
        }

        $options = [];
        $retention = $this->option('retention');
        if ($retention !== null && $retention !== '') {
            $options['retention_days'] = (int) $retention;
        }

        $this->progress('Creating backup with targets: '.implode(', ', $targets), $json);
        if (isset($options['retention_days'])) {
            $this->progress("Retention: {$options['retention_days']} days", $json);
        }

        if ($json) {
            try {
                $result = $backupService->backup($targets, $options);
            } catch (\Throwable $e) {
                $result = BackupResultDTO::failure($e->getMessage());
            }
            $this->emitJson($result, $targets);

            return $result->success ? self::SUCCESS : self::FAILURE;
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

    /**
     * Write the result as the single JSON object of --format=json.
     *
     * @param  string[]  $requestedTargets  Reported when the result carries none (a failure)
     */
    private function emitJson(BackupResultDTO $result, array $requestedTargets): void
    {
        $payload = [
            'success' => $result->success,
            'backup_record_id' => $result->backupRecordId,
            'file_path' => $result->filePath,
            'file_name' => $result->filePath !== null ? basename($result->filePath) : null,
            'file_size' => $result->fileSize,
            'duration' => $result->duration !== null ? round($result->duration, 2) : null,
            'targets' => array_values($result->targets !== [] ? $result->targets : $requestedTargets),
            'hash' => $result->metadata['hash'] ?? null,
            'error' => $result->error,
        ];

        $this->output->writeln(
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            OutputInterface::OUTPUT_RAW,
        );
    }

    /**
     * Progress lines go to stdout as text, and to stderr in JSON mode.
     */
    private function progress(string $message, bool $json): void
    {
        $json ? $this->stderr($message) : $this->info($message);
    }

    /**
     * Write to stderr. An output without a separate error stream (one
     * captured by Artisan::call(), say) drops the line instead, because
     * stdout must hold nothing but the JSON object.
     */
    private function stderr(string $message): void
    {
        $output = $this->output->getOutput();
        if ($output instanceof ConsoleOutputInterface) {
            $output->getErrorOutput()->writeln($message, OutputInterface::OUTPUT_RAW);
        }
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
