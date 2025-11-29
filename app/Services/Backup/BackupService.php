<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * Service for creating backups of files and database
 */
class BackupService
{
    protected ?Command $command = null;
    protected string $backupDir;
    protected string $timestamp;

    // Backup target constants
    public const TARGET_ALL = 'all';
    public const TARGET_CORE = 'core';
    public const TARGET_PLUGINS = 'plugins';
    public const TARGET_THEMES = 'themes';
    public const TARGET_CUSTOM = 'custom';
    public const TARGET_STORAGE_PUBLIC = 'storage_public';
    public const TARGET_STORAGE_PRIVATE = 'storage_private';
    public const TARGET_LOGS = 'logs';
    public const TARGET_DATABASE = 'database';

    public function __construct()
    {
        $this->backupDir = database_path('backups');
        $this->timestamp = date('Ymd_His');
        
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Set command for output
     */
    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    /**
     * Get backup directory
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Create file backup
     */
    public function backupFiles(array $targets = []): ?string
    {
        if (empty($targets)) {
            $targets = [self::TARGET_ALL];
        }

        $paths = $this->getPathsForTargets($targets);
        
        if (empty($paths)) {
            $this->error(__('command.backup.no_paths_to_backup'));
            return null;
        }

        $zipFileName = 'dixlase_files_' . $this->timestamp . '.zip';
        $zipFilePath = $this->backupDir . '/' . $zipFileName;

        $this->info(__('command.backup.creating_file_backup', ['file' => $zipFileName]));

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error(__('command.backup.failed_to_create_zip'));
            return null;
        }

        $basePath = base_path();
        $totalFiles = 0;

        foreach ($paths as $target => $relativePath) {
            $fullPath = $basePath . '/' . $relativePath;
            
            if (!File::exists($fullPath)) {
                $this->warn(__('command.backup.path_not_found', ['path' => $relativePath]));
                continue;
            }

            $this->info(__('command.backup.adding_target', ['target' => $target, 'path' => $relativePath]));

            if (File::isDirectory($fullPath)) {
                $files = $this->getFilesRecursively($fullPath);
                foreach ($files as $file) {
                    $localPath = str_replace($basePath . '/', '', $file);
                    $zip->addFile($file, $localPath);
                    $totalFiles++;
                }
            } else {
                $localPath = str_replace($basePath . '/', '', $fullPath);
                $zip->addFile($fullPath, $localPath);
                $totalFiles++;
            }
        }

        $zip->close();

        if ($totalFiles === 0) {
            File::delete($zipFilePath);
            $this->warn(__('command.backup.no_files_added'));
            return null;
        }

        $fileSize = $this->formatFileSize(File::size($zipFilePath));
        $this->info(__('command.backup.file_backup_completed', [
            'file' => $zipFileName,
            'count' => $totalFiles,
            'size' => $fileSize
        ]));

        return $zipFilePath;
    }

    /**
     * Create database backup
     */
    public function backupDatabase(array $tables = []): ?string
    {
        $dbConfig = $this->getDatabaseConfig();
        
        if (!$dbConfig) {
            $this->error(__('command.backup.database_config_not_found'));
            return null;
        }

        $sqlFileName = 'dixlase_db_' . $this->timestamp . '.sql';
        $sqlFilePath = $this->backupDir . '/' . $sqlFileName;

        $this->info(__('command.backup.creating_database_backup', ['file' => $sqlFileName]));

        $command = $this->buildMysqldumpCommand($dbConfig, $tables);
        $command .= ' > ' . escapeshellarg($sqlFilePath);

        $this->info(__('command.backup.running_mysqldump'));

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error(__('command.backup.mysqldump_failed', ['error' => implode("\n", $output)]));
            if (File::exists($sqlFilePath)) {
                File::delete($sqlFilePath);
            }
            return null;
        }

        $fileSize = $this->formatFileSize(File::size($sqlFilePath));
        $tableInfo = empty($tables) 
            ? __('command.backup.all_tables') 
            : implode(', ', $tables);

        $this->info(__('command.backup.database_backup_completed', [
            'file' => $sqlFileName,
            'tables' => $tableInfo,
            'size' => $fileSize
        ]));

