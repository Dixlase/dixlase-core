<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPasswordResetTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-password-reset-tokens {--days=30 : Number of days to keep password reset token records} {--all : Delete all password reset token records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old password reset token records';

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
                $this->info(__('admin.cleanup_password_reset_tokens.days_zero_warning'));
            }
            
            if (!$this->confirm(__('admin.cleanup_password_reset_tokens.confirm_delete_all'))) {
                $this->info(__('admin.cleanup_password_reset_tokens.operation_cancelled'));
                return 0;
            }
            
            $this->info(__('admin.cleanup_password_reset_tokens.deleting_all'));
            $deletedCount = DB::table('members_password_reset_tokens')->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_password_reset_tokens.deleted_all_success', ['count' => $deletedCount]));
            } else {
                $this->info(__('admin.cleanup_password_reset_tokens.no_records_found'));
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_password_reset_tokens.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_password_reset_tokens.cleaning_up', ['days' => $days]));

        $deletedCount = DB::table('members_password_reset_tokens')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_password_reset_tokens.deleted_old_success', ['count' => $deletedCount]));
        } else {
            $this->info(__('admin.cleanup_password_reset_tokens.no_old_records_found'));
        }

        return 0;
    }
}
