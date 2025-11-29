<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

/**
 * Command to create backups of files and/or database
 */
class BackupCreate extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:create
        {--all : Backup everything (all files and database)}
        {--core : Backup core Dixlase files}
        {--plugins : Backup plugins directory}
        {--themes : Backup themes directory}
        {--custom : Backup custom directory}
        {--storage-public : Backup storage/app/public directory}
        {--storage-private : Backup storage/app/private directory}
        {--logs : Backup log files}
        {--database : Backup database}
        {--tables=* : Specific database tables to backup (can be specified multiple times)}
        {--files-only : Only backup files (skip database even with --all)}
        {--db-only : Only backup database (skip files even with --all)}';

    /**
     * The console command description.
     */
    protected $description = 'Create a backup of Dixlase files and/or database';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $backupService->setCommand($this);

        $this->info(__('command.backup.starting'));
        $this->newLine();

        $fileTargets = $this->getFileTargets();
        $tables = $this->option('tables') ?: [];
        $filesOnly = $this->option('files-only');
        $dbOnly = $this->option('db-only');
        $backupDatabase = $this->option('database') || $this->option('all');

        // Validate options
        if ($filesOnly && $dbOnly) {
            $this->error(__('command.backup.cannot_use_both_only'));
            return Command::FAILURE;
        }

        if (empty($fileTargets) && !$backupDatabase && empty($tables)) {
            $this->error(__('command.backup.no_targets'));
            $this->info(__('command.backup.use_options'));
            return Command::FAILURE;
        }

        $results = [];

        // Backup files
        if (!$dbOnly && !empty($fileTargets)) {
            $this->info(__('command.backup.section_files'));
            $this->line(str_repeat('-', 40));
            
            $filePath = $backupService->backupFiles($fileTargets);
            if ($filePath) {
                $results['files'] = $filePath;
            }
            $this->newLine();
        }

        // Backup database
        if (!$filesOnly && ($backupDatabase || !empty($tables))) {
            $this->info(__('command.backup.section_database'));
            $this->line(str_repeat('-', 40));
            
            $dbPath = $backupService->backupDatabase($tables);
            if ($dbPath) {
                $results['database'] = $dbPath;
            }
            $this->newLine();
        }

        // Summary
        $this->info(__('command.backup.summary'));
        $this->line(str_repeat('=', 40));

        if (empty($results)) {
            $this->warn(__('command.backup.no_backups_created'));
            return Command::FAILURE;
        }

        if (isset($results['files'])) {
            $this->info(__('command.backup.files_saved', ['path' => $results['files']]));
        }
        if (isset($results['database'])) {
            $this->info(__('command.backup.database_saved', ['path' => $results['database']]));
        }

        $this->newLine();
        $this->info(__('command.backup.completed'));

        return Command::SUCCESS;
    }

    /**
     * Get file targets based on options
     */
    protected function getFileTargets(): array
    {
        $targets = [];

        if ($this->option('all') && !$this->option('db-only')) {
            return [BackupService::TARGET_ALL];
        }

        if ($this->option('core')) {
            $targets[] = BackupService::TARGET_CORE;
        }
        if ($this->option('plugins')) {
            $targets[] = BackupService::TARGET_PLUGINS;
        }
        if ($this->option('themes')) {
            $targets[] = BackupService::TARGET_THEMES;
        }
        if ($this->option('custom')) {
            $targets[] = BackupService::TARGET_CUSTOM;
        }
        if ($this->option('storage-public')) {
            $targets[] = BackupService::TARGET_STORAGE_PUBLIC;
        }
        if ($this->option('storage-private')) {
            $targets[] = BackupService::TARGET_STORAGE_PRIVATE;
        }
        if ($this->option('logs')) {
            $targets[] = BackupService::TARGET_LOGS;
        }

        return $targets;
    }
}
