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
    protected $signature = 'admin:cleanup-cache {--expired-only : Delete only expired cache entries} {--all : Delete all cache entries}';

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
            if (!$this->confirm(__('admin.cleanup_cache.confirm_delete_all'))) {
                $this->info(__('admin.cleanup_cache.operation_cancelled'));
                return 0;
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
        
        // Default behavior: clean expired entries
        $this->info(__('admin.cleanup_cache.cleaning_expired'));
        
        $now = time();
        
        // Delete expired cache entries
        $expiredCacheDeleted = DB::table('cache')
            ->where('expiration', '<', $now)
            ->delete();
            
        // Delete expired cache locks
        $expiredLocksDeleted = DB::table('cache_locks')
            ->where('expiration', '<', $now)
            ->delete();

        if ($expiredCacheDeleted > 0 || $expiredLocksDeleted > 0) {
            $this->info(__('admin.cleanup_cache.deleted_expired_success', [
                'cache_count' => $expiredCacheDeleted,
                'locks_count' => $expiredLocksDeleted
            ]));
        } else {
            $this->info(__('admin.cleanup_cache.no_expired_records_found'));
        }

        return 0;
    }
}
