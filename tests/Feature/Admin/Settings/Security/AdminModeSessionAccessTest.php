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

namespace Tests\Feature\Admin\Settings\Security;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Models\SecuritySetting;
use App\Services\AdminModeAutoConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * かんたんモード時のセッション設定アクセス制御テスト
 */
class AdminModeSessionAccessTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    // ========================================
    // かんたんモード時: セッション設定はHiddenのためアクセス不可
    // ========================================

    public function test_simple_mode_redirects_session_settings_get(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.session'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('warning');
    }

    public function test_simple_mode_blocks_session_settings_post(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.settings.security.session.update'), [
                '_token' => csrf_token(),
                'session_lifetime' => 60,
                'session_encrypt' => false,
            ]);

        // HiddenのためGETと同じくリダイレクトされる（check.menu.accessで弾かれる）
        $response->assertRedirect(route('admin.dashboard'));
    }

    // ========================================
    // 詳細モード時: セッション設定にアクセスできる
    // ========================================

    public function test_advanced_mode_allows_session_settings_get(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.session'));

        $response->assertStatus(200);
    }

    public function test_advanced_mode_allows_session_settings_post(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.settings.security.session.update'), [
                'session_lifetime' => 60,
                'session_encrypt' => false,
            ]);

        $response->assertRedirect(route('admin.settings.security.session'));
        $response->assertSessionHas('success');
    }

    // ========================================
    // 自動設定: かんたんモードへ切替時にセッション設定が自動適用される
    // ========================================

    public function test_switching_to_simple_mode_applies_session_defaults(): void
    {
        // 事前に詳細モードでセッション設定を変更
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        ConfigHelper::setSessionLifetime(60);
        ConfigHelper::setSessionEncrypt(true);

        // かんたんモードに切り替え
        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.settings.base.mode.update'), [
                'admin_mode' => AdminMode::Simple->value,
            ]);

        $response->assertRedirect(route('admin.settings.base.mode'));

        // セッション設定がデフォルト値に戻っていることを確認
        $this->assertEquals(120, ConfigHelper::getSessionLifetime());
        $this->assertFalse(ConfigHelper::getSessionEncrypt());
    }

    public function test_switching_to_advanced_mode_does_not_change_settings(): void
    {
        // 事前にかんたんモードでセッション設定がデフォルト値
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $currentLifetime = ConfigHelper::getSessionLifetime();

        // 詳細モードに切り替え
        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.settings.base.mode.update'), [
                'admin_mode' => AdminMode::Advanced->value,
            ]);

        $response->assertRedirect(route('admin.settings.base.mode'));

        // セッション設定は変更されていない
        $this->assertEquals($currentLifetime, ConfigHelper::getSessionLifetime());
    }

    // ========================================
    // AdminModeAutoConfigService 単体テスト
    // ========================================

    public function test_auto_config_service_applies_session_defaults(): void
    {
        // カスタム値を設定
        ConfigHelper::setSessionLifetime(30);
        ConfigHelper::setSessionEncrypt(true);

        // 自動設定を適用
        $service = new AdminModeAutoConfigService();
        $service->applySessionDefaults();

        // デフォルト値に戻っていることを確認
        $this->assertEquals(120, ConfigHelper::getSessionLifetime());
        $this->assertFalse(ConfigHelper::getSessionEncrypt());
    }

    public function test_auto_config_service_apply_all_returns_results(): void
    {
        $service = new AdminModeAutoConfigService();
        $results = $service->applyAll();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('settings.security.session', $results);
        $this->assertTrue($results['settings.security.session']);
    }

    // ========================================
    // サイドバー表示: かんたんモードでセッション設定が非表示
    // ========================================

    public function test_sidebar_hides_session_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        // ダッシュボードにアクセスしてサイドバーを確認
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // セッション設定へのリンクが含まれていないことを確認
        $response->assertDontSee(route('admin.settings.security.session'));
    }

    public function test_sidebar_shows_session_in_advanced_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // セッション設定へのリンクが含まれていることを確認
        $response->assertSee(route('admin.settings.security.session'));
    }
}
