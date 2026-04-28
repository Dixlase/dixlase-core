<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

class CspStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSPの現在の状態を表示します';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // CSP設定を取得
            $enabled = SecuritySetting::get('csp_enabled', 0);
            $modeValue = SecuritySetting::get('csp_mode', 0);
            $adminModeValue = SecuritySetting::get('csp_admin_mode');
            $excludeDevTools = SecuritySetting::get('csp_exclude_dev_tools', 1);
            
            // モードを文字列に変換
            $mode = match((int)$modeValue) {
                0 => 'development (開発モード)',
                1 => 'standard (標準モード)',
                2 => 'strict (厳格モード)',
                default => 'unknown',
            };
            
            // 管理画面モードを文字列に変換
            $adminMode = 'フロントと同じ';
            if ($adminModeValue !== null && $adminModeValue !== '') {
                $adminMode = match((int)$adminModeValue) {
                    0 => 'development (開発モード)',
                    1 => 'standard (標準モード)',
                    2 => 'strict (厳格モード)',
                    default => 'unknown',
                };
            }
            
            // 状態を表示
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📊 CSP現在の状態');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');
            
            // CSP有効/無効
            if ($enabled) {
                $this->info('✅ CSP: 有効');
            } else {
                $this->warn('⚠️  CSP: 無効');
            }
            
            // モード
            $this->info("📋 フロントエンド: {$mode}");
            $this->info("🔐 管理画面: {$adminMode}");
            
            // 開発ツール除外
            if ($excludeDevTools) {
                $this->info('🔧 開発ツール除外: 有効');
            } else {
                $this->line('🔧 開発ツール除外: 無効');
            }
            
            $this->line('');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            // コマンドヘルプ
            $this->line('');
            $this->comment('💡 利用可能なコマンド:');
            $this->line('  dixlase:csp:disable                - CSPを無効化');
            $this->line('  dixlase:csp:enable --mode=MODE     - CSPを有効化');
            $this->line('  dixlase:csp:set MODE               - フロントモードを変更');
            $this->line('  dixlase:csp:set-admin MODE         - 管理画面モードを変更');
            $this->line('  dixlase:csp:status                 - 現在の状態を表示');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ CSP状態の取得に失敗しました: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