        return $sqlFilePath;
    }

    /**
     * Get paths for specified targets
     */
    protected function getPathsForTargets(array $targets): array
    {
        $allPaths = [
            self::TARGET_CORE => [
                'app',
                'bootstrap',
                'config',
                'database/migrations',
                'database/seeders',
                'lang',
                'public',
                'resources',
                'routes',
                'stubs',
            ],
            self::TARGET_PLUGINS => ['plugins'],
            self::TARGET_THEMES => ['themes'],
            self::TARGET_CUSTOM => ['custom'],
            self::TARGET_STORAGE_PUBLIC => ['storage/app/public'],
            self::TARGET_STORAGE_PRIVATE => ['storage/app/private'],
            self::TARGET_LOGS => ['storage/logs'],
        ];

        $result = [];

        // If 'all' is specified, include all file targets
        if (in_array(self::TARGET_ALL, $targets)) {
            foreach ($allPaths as $target => $paths) {
                foreach ($paths as $path) {
                    $result[$target . ':' . $path] = $path;
                }
            }
            return $result;
        }

        // Add specific targets
        foreach ($targets as $target) {
            if (isset($allPaths[$target])) {
                foreach ($allPaths[$target] as $path) {
                    $result[$target . ':' . $path] = $path;
                }
            }
        }

        return $result;
    }

    /**
     * Get files recursively from directory
     */
    protected function getFilesRecursively(string $directory): array
    {
        $files = [];
        $excludePatterns = $this->getExcludePatterns();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPathname();
                
                // Check if file should be excluded
                $shouldExclude = false;
                foreach ($excludePatterns as $pattern) {
                    if (fnmatch($pattern, $filePath) || fnmatch($pattern, basename($filePath))) {
                        $shouldExclude = true;
                        break;
                    }
                }
                
                if (!$shouldExclude) {
                    $files[] = $filePath;
                }
            }
        }

        return $files;
    }

    /**
     * Get exclude patterns for backup
     */
    protected function getExcludePatterns(): array
    {
        return [
            '.git',
            '.git/*',
            '*.git*',
            'node_modules',
            'node_modules/*',
            'vendor',
            'vendor/*',
            '.env',
            '.env.*',
            '*.log',
            '.DS_Store',
            'Thumbs.db',
            '*.cache',
            '.phpunit.result.cache',
        ];
    }

    /**
     * Get database configuration
     */
    protected function getDatabaseConfig(): ?array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (!$config || $config['driver'] !== 'mysql') {
            return null;
        }

        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'name' => $config['database'],
            'user' => $config['username'],
            'password' => $config['password'] ?? '',
        ];
    }

    /**
     * Build mysqldump command
     */
    protected function buildMysqldumpCommand(array $dbConfig, array $tables = []): string
    {
        $parts = ['mysqldump'];
        
        $parts[] = '-h' . escapeshellarg($dbConfig['host']);
        $parts[] = '-u' . escapeshellarg($dbConfig['user']);
        
        if (!empty($dbConfig['password'])) {
            $parts[] = '-p' . escapeshellarg($dbConfig['password']);
        }
        
        if (!empty($dbConfig['port']) && $dbConfig['port'] !== 3306) {
            $parts[] = '-P' . escapeshellarg((string)$dbConfig['port']);
        }
        
        $parts[] = '--single-transaction';
        $parts[] = '--quick';
        $parts[] = '--lock-tables=false';
        $parts[] = '--routines';
        $parts[] = '--triggers';
        
        $parts[] = escapeshellarg($dbConfig['name']);
        
        // Specific tables
        foreach ($tables as $table) {
            $parts[] = escapeshellarg($table);
        }
        
        return implode(' ', $parts);
    }

    /**
     * Format file size
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [
            'files' => [],
            'database' => [],
        ];

        if (!File::exists($this->backupDir)) {
            return $backups;
        }

        $files = File::files($this->backupDir);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $size = $this->formatFileSize($file->getSize());
            $modified = date('Y-m-d H:i:s', $file->getMTime());

            $info = [
                'filename' => $filename,
                'path' => $file->getPathname(),
                'size' => $size,
                'modified' => $modified,
            ];

            if (str_ends_with($filename, '.zip')) {
                $backups['files'][] = $info;
            } elseif (str_ends_with($filename, '.sql')) {
                $backups['database'][] = $info;
            }
        }

        // Sort by modified date descending
        usort($backups['files'], fn($a, $b) => strcmp($b['modified'], $a['modified']));
        usort($backups['database'], fn($a, $b) => strcmp($b['modified'], $a['modified']));

        return $backups;
    }

    /**
     * Delete old backups
     */
    public function cleanupOldBackups(int $keepDays = 30): int
    {
        $deleted = 0;
        $cutoffTime = time() - ($keepDays * 24 * 60 * 60);

        if (!File::exists($this->backupDir)) {
            return $deleted;
        }

        $files = File::files($this->backupDir);

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $deleted++;
                $this->info(__('command.backup.deleted_old_backup', ['file' => $file->getFilename()]));
            }
        }

        return $deleted;
    }

    /**
     * Output info message
     */
    protected function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            Log::info("[Backup] {$message}");
        }
    }

    /**
     * Output warning message
     */
    protected function warn(string $message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        } else {
            Log::warning("[Backup] {$message}");
        }
    }

    /**
     * Output error message
     */
    protected function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        } else {
            Log::error("[Backup] {$message}");
        }
    }
}
