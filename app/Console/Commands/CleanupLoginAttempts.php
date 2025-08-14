<?php

namespace App\Console\Commands;

use App\Models\MemberLoginAttempt;
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
                $this->info(__('admin.cleanup_login_attempts.days_zero_warning'));
            }
            
            if (!$this->confirm(__('admin.cleanup_login_attempts.confirm_delete_all'))) {
                $this->info(__('admin.cleanup_login_attempts.operation_cancelled'));
                return 0;
            }
            
            $this->info(__('admin.cleanup_login_attempts.deleting_all'));
            $deletedCount = MemberLoginAttempt::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_login_attempts.deleted_all_success', ['count' => $deletedCount]));
            } else {
                $this->info(__('admin.cleanup_login_attempts.no_records_found'));
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_login_attempts.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_login_attempts.cleaning_up', ['days' => $days]));

        $deletedCount = MemberLoginAttempt::cleanupOldAttempts($days);

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_login_attempts.deleted_old_success', ['count' => $deletedCount]));
        } else {
            $this->info(__('admin.cleanup_login_attempts.no_old_records_found'));
        }

        return 0;
    }
}
