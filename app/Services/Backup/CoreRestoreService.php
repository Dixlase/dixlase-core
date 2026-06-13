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
use App\Models\Plugin;
use App\Models\RestoreRecord;
use App\Models\Theme;
use Illuminate\Support\Facades\Artisan;
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

    /**
     * Directory names always preserved at any depth when restoring the
     * plugins/themes source-tree targets, regardless of what the
     * archive contains. Restoring a stale .git over a live checkout
     * corrupts the repository, so .git is never cleared nor extracted
     * — even from archives created before the backup-side exclusion
     * existed. The rest of the preserve set is read per-archive from
     * manifest.json (`excluded_dir_names`): only what the backup
     * actually skipped is protected, so an archive that does contain
     * e.g. vendor/ restores it faithfully.
     */
    private const FORCED_SOURCE_TREE_PRESERVES = ['.git'];

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

        // Snapshot the bookkeeping tables as raw attribute arrays
        // BEFORE any target runs: restoring the database target
        // replaces every table with the dump, which erases rows
        // created after the dump was taken — including the record of
        // the backup being restored, the pre-restore safety
        // snapshot's record, and the RestoreRecord for this very
        // restore. The backup ZIPs on disk do not rewind with the
        // data, so these two tables must keep reflecting reality.
        $bookkeepingBackupRows = BackupRecord::query()
            ->get()->map(fn (BackupRecord $row) => $row->getAttributes())->all();
        $bookkeepingRestoreRows = RestoreRecord::query()
            ->get()->map(fn (RestoreRecord $row) => $row->getAttributes())->all();

        try {
            $sourceTreePreserves = $this->archivePreservedDirNames($zip);

            foreach ($targets as $target) {
                $this->restoreTarget($zip, $target, $sourceTreePreserves);
            }

            if (in_array(BackupServiceInterface::TARGET_DATABASE, $targets, true)) {
                $this->reinsertBookkeepingRows($bookkeepingBackupRows, $bookkeepingRestoreRows);
            }

            if (in_array(BackupServiceInterface::TARGET_CORE_SOURCE, $targets, true)) {
                $this->relinkPublicAssets();
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

            // The database target may have been imported before the
            // failure, erasing the bookkeeping rows — put them back so
            // markAsFailed() below has a row to update and the restore
            // shows up as failed in the history.
            try {
                $this->reinsertBookkeepingRows($bookkeepingBackupRows, $bookkeepingRestoreRows);
            } catch (\Throwable) {
                // The DB may be unusable mid-restore; the original error matters more.
            }

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
     * Re-insert bookkeeping rows erased by a database restore.
     *
     * database.sql replaces every table with the dump's contents, so
     * rows created after the dump vanish: the BackupRecord being
     * restored, the pre-restore safety snapshot's record, the
     * RestoreRecord of this restore, and any other backup/restore
     * history accumulated since the dump. Without them the restore
     * cannot be rolled back from the UI and the snapshot ZIPs become
     * orphan files invisible to retention cleanup. The ZIPs on disk
     * do not rewind with the data, so the pre-import state of these
     * two tables is upserted back wholesale (original IDs kept so
     * rows keep referencing each other).
     *
     * @param  array<int,array<string,mixed>>  $backupRows
     * @param  array<int,array<string,mixed>>  $restoreRows
     */
    private function reinsertBookkeepingRows(array $backupRows, array $restoreRows): void
    {
        if ($backupRows !== []) {
            BackupRecord::query()->getQuery()->upsert($backupRows, ['id']);
        }

        if ($restoreRows !== []) {
            RestoreRecord::query()->getQuery()->upsert($restoreRows, ['id']);
        }
    }

    /**
     * Recreate infrastructure symlinks after a core source restore.
     *
     * Backup archives cannot represent symlinks, so restoring
     * core_source rebuilds public/ without the per-extension asset
     * links under public/assets/themes and public/assets/plugins
     * (and without public/storage if it was ever lost). Every theme
     * and plugin asset then 404s and the front page renders
     * unstyled, so regenerate the links the same way the installer
     * does. The symlink commands are idempotent (no-op when the
     * link already exists).
     */
    private function relinkPublicAssets(): void
    {
        $storageLink = public_path('storage');
        if (! is_link($storageLink) && ! file_exists($storageLink)) {
            Artisan::call('storage:link');
        }

        foreach (Theme::query()->whereNotNull('directory')->pluck('directory') as $directory) {
            Artisan::call('dls:theme:symlink', ['action' => 'create', 'theme' => $directory]);
        }

        foreach (Plugin::query()->whereNotNull('directory')->pluck('directory') as $directory) {
            Artisan::call('dls:plugin:symlink', ['action' => 'create', 'plugin' => $directory]);
        }
    }

    /**
     * Restore specified targets
     *
     * @param  string[]  $sourceTreePreserveDirNames  Directory names preserved at any depth for plugins/themes
     */
    private function restoreTarget(\ZipArchive $zip, string $target, array $sourceTreePreserveDirNames = []): void
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
            BackupServiceInterface::TARGET_PLUGINS_ALL => $this->restoreDirectory(
                $zip,
                'plugins',
                base_path('plugins'),
                preserveDirNames: $sourceTreePreserveDirNames,
            ),
            BackupServiceInterface::TARGET_THEMES_ALL => $this->restoreDirectory(
                $zip,
                'themes',
                base_path('themes'),
                preserveDirNames: $sourceTreePreserveDirNames,
            ),
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
     * Directory names to preserve when restoring plugins/themes:
     * always .git, plus whatever the archive's manifest says was
     * excluded at backup time. Only names absent from the archive are
     * preserved — names the backup did capture are cleared and
     * re-extracted so the restored tree stays internally consistent
     * (e.g. a vendor/ included in the backup is restored at the exact
     * version matching the restored source).
     *
     * @return string[]
     */
    private function archivePreservedDirNames(\ZipArchive $zip): array
    {
        $names = self::FORCED_SOURCE_TREE_PRESERVES;

        $manifest = $zip->getFromName('manifest.json');
        if ($manifest !== false) {
            $decoded = json_decode($manifest, true);
            foreach ((array) ($decoded['excluded_dir_names'] ?? []) as $name) {
                if (is_string($name) && $name !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
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
     * @param  string[]  $preserveDirNames  Directory names to protect at any depth
     */
    private function restoreDirectory(
        \ZipArchive $zip,
        string $namespace,
        string $destDir,
        array $preserveDirs = [],
        array $preserveDirNames = [],
    ): void {
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Clear existing contents (except preserveDirs / preserveDirNames)
        $this->clearDirectory($destDir, $preserveDirs, $preserveDirNames);

        // Extract entries for the corresponding namespace from ZIP
        $prefix = $namespace.'/';
        $extracted = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (! str_starts_with($entryName, $prefix)) {
                continue;
            }

            $relativePath = substr($entryName, strlen($prefix));
            if ($relativePath === '') {
                continue;
            }

            // Never extract into preserved directories: archives created
            // before the backup-side exclusion still contain entries such
            // as plugins/<Name>/.git/..., and writing those stale copies
            // over a live checkout would corrupt it.
            if ($this->pathContainsDirName($relativePath, $preserveDirNames)) {
                continue;
            }

            // Directory entries are written by the backup only for
            // empty directories — recreate them so scaffold dirs
            // (e.g. custom/tests/Unit) survive a restore.
            if (str_ends_with($relativePath, '/')) {
                $emptyDir = $destDir.'/'.rtrim($relativePath, '/');
                if (! is_dir($emptyDir)) {
                    mkdir($emptyDir, 0755, true);
                }

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
     * Symlinks are left untouched: backup archives do not record
     * symlinks (the backup iterator skips them), so a restore can
     * never recreate one it deletes. Worse, isDir() follows links, so
     * recursing into a symlinked directory wipes data OUTSIDE the
     * restore target — e.g. clearing public/ would empty media via
     * the public/storage -> storage/app/public link. Links such as
     * public/storage are installer infrastructure, not restorable
     * content, so the safe move is to skip them entirely.
     *
     * @param  string[]  $preserveDirs
     * @param  string[]  $preserveDirNames  Directory names preserved at any depth
     */
    private function clearDirectory(string $dir, array $preserveDirs = [], array $preserveDirNames = []): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isLink()) {
                continue;
            }
            $name = $item->getFilename();
            if ($item->isDir() && (in_array($name, $preserveDirs, true) || in_array($name, $preserveDirNames, true))) {
                continue;
            }

            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname(), $preserveDirNames);
            } else {
                @unlink($item->getPathname());
            }
        }
    }

    /**
     * Recursively delete directory
     *
     * Symlinks are unlinked (the link itself), never recursed into:
     * isDir() follows links, so descending into a symlinked directory
     * would delete files outside the tree being removed.
     *
     * Directories whose name is in $preserveDirNames are skipped at
     * any depth; the final rmdir then fails silently for every
     * ancestor of a preserved directory, which is exactly what keeps
     * the preserved subtree in place.
     *
     * @param  string[]  $preserveDirNames
     */
    private function removeDirectory(string $dir, array $preserveDirNames = []): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isLink()) {
                @unlink($item->getPathname());

                continue;
            }
            if ($item->isDir()) {
                if (in_array($item->getFilename(), $preserveDirNames, true)) {
                    continue;
                }
                $this->removeDirectory($item->getPathname(), $preserveDirNames);
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    /**
     * Whether any directory segment of a ZIP-relative path matches one
     * of the given names. The trailing segment of a file entry is its
     * file name and is ignored, so a regular file that happens to be
     * named e.g. "vendor" is not mistaken for a preserved directory.
     *
     * @param  string[]  $dirNames
     */
    private function pathContainsDirName(string $relativePath, array $dirNames): bool
    {
        if ($dirNames === []) {
            return false;
        }

        $segments = explode('/', rtrim($relativePath, '/'));

        // Keep the trailing segment only for directory entries
        if (! str_ends_with($relativePath, '/')) {
            array_pop($segments);
        }

        foreach ($segments as $segment) {
            if (in_array($segment, $dirNames, true)) {
                return true;
            }
        }

        return false;
    }
}
