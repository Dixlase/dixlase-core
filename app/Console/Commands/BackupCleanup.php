<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

/**
 * Command to cleanup old backups
 */
class BackupCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:cleanup
        {--days=30 : Delete backups older than this many days}
        {--all : Delete all backups}
        {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Delete old backup files';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $backupService->setCommand($this);

        $days = (int) $this->option('days');
        $deleteAll = $this->option('all');
        $force = $this->option('force');

        if ($deleteAll) {
            $days = 0;
        }

        if ($days < 0) {
            $this->error(__('command.backup.cleanup.invalid_days'));
            return Command::FAILURE;
        }

        // Confirmation
        if ($deleteAll) {
            $message = __('command.backup.cleanup.confirm_delete_all');
        } else {
            $message = __('command.backup.cleanup.confirm_delete_old', ['days' => $days]);
        }

        if (!$force && !$this->confirm($message)) {
            $this->info(__('command.backup.cleanup.cancelled'));
            return Command::SUCCESS;
        }

        $this->info(__('command.backup.cleanup.starting'));

        $deleted = $backupService->cleanupOldBackups($days);

        if ($deleted === 0) {
            $this->info(__('command.backup.cleanup.no_backups_deleted'));
        } else {
            $this->info(__('command.backup.cleanup.deleted_count', ['count' => $deleted]));
        }

        return Command::SUCCESS;
    }
}
