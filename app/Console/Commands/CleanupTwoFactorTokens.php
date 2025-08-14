<?php

namespace App\Console\Commands;

use App\Models\MembersTwoFactorToken;
use Illuminate\Console\Command;

class CleanupTwoFactorTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-two-factor-tokens {--days=7 : Number of days to keep two-factor token records} {--all : Delete all two-factor token records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old two-factor authentication token records';

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
                $this->info(__('admin.cleanup_two_factor_tokens.days_zero_warning'));
            }
            
            if (!$this->confirm(__('admin.cleanup_two_factor_tokens.confirm_delete_all'))) {
                $this->info(__('admin.cleanup_two_factor_tokens.operation_cancelled'));
                return 0;
            }
            
            $this->info(__('admin.cleanup_two_factor_tokens.deleting_all'));
            $deletedCount = MembersTwoFactorToken::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_two_factor_tokens.deleted_all_success', ['count' => $deletedCount]));
            } else {
                $this->info(__('admin.cleanup_two_factor_tokens.no_records_found'));
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_two_factor_tokens.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_two_factor_tokens.cleaning_up', ['days' => $days]));

        // 期限切れのトークンと古いトークンを削除
        $deletedCount = MembersTwoFactorToken::where('expires_at', '<', now())
            ->orWhere('created_at', '<', now()->subDays($days))
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_two_factor_tokens.deleted_old_success', ['count' => $deletedCount]));
        } else {
            $this->info(__('admin.cleanup_two_factor_tokens.no_old_records_found'));
        }

        return 0;
    }
}
