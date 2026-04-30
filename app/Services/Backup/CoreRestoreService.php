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
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * コア復元サービス
 *
 * バックアップからの復元およびロールバックのデフォルト実装です。
 * 復元前に自動的にセーフティスナップショットを取得します。
 */
class CoreRestoreService implements RestoreServiceInterface
{
    /**
     * private 対象から保護するサブディレクトリ（復元時にも残す）
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

        // バックアップファイルの存在確認
        if (! $backup->file_path || ! file_exists($backup->file_path)) {
            return RestoreResultDTO::failure("Backup file not found: {$backup->file_path}");
        }

        // ハッシュ検証（記録されている場合）
        if ($backup->hash) {
            $algorithm = $backup->hash_algorithm ?: 'sha256';
            if (! $this->verifier->verifyHash($backup->file_path, $backup->hash, $algorithm)) {
                return RestoreResultDTO::failure('Backup file hash mismatch (file may be corrupted)');
            }
        }

        // 復元対象を決定（指定が空ならバックアップ内の全対象）
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

        // ZIP を開く
        $zip = new \ZipArchive();
        if ($zip->open($backup->file_path) !== true) {
            Event::dispatch(DixlaseEvents::BACKUP_RESTORE_FAILED, [
                'backup_record_id' => $backup->id,
                'path' => $backup->file_path,
                'error' => 'Failed to open backup archive',
            ]);

            return RestoreResultDTO::failure('Failed to open backup archive');
        }

        // 復元前のセーフティスナップショット
        $preRestoreBackupId = null;
        if (! ($options['skip_pre_restore_backup'] ?? false)) {
            $snapshot = $this->backupService->backup($targets, [
                'retention_days' => $options['pre_restore_retention_days'] ?? 30,
            ]);
            if ($snapshot->success) {
                $preRestoreBackupId = $snapshot->backupRecordId;
            }
            // スナップショット失敗は致命的ではない（ログのみ）
        }

        // RestoreRecord を作成
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

        // セーフティスナップショットから復元（さらにスナップショットは取らない）
        $result = $this->restore($preRestoreBackup, [], ['skip_pre_restore_backup' => true]);

        if ($result->success) {
            $restore->markAsRolledBack();
        }

        return $result;
    }

    /**
     * RestoreRecord を作成
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
     * 指定対象を復元
     */
    private function restoreTarget(\ZipArchive $zip, string $target): void
    {
        match ($target) {
            BackupServiceInterface::TARGET_DATABASE => $this->restoreDatabase($zip),
            BackupServiceInterface::TARGET_MEDIA => $this->restoreDirectory($zip, 'media', storage_path('app/public')),
            BackupServiceInterface::TARGET_PRIVATE => $this->restoreDirectory(
                $zip,
                'private',
                storage_path('app/private'),
                self::PRIVATE_PRESERVE_DIRS,
            ),
            BackupServiceInterface::TARGET_CUSTOM => $this->restoreDirectory($zip, 'custom', base_path('custom')),
            BackupServiceInterface::TARGET_LOGS => $this->restoreDirectory($zip, 'logs', storage_path('logs')),
            default => throw new \InvalidArgumentException("Unknown restore target: {$target}"),
        };
    }

    /**
     * データベースを復元（ZIP 内の database.sql を実行）
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
     * SQL を文単位に分割（簡易パーサー）
     *
     * @return string[]
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = ltrim($line);
            // コメント行はスキップ
            if (str_starts_with($trimmed, '--') || $trimmed === '') {
                continue;
            }
            $current .= $line."\n";
            // 行末が ; で終われば文を確定
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
     * ディレクトリを復元（既存内容をクリアしてから ZIP から展開）
     *
     * @param  string[]  $preserveDirs  クリアから保護するサブディレクトリ
     */
    private function restoreDirectory(\ZipArchive $zip, string $namespace, string $destDir, array $preserveDirs = []): void
    {
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // 既存内容をクリア（preserveDirs を除く）
        $this->clearDirectory($destDir, $preserveDirs);

        // ZIP から該当 namespace のエントリを展開
        $prefix = $namespace.'/';
        $extracted = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (! str_starts_with($entryName, $prefix)) {
                continue;
            }

            $relativePath = substr($entryName, strlen($prefix));
            // ディレクトリエントリは skip
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
     * ディレクトリの内容を削除（preserveDirs に含まれる直下のディレクトリは除外）
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
     * ディレクトリを再帰的に削除
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
