<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckMaintenanceAutoRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:check-auto-release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-release maintenance mode if scheduled time has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // メンテナンスモード設定を取得
        $settings = DB::table('base_settings')
            ->whereIn('name', [
                'maintenance_mode',
                'maintenance_auto_release',
                'maintenance_release_at',
            ])
            ->pluck('value', 'name')
            ->toArray();

        $maintenanceMode = ($settings['maintenance_mode'] ?? '0') === '1';
        $autoRelease = ($settings['maintenance_auto_release'] ?? '0') === '1';
        $releaseAt = $settings['maintenance_release_at'] ?? null;

        // メンテナンスモードが無効、または自動解除が無効な場合は何もしない
        if (!$maintenanceMode || !$autoRelease || !$releaseAt) {
            return self::SUCCESS;
        }

        // 終了日時を過ぎているかチェック
        $releaseTime = \Carbon\Carbon::parse($releaseAt);
        if (now()->gte($releaseTime)) {
            // メンテナンスモードを解除
            DB::table('base_settings')
                ->where('name', 'maintenance_mode')
                ->update([
                    'value' => '0',
                    'updated_at' => now(),
                ]);

            // 自動解除設定もリセット
            DB::table('base_settings')
                ->whereIn('name', ['maintenance_auto_release', 'maintenance_start_at', 'maintenance_release_at'])
                ->update([
                    'value' => null,
                    'updated_at' => now(),
                ]);

            Log::info('Maintenance mode auto-released', [
                'release_at' => $releaseAt,
                'released_at' => now()->toDateTimeString(),
            ]);

            $this->info('Maintenance mode has been automatically released.');
        }

        return self::SUCCESS;
    }
}
