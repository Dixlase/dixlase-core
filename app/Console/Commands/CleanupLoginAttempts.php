<?php

namespace App\Console\Commands;

use App\Models\AdminLoginAttempt;
use Illuminate\Console\Command;

class CleanupLoginAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-login-attempts {--days=30 : Number of days to keep login attempt records} {--all : Delete all login attempt records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old admin login attempt records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleteAll = $this->option('all');
        $days = (int) $this->option('days');
        
        // --allオプションまたは--days=0で全レコード削除
        if ($deleteAll || $days === 0) {
            if (!$deleteAll && $days === 0) {
                $this->info('Days set to 0 - this will delete ALL login attempt records.');
            }
            
            if (!$this->confirm('Are you sure you want to delete ALL login attempt records? This action cannot be undone.')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            $this->info('Deleting all login attempt records...');
            $deletedCount = AdminLoginAttempt::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info("Successfully deleted all {$deletedCount} login attempt records.");
            } else {
                $this->info('No login attempt records found to delete.');
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error('Days must be a positive integer, or use --all to delete all records.');
            return 1;
        }

        $this->info("Cleaning up login attempts older than {$days} days...");

        $deletedCount = AdminLoginAttempt::cleanupOldAttempts($days);

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old login attempt records.");
        } else {
            $this->info('No old login attempt records found to delete.');
        }

        return 0;
    }
}
