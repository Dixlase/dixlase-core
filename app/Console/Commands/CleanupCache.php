<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-cache {--expired-only : Delete only expired cache entries} {--all : Delete all cache entries} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up cache and cache locks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleteAll = $this->option('all');
        $expiredOnly = $this->option('expired-only');
        
        if ($deleteAll) {
            // --forceオプションがない場合のみ確認を求める
            // Webインターフェースからの実行時はSTDINが利用できないため、forceフラグが必須
            if (!$this->option('force')) {
                // コマンドラインから実行されている場合のみ確認プロンプトを表示
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_cache.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_cache.operation_cancelled'));
                        return 0;
                    }
                } else {
                    // Webインターフェースからの実行時は--forceフラグが必要
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_cache.deleting_all'));
            
            // Delete all cache entries
            $cacheDeleted = DB::table('cache')->delete();
            
            // Delete all cache locks
            $locksDeleted = DB::table('cache_locks')->delete();
            
            if ($cacheDeleted > 0 || $locksDeleted > 0) {
                $this->info(__('admin.cleanup_cache.deleted_all_success', [
                    'cache_count' => $cacheDeleted,
                    'locks_count' => $locksDeleted
                ]));
            } else {
                $this->info(__('admin.cleanup_cache.no_records_found'));
            }
            
            return 0;
        }
        
        // Default behavior: clean all cache entries
        $this->info(__('admin.cleanup_cache.cleaning_all'));
        
        // Delete all cache entries
        $expiredCacheDeleted = DB::table('cache')->delete();
            
        // Delete all cache locks
        $expiredLocksDeleted = DB::table('cache_locks')->delete();

        $totalDeleted = $expiredCacheDeleted + $expiredLocksDeleted;
        
        if ($totalDeleted > 0) {
            $this->info(__('admin.cleanup_cache.deleted_expired_success', [
                'cache_count' => $expiredCacheDeleted,
                'locks_count' => $expiredLocksDeleted
            ]));
            $this->line("DELETED_COUNT: {$totalDeleted}");
        } else {
            $this->info(__('admin.cleanup_cache.no_expired_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
