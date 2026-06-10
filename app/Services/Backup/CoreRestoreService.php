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

namespace App\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\Contracts\Backup\RestoreServiceInterface;
use App\Contracts\Verification\FileVerificationServiceInterface;
use App\DTO\Backup\RestoreResultDTO;
use App\Events\DixlaseEvents;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Core restore service
 *
 * Default implementation for restore and rollback from backup.
 * Automatically takes a safety snapshot before restoration.
 */
class CoreRestoreService implements RestoreServiceInterface
{
    /**
     * Subdirectories to protect from target (preserve during restore)
     */
    private const PRIVATE_PRESERVE_DIRS = [
        'backups',
        '.backup-tmp',
    ];

    public function __construct(
        private FileVerificationServiceInterface $verifier,
        private BackupServiceInterface $backupService,
    ) {}

    public function restore(BackupRecord $backup, array $targets = [], array $options = []): RestoreResultDTO
    {
        $startTime = microtime(true);

        // Check backup file existence
        if (! $backup->file_path || ! file_exists($backup->file_path)) {
            return RestoreResultDTO::failure("Backup file not found: {$backup->file_path}");
        }

        // Hash verification (if recorded)
        if ($backup->hash) {
            $algorithm = $backup->hash_algorithm ?: 'sha256';
            if (! $this->verifier->verifyHash($backup->file_path, $backup->hash, $algorithm)) {
                return RestoreResultDTO::failure('Backup file hash mismatch (file may be corrupted)');
            }
        }

        // Determine restore targets (all targets in backup if not specified)
        $availableTargets = is_array($backup->targets) ? $backup->targets : [];
        $targets = empty($targets)
            ? $availableTargets
            : array_values(array_intersect($targets, $availableTargets));

        if (empty($targets)) {
            return RestoreResultDTO::failure('No valid targets to restore');
        }

        Event::dispatch(DixlaseEvents::BACKUP_RESTORE_STARTED, [
            'backup_record_id' => $backup->id,
            'path' => $backup->file_path,
            'targets' => $targets,
        ]);

        // Open ZIP
        $zip = new \ZipArchive();
        if ($zip->open($backup->file_path) !== true) {
            Event::dispatch(DixlaseEvents::BACKUP_RESTORE_FAILED, [
                'backup_record_id' => $backup->id,
                'path' => $backup->file_path,
                'error' => 'Failed to open backup archive',
            ]);

            return RestoreResultDTO::failure('Failed to open backup archive');
        }

        // Safety snapshot before restore
        $preRestoreBackupId = null;
        if (! ($options['skip_pre_restore_backup'] ?? false)) {
            $snapshot = $this->backupService->backup($targets, [
                'retention_days' => $options['pre_restore_retention_days'] ?? 30,
            ]);
            if ($snapshot->success) {
                $preRestoreBackupId = $snapshot->backupRecordId;
            }
            // Snapshot failure is not fatal (log only)
        }

        // Create RestoreRecord
        $restoreRecord = $this->createRestoreRecord($backup, $preRestoreBackupId, $targets);

        try {
            foreach ($targets as $target) {
                $this->restoreTarget($zip, $target);
            }

            $duration = microtime(true) - $startTime;
            $restoreRecord->markAsCompleted((int) round($duration));

            $zip->close();

            Event::dispatch(DixlaseEvents::BACKUP_RESTORE_COMPLETED, [
                'backup_record_id' => $backup->id,
                'restore_record_id' => $restoreRecord->id,
                'path' => $backup->file_path,
                'duration' => $duration,
                'targets' => $targets,
            ]);

            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_RESTORED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_WARNING,
                'actor' => auth()->user(),
                'target' => $restoreRecord,
                'context' => [
                    'backup_record_id' => $backup->id,
                    'pre_restore_backup_id' => $preRestoreBackupId,
                    'targets' => $targets,
                    'duration_seconds' => round($duration, 2),
                ],
            ]);

