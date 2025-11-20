<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
    protected $signature = 'dls:admin:cleanup-sessions {--days=7 : Number of days to keep session records} {--all : Delete all session records} {--force : Force deletion without confirmation}';

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
            // --forceオプションがない場合のみ確認を求める
            // Webインターフェースからの実行時はSTDINが利用できないため、forceフラグが必須
            if (!$this->option('force')) {
                // コマンドラインから実行されている場合のみ確認プロンプトを表示
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_sessions.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_sessions.operation_cancelled'));
                        return 0;
                    }
                } else {
                    // Webインターフェースからの実行時は--forceフラグが必要
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_sessions.deleting_all'));
            $deletedCount = DB::table('sessions')->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_sessions.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_sessions.no_records_found'));
                $this->line("DELETED_COUNT: 0");
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
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_sessions.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
