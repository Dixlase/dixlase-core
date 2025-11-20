<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use App\Models\Member2faAttempt;
use Illuminate\Console\Command;

class CleanupTwoFactorAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:admin:cleanup-two-factor-attempts {--days=30 : Number of days to keep 2FA attempt records} {--all : Delete all 2FA attempt records} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old two-factor authentication attempt records';

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
                $this->info(__('admin.cleanup_two_factor_attempts.days_zero_warning'));
            }
            
            // --forceオプションがない場合のみ確認を求める
            if (!$this->option('force')) {
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_two_factor_attempts.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_two_factor_attempts.operation_cancelled'));
                        return 0;
                    }
                } else {
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_two_factor_attempts.deleting_all'));
            $deletedCount = Member2faAttempt::query()->delete();
            
            if ($deletedCount > 0) {
                $this->info(__('admin.cleanup_two_factor_attempts.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_two_factor_attempts.no_records_found'));
                $this->line("DELETED_COUNT: 0");
            }
            
            return 0;
        }
        
        if ($days < 0) {
            $this->error(__('admin.cleanup_two_factor_attempts.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_two_factor_attempts.cleaning_up', ['days' => $days]));

        // 古い試行履歴を削除
        $deletedCount = Member2faAttempt::where('created_at', '<', now()->subDays($days))
            ->delete();

        if ($deletedCount > 0) {
            $this->info(__('admin.cleanup_two_factor_attempts.deleted_old_success', ['count' => $deletedCount]));
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_two_factor_attempts.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
