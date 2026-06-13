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
use App\Contracts\Verification\FileVerificationServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use App\Events\DixlaseEvents;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Core backup service
 *
 * Default implementation for manual backups
 * Targets: database, media, storage/app/private, custom/, (optional) storage/logs
 *
 * Advanced features such as scheduled execution, encryption, and remote storage
 * are overridden by backup plugins
 */
class CoreBackupService implements BackupServiceInterface
{
    /**
     * Optional targets not included in backup by default.
     *
     * TARGET_CORE_SOURCE / TARGET_PLUGINS_ALL / TARGET_THEMES_ALL are
     * intentionally opt-in: a routine operator-initiated backup should
     * not double up on source code that already lives in version
     * control. The "take a backup first" toggle on the admin updates
     * page selects them explicitly when the matching update path is
     * about to overwrite that source tree on disk.
     */
    private const OPTIONAL_TARGETS = [
        BackupServiceInterface::TARGET_LOGS,
        BackupServiceInterface::TARGET_CORE_SOURCE,
        BackupServiceInterface::TARGET_PLUGINS_ALL,
        BackupServiceInterface::TARGET_THEMES_ALL,
    ];

    /**
     * Subdirectories to exclude from private target
     */
    private const PRIVATE_EXCLUDE_DIRS = [
        'backups',
        '.backup-tmp',
    ];

    /**
     * Directory names always excluded at any depth from the
     * plugins/themes source-tree targets. VCS history must never be
     * captured: restoring a stale .git over a live checkout corrupts
     * the repository, so this is not operator-configurable.
     */
    private const FORCED_SOURCE_TREE_EXCLUDES = ['.git'];

    /**
     * Operator-configurable source-tree exclusions, keyed by the
     * setting that controls them. Unconfigured ('' / null) means
     * excluded — these are developer artifacts that bloat archives
     * (~90% of the file count under plugins/), so including them in a
     * backup is the explicit opt-in. The names actually excluded are
     * recorded in manifest.json (`excluded_dir_names`) so the restore
     * side preserves exactly what the archive does not contain.
     */
    private const OPTIONAL_SOURCE_TREE_EXCLUDES = [
        'backup.exclude_node_modules' => 'node_modules',
        'backup.exclude_vendor' => 'vendor',
    ];

    /**
     * Number of rows per batch for INSERT statements
     */
    private const DUMP_BATCH_SIZE = 100;

    public function __construct(
        private FileVerificationServiceInterface $verifier,
    ) {}

    public function backup(array $targets, array $options = []): BackupResultDTO
    {
        $startTime = microtime(true);
        $targets = $this->normalizeTargets($targets);

        if (empty($targets)) {
            return BackupResultDTO::failure('No valid backup targets specified');
        }

        $type = $this->determineType($targets);

        Event::dispatch(DixlaseEvents::BACKUP_STARTED, [
            'type' => $type,
            'targets' => $targets,
            'options' => $options,
        ]);

        $tempDir = $this->createTempDir();
        $zipPath = null;

        try {
            $fileName = $this->generateFileName($type);
            $zipPath = $this->ensureBackupDirectory().'/'.$fileName;

            $this->buildBackupArchive($targets, $tempDir, $zipPath);

            $hash = $this->verifier->hashFile($zipPath);
            $fileSize = filesize($zipPath) ?: 0;
            $duration = microtime(true) - $startTime;

            $record = $this->createBackupRecord(
                targets: $targets,
                type: $type,
                filePath: $zipPath,
                fileName: $fileName,
                fileSize: $fileSize,
                hash: $hash,
                durationSeconds: round($duration, 2),
                options: $options,
            );

            Event::dispatch(DixlaseEvents::BACKUP_COMPLETED, [
                'type' => $type,
                'path' => $zipPath,
                'size' => $fileSize,
                'duration' => $duration,
            ]);

            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_CREATED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_NOTICE,
                'actor' => auth()->user(),
                'target' => $record,
                'context' => [
                    'type' => $type,
                    'targets' => $targets,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'duration_seconds' => round($duration, 2),
                ],
            ]);