            return RestoreResultDTO::success(
                restoreRecordId: $restoreRecord->id,
                preRestoreBackupRecordId: $preRestoreBackupId,
                duration: $duration,
                targets: $targets,
            );
        } catch (\Throwable $e) {
            $zip->close();
            $restoreRecord->markAsFailed($e->getMessage());

            Event::dispatch(DixlaseEvents::BACKUP_RESTORE_FAILED, [
                'backup_record_id' => $backup->id,
                'restore_record_id' => $restoreRecord->id,
                'path' => $backup->file_path,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_RESTORE_FAILED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_WARNING,
                'actor' => auth()->user(),
                'target' => $restoreRecord,
                'context' => [
                    'backup_record_id' => $backup->id,
                    'targets' => $targets,
                    'error' => $e->getMessage(),
                ],
            ]);

            return RestoreResultDTO::failure($e->getMessage(), $restoreRecord->id);
        }
    }

    public function rollback(RestoreRecord $restore): RestoreResultDTO
    {
        if (! $restore->canRollback()) {
            return RestoreResultDTO::failure('Restore cannot be rolled back (status not completed or no pre-restore backup)');
        }

        $preRestoreBackup = $restore->preRestoreBackup;
        if (! $preRestoreBackup) {
            return RestoreResultDTO::failure('Pre-restore backup not found');
        }

        // Restore from safety snapshot (do not take another snapshot)
        $result = $this->restore($preRestoreBackup, [], ['skip_pre_restore_backup' => true]);

        if ($result->success) {
            $restore->markAsRolledBack();

            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_ROLLED_BACK,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_WARNING,
                'actor' => auth()->user(),
                'target' => $restore,
                'context' => [
                    'original_backup_id' => $restore->backup_record_id,
                    'pre_restore_backup_id' => $preRestoreBackup->id,
                    'targets' => $restore->targets,
                ],
            ]);
        }

        return $result;
    }

    /**
     * Create RestoreRecord
     *
     * @param  string[]  $targets
     */
    private function createRestoreRecord(BackupRecord $backup, ?int $preRestoreBackupId, array $targets): RestoreRecord
    {
        $actor = auth()->user();

        return RestoreRecord::create([
            'backup_record_id' => $backup->id,
            'pre_restore_backup_id' => $preRestoreBackupId,
            'restored_by' => $actor?->id,
            'restored_by_name' => $actor?->email ?? 'system',
            'restored_at' => now(),
            'targets' => $targets,
            'status' => RestoreRecord::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Restore specified targets
     */
    private function restoreTarget(\ZipArchive $zip, string $target): void
    {
        match ($target) {
            BackupServiceInterface::TARGET_DATABASE => $this->restoreDatabase($zip),
            BackupServiceInterface::TARGET_MEDIA => $this->restoreDirectory(
                $zip,
                $this->resolveNamespace($zip, 'storage/app/public', 'media'),
                storage_path('app/public'),
            ),
            BackupServiceInterface::TARGET_PRIVATE => $this->restoreDirectory(
                $zip,
                $this->resolveNamespace($zip, 'storage/app/private', 'private'),
                storage_path('app/private'),
                self::PRIVATE_PRESERVE_DIRS,
            ),
            BackupServiceInterface::TARGET_CUSTOM => $this->restoreDirectory($zip, 'custom', base_path('custom')),
            BackupServiceInterface::TARGET_LOGS => $this->restoreDirectory(
                $zip,
                $this->resolveNamespace($zip, 'storage/logs', 'logs'),
                storage_path('logs'),
            ),
            BackupServiceInterface::TARGET_CORE_SOURCE => $this->restoreCoreSource($zip),
            BackupServiceInterface::TARGET_PLUGINS_ALL => $this->restoreDirectory($zip, 'plugins', base_path('plugins')),
            BackupServiceInterface::TARGET_THEMES_ALL => $this->restoreDirectory($zip, 'themes', base_path('themes')),
            default => throw new \InvalidArgumentException("Unknown restore target: {$target}"),
        };
    }

    /**
     * Resolve the ZIP namespace for a directory target.
     *
     * Manifest v2 archives store directory targets under their
     * core-relative paths (e.g. storage/app/public). Archives created
     * before that change used flat top-level names (media/, private/,
     * logs/); fall back to the legacy prefix when the archive has no
     * entries under the current one, so old backups stay restorable.
     */
    private function resolveNamespace(\ZipArchive $zip, string $namespace, string $legacyNamespace): string
    {
        $prefix = $namespace.'/';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with((string) $zip->getNameIndex($i), $prefix)) {
                return $namespace;
            }
        }

        return $legacyNamespace;
    }

    /**
     * Restore the core source tree from `core/<rel-path>/` entries in
     * the backup ZIP. Mirror of CoreBackupService::addCoreSourceToZip()
     * — iterates the same SOURCE_DIRECTORIES + SOURCE_FILES whitelist
     * and restores whatever the backup actually captured (entries
     * missing from the ZIP are skipped, which matches how the backup
     * skipped missing live entries).
     */
    private function restoreCoreSource(\ZipArchive $zip): void
    {
        foreach (\App\Services\Core\CoreSourceSnapshot::SOURCE_DIRECTORIES as $rel) {
            $this->restoreDirectory($zip, 'core/'.$rel, base_path($rel));
        }

        foreach (\App\Services\Core\CoreSourceSnapshot::SOURCE_FILES as $rel) {
            $contents = $zip->getFromName('core/'.$rel);
            if ($contents === false) {
                continue;
            }
            $dest = base_path($rel);
            \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($dest));
            file_put_contents($dest, $contents);
        }
    }

    /**
     * Restore database (execute database.sql in ZIP)
     */
    private function restoreDatabase(\ZipArchive $zip): void
    {
        $sql = $zip->getFromName('database.sql');
        if ($sql === false) {
            throw new \RuntimeException('database.sql not found in backup archive');
        }

        $statements = $this->splitSqlStatements($sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || $statement === ';') {
                continue;
            }
            DB::unprepared($statement);
        }
    }

    /**
     * Split SQL into statements (simple parser)
     *
     * @return string[]
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = ltrim($line);
            // Skip comment lines
            if (str_starts_with($trimmed, '--') || $trimmed === '') {
                continue;
            }
            $current .= $line."\n";
            // Finalize statement if line ends with ;
            if (preg_match('/;\s*$/', $line)) {
                $statements[] = $current;
                $current = '';
            }
        }
        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * Restore directory (clear existing contents then extract from ZIP)
     *
     * @param  string[]  $preserveDirs  Subdirectories to protect from clearing
     */
    private function restoreDirectory(\ZipArchive $zip, string $namespace, string $destDir, array $preserveDirs = []): void
    {
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Clear existing contents (except preserveDirs)
        $this->clearDirectory($destDir, $preserveDirs);

        // Extract entries for the corresponding namespace from ZIP
        $prefix = $namespace.'/';
        $extracted = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (! str_starts_with($entryName, $prefix)) {
                continue;
            }

            $relativePath = substr($entryName, strlen($prefix));
            // Skip directory entries
            if ($relativePath === '' || str_ends_with($relativePath, '/')) {
                continue;
            }

            $destPath = $destDir.'/'.$relativePath;
            $destSubDir = dirname($destPath);
            if (! is_dir($destSubDir)) {
                mkdir($destSubDir, 0755, true);
            }

            $contents = $zip->getFromIndex($i);
            if ($contents !== false) {
                file_put_contents($destPath, $contents);
                $extracted++;
            }
        }
    }

    /**
     * Delete directory contents (excluding immediate subdirectories contained in preserveDirs)
     *
     * @param  string[]  $preserveDirs
     */
    private function clearDirectory(string $dir, array $preserveDirs = []): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $name = $item->getFilename();
            if ($item->isDir() && in_array($name, $preserveDirs, true)) {
                continue;
            }

            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }

    /**
     * Recursively delete directory
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
