<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

/**
 * Command to list available backups
 */
class BackupList extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:list
        {--files : Show only file backups}
        {--database : Show only database backups}';

    /**
     * The console command description.
     */
    protected $description = 'List all available backups';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $backups = $backupService->listBackups();
        $showFiles = $this->option('files') || !$this->option('database');
        $showDatabase = $this->option('database') || !$this->option('files');

        $this->info(__('command.backup.list.title'));
        $this->line(str_repeat('=', 60));
        $this->newLine();

        $hasBackups = false;

        // File backups
        if ($showFiles) {
            $this->info(__('command.backup.list.file_backups'));
            $this->line(str_repeat('-', 40));

            if (empty($backups['files'])) {
                $this->line(__('command.backup.list.no_file_backups'));
            } else {
                $hasBackups = true;
                $this->table(
                    [
                        __('command.backup.list.filename'),
                        __('command.backup.list.size'),
                        __('command.backup.list.date'),
                    ],
                    array_map(fn($b) => [$b['filename'], $b['size'], $b['modified']], $backups['files'])
                );
            }
            $this->newLine();
        }

        // Database backups
        if ($showDatabase) {
            $this->info(__('command.backup.list.database_backups'));
            $this->line(str_repeat('-', 40));

            if (empty($backups['database'])) {
                $this->line(__('command.backup.list.no_database_backups'));
            } else {
                $hasBackups = true;
                $this->table(
                    [
                        __('command.backup.list.filename'),
                        __('command.backup.list.size'),
                        __('command.backup.list.date'),
                    ],
                    array_map(fn($b) => [$b['filename'], $b['size'], $b['modified']], $backups['database'])
                );
            }
            $this->newLine();
        }

        // Show backup directory
        $this->info(__('command.backup.list.backup_directory', ['path' => $backupService->getBackupDir()]));

        return Command::SUCCESS;
    }
}