            return BackupResultDTO::success(
                backupRecordId: $record->id,
                filePath: $zipPath,
                fileSize: $fileSize,
                duration: $duration,
                targets: $targets,
                metadata: ['hash' => $hash],
            );
        } catch (\Throwable $e) {
            if ($zipPath !== null && file_exists($zipPath)) {
                @unlink($zipPath);
            }

            Event::dispatch(DixlaseEvents::BACKUP_FAILED, [
                'type' => $type,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_FAILED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_WARNING,
                'actor' => auth()->user(),
                'context' => [
                    'type' => $type,
                    'targets' => $targets,
                    'error' => $e->getMessage(),
                ],
            ]);

            return BackupResultDTO::failure($e->getMessage());
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    public function getAvailableTargets(): array
    {
        return [
            BackupServiceInterface::TARGET_DATABASE,
            BackupServiceInterface::TARGET_MEDIA,
            BackupServiceInterface::TARGET_PRIVATE,
            BackupServiceInterface::TARGET_CUSTOM,
            BackupServiceInterface::TARGET_LOGS,
            // Source-tree targets — exposed to the operator so the
            // backup-settings page can list them, but excluded from
            // the default selection set via OPTIONAL_TARGETS above.
            // Primarily driven by the admin updates page's
            // "take a backup first" toggle.
            BackupServiceInterface::TARGET_CORE_SOURCE,
            BackupServiceInterface::TARGET_PLUGINS_ALL,
            BackupServiceInterface::TARGET_THEMES_ALL,
        ];
    }

    public function getDefaultTargets(): array
    {
        return array_values(array_diff(
            $this->getAvailableTargets(),
            self::OPTIONAL_TARGETS,
        ));
    }

    public function delete(BackupRecord $record): bool
    {
        if ($record->file_path && file_exists($record->file_path)) {
            if (! @unlink($record->file_path)) {
                return false;
            }
        }

        $marked = $record->markAsDeleted();

        if ($marked) {
            Audit::log([
                'action' => AuditLog::ACTION_BACKUP_DELETED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_NOTICE,
                'actor' => auth()->user(),
                'target' => $record,
                'context' => [
                    'type' => $record->type,
                    'file_name' => $record->file_name,
                    'file_size' => $record->file_size,
                ],
            ]);
        }

        return $marked;
    }

    /**
     * Normalize received targets to only valid ones
     *
     * @param  string[]  $targets
     * @return string[]
     */
    private function normalizeTargets(array $targets): array
    {
        return array_values(array_unique(array_intersect($targets, $this->getAvailableTargets())));
    }

    /**
     * Determine backup type from targets
     *
     * @param  string[]  $targets
     */
    private function determineType(array $targets): string
    {
        $hasDb = in_array(BackupServiceInterface::TARGET_DATABASE, $targets, true);
        $hasFiles = ! empty(array_diff($targets, [BackupServiceInterface::TARGET_DATABASE]));

        if ($hasDb && $hasFiles) {
            return BackupRecord::TYPE_FULL;
        }
        if ($hasDb) {
            return BackupRecord::TYPE_DATABASE;
        }

        return BackupRecord::TYPE_FILES;
    }

    /**
     * Build backup archive
     *
     * @param  string[]  $targets
     */
    private function buildBackupArchive(array $targets, string $tempDir, string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create zip archive: {$zipPath}");
        }

        try {
            $sourceTreeExcludes = $this->resolveSourceTreeExcludeDirNames();

            foreach ($targets as $target) {
                $this->addTargetToZip($zip, $target, $tempDir, $sourceTreeExcludes);
            }

            // version 3: every target is stored under its core-relative
            // path so an extracted archive mirrors the live layout —
            // media/private/logs under storage/… (v2) and now the
            // core_source tree at the root (app/, config/, artisan, …)
            // instead of under a core/ wrapper (v1/v2).
            $manifest = [
                'version' => 3,
                'generator' => 'dixlase-core',
                'php_version' => PHP_VERSION,
                'created_at' => now()->toIso8601String(),
                'targets' => $targets,
                // Directory names excluded at any depth from the
                // plugins/themes targets — the restore side preserves
                // these on disk because the archive cannot rebuild them.
                'excluded_dir_names' => $sourceTreeExcludes,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } finally {
            $zip->close();
        }
    }

    /**
     * Add specified targets to ZIP
     *
     * Directory targets are placed under the same relative path they
     * occupy in a live installation (e.g. storage/app/public, not a
     * flat media/ folder), so an extracted archive mirrors the core
     * directory layout. CoreRestoreService relies on these prefixes.
     *
     * @param  string[]  $sourceTreeExcludeDirNames  Directory names excluded at any depth from plugins/themes
     */
    private function addTargetToZip(\ZipArchive $zip, string $target, string $tempDir, array $sourceTreeExcludeDirNames = []): void
    {
        match ($target) {
            BackupServiceInterface::TARGET_DATABASE => $this->addDatabaseToZip($zip, $tempDir),
            BackupServiceInterface::TARGET_MEDIA => $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/app/public'),
            BackupServiceInterface::TARGET_PRIVATE => $this->addDirectoryToZip(
                $zip,
                storage_path('app/private'),
                'storage/app/private',
                self::PRIVATE_EXCLUDE_DIRS,
            ),
            BackupServiceInterface::TARGET_CUSTOM => $this->addDirectoryToZip($zip, base_path('custom'), 'custom'),
            BackupServiceInterface::TARGET_LOGS => $this->addDirectoryToZip($zip, storage_path('logs'), 'storage/logs'),
            BackupServiceInterface::TARGET_CORE_SOURCE => $this->addCoreSourceToZip($zip),
            BackupServiceInterface::TARGET_PLUGINS_ALL => $this->addDirectoryToZip(
                $zip,
                base_path('plugins'),
                'plugins',
                excludeDirNames: $sourceTreeExcludeDirNames,
            ),
            BackupServiceInterface::TARGET_THEMES_ALL => $this->addDirectoryToZip(
                $zip,
                base_path('themes'),
                'themes',
                excludeDirNames: $sourceTreeExcludeDirNames,
            ),
            default => throw new \InvalidArgumentException("Unknown backup target: {$target}"),
        };
    }

    /**
     * Resolve the directory names to exclude from the plugins/themes
     * targets: the forced set plus every optional exclusion whose
     * setting is enabled or not yet configured (excluded by default).
     *
     * @return string[]
     */
    private function resolveSourceTreeExcludeDirNames(): array
    {
        $names = self::FORCED_SOURCE_TREE_EXCLUDES;

        foreach (self::OPTIONAL_SOURCE_TREE_EXCLUDES as $settingKey => $dirName) {
            $stored = \App\Models\SiteSetting::getValue($settingKey);
            if (! is_string($stored) || $stored === '' || $stored === '1') {
                $names[] = $dirName;
            }
        }

        return array_values($names);
    }

    /**
     * Add core source tree to ZIP.
     *
     * Mirrors the SOURCE_DIRECTORIES + SOURCE_FILES whitelist that
     * CoreSourceSnapshot uses when capturing a pre-upgrade snapshot —
     * the same path set the upgrade process is about to overwrite on
     * disk. Each path is stored under its own core-relative location
     * (app/…, config/…, artisan, …) so an extracted archive mirrors
     * the live installation layout. The whitelist itself is how the
     * restore step rediscovers these entries — no `core/` wrapper is
     * needed. (Manifest v2 and earlier wrapped them under `core/`;
     * CoreRestoreService falls back to that prefix for old archives.)
     */
    private function addCoreSourceToZip(\ZipArchive $zip): void
    {
        foreach (\App\Services\Core\CoreSourceSnapshot::SOURCE_DIRECTORIES as $rel) {
            $abs = base_path($rel);
            if (! is_dir($abs)) {
                continue;
            }
            $this->addDirectoryToZip($zip, $abs, $rel);
        }

        foreach (\App\Services\Core\CoreSourceSnapshot::SOURCE_FILES as $rel) {
            $abs = base_path($rel);
            if (! is_file($abs)) {
                continue;
            }
            $zip->addFile($abs, $rel);
        }
    }

    /**
     * Generate database dump and add to ZIP
     */
    private function addDatabaseToZip(\ZipArchive $zip, string $tempDir): void
    {
        $sqlFile = $tempDir.'/database.sql';
        $handle = fopen($sqlFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Failed to create database dump file');
        }

        try {
            $this->dumpDatabaseToHandle($handle);
        } finally {
            fclose($handle);
        }

        $zip->addFile($sqlFile, 'database.sql');
    }

    /**
     * Dump database in SQL format (write to stream)
     *
     * @param  resource  $handle
     */
    private function dumpDatabaseToHandle($handle): void
    {
        $defaultConnection = config('database.default');
        $driver = config("database.connections.{$defaultConnection}.driver");
        $prefix = (string) config("database.connections.{$defaultConnection}.prefix", '');

        fwrite($handle, "-- Dixlase Database Backup\n");
        fwrite($handle, '-- Generated: '.now()->toIso8601String()."\n");
        fwrite($handle, "-- Driver: {$driver}\n");
        fwrite($handle, "-- DO NOT EDIT THIS FILE MANUALLY\n\n");

        match ($driver) {
            'mysql' => $this->dumpMysql($handle, $prefix),
            'sqlite' => $this->dumpSqlite($handle, $prefix),
            default => throw new \RuntimeException("Database backup not supported for driver: {$driver}"),
        };
    }

    /**
     * Dump MySQL database
     *
     * @param  resource  $handle
     */
    private function dumpMysql($handle, string $prefix): void
    {
        $databaseName = DB::getDatabaseName();

        fwrite($handle, "-- Database: {$databaseName}\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET NAMES utf8mb4;\n\n");

        $tables = $this->getTablesForBackup($databaseName, $prefix);

        foreach ($tables as $table) {
            $this->dumpTable($handle, $table);
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * Dump SQLite database
     *
     * @param  resource  $handle
     */
    private function dumpSqlite($handle, string $prefix): void
    {
        fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

        // Get table list from sqlite_master in SQLite
        if ($prefix === '') {
            $rows = DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
            );
        } else {
            $likePrefix = str_replace(['_', '%'], ['\\_', '\\%'], $prefix).'%';
            $rows = DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name LIKE ? ESCAPE '\\' ORDER BY name",
                [$likePrefix],
            );
        }

        $tables = array_map(fn ($row) => array_values((array) $row)[0], $rows);

        foreach ($tables as $table) {
            $this->dumpSqliteTable($handle, $table);
        }

        fwrite($handle, "\nPRAGMA foreign_keys = ON;\n");
    }

    /**
     * Dump a single table in SQLite
     *
     * @param  resource  $handle
     */
    private function dumpSqliteTable($handle, string $table): void
    {
        fwrite($handle, "\n-- Table: {$table}\n");
        fwrite($handle, "DROP TABLE IF EXISTS \"{$table}\";\n");

        // Get CREATE TABLE statement
        $createRows = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        if (empty($createRows)) {
            return;
        }
        $createStatement = (array) $createRows[0];
        fwrite($handle, ($createStatement['sql'] ?? '').";\n\n");

        // Data
        $offset = 0;
        $pdo = DB::getPdo();

        while (true) {
            $rows = DB::select("SELECT * FROM \"{$table}\" LIMIT ? OFFSET ?", [self::DUMP_BATCH_SIZE, $offset]);
            if (empty($rows)) {
                break;
            }

            $columns = array_keys((array) $rows[0]);
            $columnList = implode(', ', array_map(fn ($c) => "\"{$c}\"", $columns));

            $valueRows = [];
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $valueSet = [];
                foreach ($columns as $col) {
                    $value = $rowArray[$col] ?? null;
                    if ($value === null) {
                        $valueSet[] = 'NULL';
                    } elseif (is_int($value) || is_float($value)) {
                        $valueSet[] = (string) $value;
                    } else {
                        $valueSet[] = $pdo->quote((string) $value);
                    }
                }
                $valueRows[] = '('.implode(', ', $valueSet).')';
            }

            fwrite($handle, "INSERT INTO \"{$table}\" ({$columnList}) VALUES\n");
            fwrite($handle, implode(",\n", $valueRows));
            fwrite($handle, ";\n");

            if (count($rows) < self::DUMP_BATCH_SIZE) {
                break;
            }
            $offset += self::DUMP_BATCH_SIZE;
        }
    }

    /**
     * Get list of tables to backup (filtered by prefix)
     *
     * @return string[]
     */
    private function getTablesForBackup(string $databaseName, string $prefix): array
    {
        if ($prefix === '') {
            $rows = DB::select(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?',
                [$databaseName, 'BASE TABLE'],
            );
        } else {
            $likePrefix = str_replace(['_', '%'], ['\\_', '\\%'], $prefix).'%';
            $rows = DB::select(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? AND TABLE_NAME LIKE ?',
                [$databaseName, 'BASE TABLE', $likePrefix],
            );
        }

        return array_map(fn ($row) => array_values((array) $row)[0], $rows);
    }

    /**
     * Dump a single table
     *
     * @param  resource  $handle
     */
    private function dumpTable($handle, string $table): void
    {
        fwrite($handle, "\n-- Table: {$table}\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createRows = DB::select("SHOW CREATE TABLE `{$table}`");
        $createSql = (array) $createRows[0];
        $createStatement = $createSql['Create Table'] ?? array_values($createSql)[1] ?? null;
        if (! $createStatement) {
            throw new \RuntimeException("Failed to retrieve CREATE statement for table: {$table}");
        }
        fwrite($handle, $createStatement.";\n\n");

        $offset = 0;
        $pdo = DB::getPdo();

        while (true) {
            $rows = DB::select("SELECT * FROM `{$table}` LIMIT ? OFFSET ?", [self::DUMP_BATCH_SIZE, $offset]);
            if (empty($rows)) {
                break;
            }

            $columns = array_keys((array) $rows[0]);
            $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

            $valueRows = [];
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $valueSet = [];
                foreach ($columns as $col) {
                    $value = $rowArray[$col] ?? null;
                    if ($value === null) {
                        $valueSet[] = 'NULL';
                    } elseif (is_int($value) || is_float($value)) {
                        $valueSet[] = (string) $value;
                    } else {
                        $valueSet[] = $pdo->quote((string) $value);
                    }
                }
                $valueRows[] = '('.implode(', ', $valueSet).')';
            }

            fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n");
            fwrite($handle, implode(",\n", $valueRows));
            fwrite($handle, ";\n");

            if (count($rows) < self::DUMP_BATCH_SIZE) {
                break;
            }
            $offset += self::DUMP_BATCH_SIZE;
        }
    }

    /**
     * Add directory to ZIP
     *
     * @param  string[]  $excludeDirs  Directory names to exclude directly under source
     * @param  string[]  $excludeDirNames  Directory names to exclude at any depth
     */
    private function addDirectoryToZip(
        \ZipArchive $zip,
        string $sourceDir,
        string $namespace,
        array $excludeDirs = [],
        array $excludeDirNames = [],
    ): void {
        if (! is_dir($sourceDir)) {
            return;
        }

        $sourceDir = rtrim($sourceDir, '/\\');
        $sourceLen = strlen($sourceDir) + 1;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                function ($current, $key, $iterator) use ($sourceDir, $excludeDirs, $excludeDirNames) {
                    if ($current->isDir()) {
                        // Exclude matching directory names at any depth
                        if (in_array($current->getFilename(), $excludeDirNames, true)) {
                            return false;
                        }

                        // Exclude directories directly under source that match excludeDirs
                        $relativePath = substr($current->getPathname(), strlen($sourceDir) + 1);
                        $topLevelName = explode(DIRECTORY_SEPARATOR, $relativePath)[0] ?? '';
                        if (in_array($topLevelName, $excludeDirs, true)) {
                            return false;
                        }
                    }

                    return true;
                },
            ),
            // SELF_FIRST so directories are visited too — LEAVES_ONLY
            // never yields empty directories (hasChildren() is true
            // for any directory), which made backups silently drop
            // empty scaffold dirs such as custom/tests/Unit.
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            // Symlinks are dropped: ZipArchive would store the link
            // target's content as a regular file, and restore would
            // then materialize it inside the tree — silently
            // duplicating data the link merely pointed at.
            if ($file->isLink()) {
                continue;
            }

            $filePath = $file->getPathname();
            $relativePath = substr($filePath, $sourceLen);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            // Record empty directories as explicit entries so restore
            // can recreate them (restore wipes the target before
            // extracting, so anything absent from the archive is lost).
            if ($file->isDir()) {
                if (! (new \FilesystemIterator($filePath))->valid()) {
                    $zip->addEmptyDir($namespace.'/'.$relativePath);
                }

                continue;
            }

            if (! $file->isFile()) {
                continue;
            }

            $zip->addFile($filePath, $namespace.'/'.$relativePath);
        }
    }

    /**
     * Create BackupRecord
     *
     * @param  string[]  $targets
     * @param  array<string,mixed>  $options
     */
    private function createBackupRecord(
        array $targets,
        string $type,
        string $filePath,
        string $fileName,
        int $fileSize,
        string $hash,
        float $durationSeconds,
        array $options,
    ): BackupRecord {
        $retentionDays = $options['retention_days'] ?? null;
        $retentionUntil = $retentionDays !== null
            ? now()->addDays((int) $retentionDays)
            : null;

        return BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => $type,
            'targets' => $targets,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'is_encrypted' => false,
            'hash' => $hash,
            'hash_algorithm' => 'sha256',
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'retention_until' => $retentionUntil,
            'status' => BackupRecord::STATUS_COMPLETED,
            'metadata' => [
                'duration_seconds' => $durationSeconds,
            ],
        ]);
    }

    /**
     * Create temporary working directory
     */
    private function createTempDir(): string
    {
        $base = storage_path('app/private/.backup-tmp');
        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }
        $tempDir = $base.'/'.uniqid('backup_', true);
        mkdir($tempDir, 0755, true);

        return $tempDir;
    }

    /**
     * Ensure backup destination directory exists
     */
    private function ensureBackupDirectory(): string
    {
        $dir = storage_path('app/private/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Generate backup file name
     */
    private function generateFileName(string $type): string
    {
        return sprintf(
            'dixlase-backup-%s-%s.zip',
            now()->format('Ymd-His'),
            $type,
        );
    }

    /**
     * Recursively delete temporary directory
     */
    private function cleanupTempDir(string $tempDir): void
    {
        if (! is_dir($tempDir)) {
            return;
        }

        $items = new \DirectoryIterator($tempDir);
        foreach ($items as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->cleanupTempDir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($tempDir);
    }
}
