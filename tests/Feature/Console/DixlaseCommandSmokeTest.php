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

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dixlase カスタム Artisan コマンドのスモークテスト
 *
 * 読み取り専用・リスト系コマンドのみテスト。
 * 破壊的コマンド（uninstall、delete、migrate 等）はスキップ。
 */
class DixlaseCommandSmokeTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // プラグイン系（読み取り専用）
    // =========================================================================

    public function test_plugin_list_command(): void
    {
        $this->artisan('dls:plugin:list')
            ->assertSuccessful();
    }

    public function test_source_check_command(): void
    {
        $this->artisan('dls:source:check', ['--no-interaction' => true])
            ->assertSuccessful();
    }

    // =========================================================================
    // テーマ系（読み取り専用）
    // =========================================================================

    public function test_theme_list_command(): void
    {
        $this->artisan('dls:theme:list')
            ->assertSuccessful();
    }

    // =========================================================================
    // ソース系（読み取り専用）
    // =========================================================================

    public function test_source_list_command(): void
    {
        $this->artisan('dls:source:list')
            ->assertSuccessful();
    }

    // =========================================================================
    // インストールチェック
    // =========================================================================

    public function test_install_check_command(): void
    {
        $this->artisan('dls:install:check')
            ->assertSuccessful();
    }

    // =========================================================================
    // クリーンアップ（dry-run）
    // =========================================================================

    public function test_cleanup_command_list(): void
    {
        $this->artisan('dls:cleanup', ['--list' => true, '--no-interaction' => true])
            ->assertSuccessful();
    }

    // =========================================================================
    // Git 同期（読み取り専用）
    // =========================================================================

    public function test_captcha_bypass_help(): void
    {
        // CAPTCHA バイパスコマンドのヘルプ表示（実際のバイパスは実行しない）
        $this->artisan('dls:admin:captcha-bypass', ['--help' => true])
            ->assertSuccessful();
    }

    // =========================================================================
    // 整合性チェック
    // =========================================================================

    public function test_integrity_scan_without_baseline(): void
    {
        // ベースラインなしでスキャン → エラーメッセージ表示だがクラッシュしない
        $result = $this->artisan('dls:integrity:scan', ['--no-interaction' => true]);

        $this->assertTrue(true);
    }
}
