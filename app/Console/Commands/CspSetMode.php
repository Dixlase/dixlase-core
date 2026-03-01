<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CspSetMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:set {mode : CSPモード (development/standard/strict)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSPモードを変更します';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $mode = $this->argument('mode');
            
            // モードの検証
            $validModes = ['development', 'standard', 'strict'];
            if (!in_array($mode, $validModes)) {
                $this->error("❌ 無効なモード: {$mode}");
                $this->info("有効なモード: " . implode(', ', $validModes));
                return Command::FAILURE;
            }
            
            // モードを数値に変換
            $modeValue = match($mode) {
                'development' => 0,
                'standard' => 1,
                'strict' => 2,
            };
            
            // CSPモードを変更
            SecuritySetting::set('csp_mode', $modeValue);
            
            // キャッシュをクリア
            Cache::flush();
            
            $this->info("✅ CSPモードを {$mode} に変更しました");
            $this->info('📝 ログ: CSPモードが変更されました - ' . now());
            
            // ログに記録
            \Log::channel('stack')->warning('CSPモード変更コマンド実行', [
                'command' => 'dixlase:csp:set',
                'mode' => $mode,
                'user' => 'CLI',
                'timestamp' => now(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ CSPモードの変更に失敗しました: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
