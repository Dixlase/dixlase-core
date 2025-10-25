<?php

namespace App\Console\Commands;

use App\Models\MembersTrustedDevice;
use Illuminate\Console\Command;

class CleanupTrustedDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-trusted-devices {--days=90 : Number of days to keep trusted device records} {--all : Delete all trusted device records} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old trusted device records';

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
                $this->info(__('admin.cleanup_trusted_devices.days_zero_warning'));
            }
            
            // --forceオプションがない場合のみ確認を求める
            // Webインターフェースからの実行時はSTDINが利用できないため、forceフラグが必須
            if (!$this->option('force')) {
                // コマンドラインから実行されている場合のみ確認プロンプトを表示
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_trusted_devices.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_trusted_devices.operation_cancelled'));
                        return 0;
                    }
                } else {
                    // Webインターフェースからの実行時は--forceフラグが必要
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_trusted_devices.deleting_all'));
            $deletedCount = MembersTrustedDevice::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_trusted_devices.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_trusted_devices.no_records_found'));
                $this->line("DELETED_COUNT: 0");
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_trusted_devices.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_trusted_devices.cleaning_up', ['days' => $days]));

        $deletedCount = MembersTrustedDevice::where('last_used_at', '<', now()->subDays($days))
            ->orWhere(function ($query) use ($days) {
                $query->whereNull('last_used_at')
                      ->where('created_at', '<', now()->subDays($days));
            })
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_trusted_devices.deleted_old_success', ['count' => $deletedCount]));
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_trusted_devices.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
