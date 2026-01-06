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

use App\Models\MemberTwoFaPasskey;
use Illuminate\Console\Command;

class CleanupPasskeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:admin:cleanup-passkeys {--days=90 : Number of days to keep deleted passkey records} {--all : Delete all passkey records} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old or deleted passkey records';

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
                $this->info(__('admin.cleanup_passkeys.days_zero_warning'));
            }
            
            // --forceオプションがない場合のみ確認を求める
            if (!$this->option('force')) {
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin.cleanup_passkeys.confirm_delete_all'))) {
                        $this->info(__('admin.cleanup_passkeys.operation_cancelled'));
                        return 0;
                    }
                } else {
                    $this->error('--force flag is required when running from web interface');
                    return 1;
                }
            }
            
            $this->info(__('admin.cleanup_passkeys.deleting_all'));
            
            // ソフトデリート済みのレコード数を取得してから削除
            $deletedCount = MemberTwoFaPasskey::onlyTrashed()->count();
            if ($deletedCount > 0) {
                MemberTwoFaPasskey::onlyTrashed()->forceDelete();
                $this->info(__('admin.cleanup_passkeys.deleted_all_success', ['count' => $deletedCount]));
                $this->line("DELETED_COUNT: {$deletedCount}");
            } else {
                $this->info(__('admin.cleanup_passkeys.no_records_found'));
                $this->line("DELETED_COUNT: 0");
            }
            
            return 0;
        }
        
        if ($days < 0) {
            $this->error(__('admin.cleanup_passkeys.invalid_days'));
            return 1;
        }

        $this->info(__('admin.cleanup_passkeys.cleaning_up', ['days' => $days]));

        // ソフトデリート済みで古いpasskeyを完全削除
        $query = MemberTwoFaPasskey::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days));
        
        $deletedCount = $query->count();
        if ($deletedCount > 0) {
            $query->forceDelete();
            $this->info(__('admin.cleanup_passkeys.deleted_old_success', ['count' => $deletedCount]));
            $this->line("DELETED_COUNT: {$deletedCount}");
        } else {
            $this->info(__('admin.cleanup_passkeys.no_old_records_found'));
            $this->line("DELETED_COUNT: 0");
        }

        return 0;
    }
}
