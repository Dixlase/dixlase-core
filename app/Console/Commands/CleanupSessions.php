<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-sessions {--days=7 : Number of days to keep session records} {--all : Delete all session records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old session records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleteAll = $this->option('all');
        $days = (int) $this->option('days');
        
        // --allオプションで全レコード削除
        if ($deleteAll) {
            if (!$this->confirm(__('admin.cleanup_sessions.confirm_delete_all'))) {
                $this->info(__('admin.cleanup_sessions.operation_cancelled'));
                return 0;
            }
            
            $this->info(__('admin.cleanup_sessions.deleting_all'));
            $deletedCount = DB::table('sessions')->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_sessions.deleted_all_success', ['count' => $deletedCount]));
            } else {
                $this->info(__('admin.cleanup_sessions.no_records_found'));
            }
            
            return 0;
        }
        
        if ($days < 1) {
            $this->error(__('admin.cleanup_sessions.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_sessions.cleaning_up', ['days' => $days]));

        // Calculate the cutoff timestamp (days ago)
        $cutoffTime = now()->subDays($days)->timestamp;

        $deletedCount = DB::table('sessions')
            ->where('last_activity', '<', $cutoffTime)
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_sessions.deleted_old_success', ['count' => $deletedCount]));
        } else {
            $this->info(__('admin.cleanup_sessions.no_old_records_found'));
        }

        return 0;
    }
}
