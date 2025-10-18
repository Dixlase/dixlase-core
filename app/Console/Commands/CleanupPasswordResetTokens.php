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

class CleanupPasswordResetTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-password-reset-tokens {--days=30 : Number of days to keep password reset token records} {--all : Delete all password reset token records} {--force : Force deletion without confirmation}';

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
            
            // --forceオプションがない場合のみ確認を求める
            // Webインターフェースからの実行時はSTDINが利用できないため、forceフラグが必須
            if (!$this->option('force')) {
                // コマンドラインから実行されている場合のみ確認プロンプトを表示
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_password_reset_tokens.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_password_reset_tokens.operation_cancelled'));
                        return 0;
                    }
                } else {
                    // Webインターフェースからの実行時は--forceフラグが必要
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_password_reset_tokens.deleting_all'));
            $deletedCount = DB::table('members_password_reset_tokens')->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_password_reset_tokens.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_password_reset_tokens.no_records_found'));
                $this->line("DELETED_COUNT: 0");
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
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_password_reset_tokens.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
