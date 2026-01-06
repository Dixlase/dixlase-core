<?php

namespace App\Console\Commands;

use App\Models\MemberTwoFaToken;
use Illuminate\Console\Command;

class CleanupTwoFaTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:admin:cleanup-two-fa-tokens {--days=7 : Number of days to keep two-factor token records} {--all : Delete all two-factor token records} {--force : Force deletion without confirmation}';

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
                $this->info(__('admin.cleanup_two_fa_tokens.days_zero_warning'));
            }
            
            // --forceオプションがない場合のみ確認を求める
            // Webインターフェースからの実行時はSTDINが利用できないため、forceフラグが必須
            if (!$this->option('force')) {
                // コマンドラインから実行されている場合のみ確認プロンプトを表示
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_two_fa_tokens.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_two_fa_tokens.operation_cancelled'));
                        return 0;
                    }
                } else {
                    // Webインターフェースからの実行時は--forceフラグが必要
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_two_fa_tokens.deleting_all'));
            $deletedCount = MemberTwoFaToken::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_two_fa_tokens.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_two_fa_tokens.no_records_found'));
                $this->line("DELETED_COUNT: 0");
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_two_fa_tokens.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_two_fa_tokens.cleaning_up', ['days' => $days]));

        // 期限切れのトークンと古いトークンを削除
        $deletedCount = MemberTwoFaToken::where('expires_at', '<', now())
            ->orWhere('created_at', '<', now()->subDays($days))
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_two_fa_tokens.deleted_old_success', ['count' => $deletedCount]));
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_two_fa_tokens.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
