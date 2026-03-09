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

namespace Tests\Feature\Admin\Settings;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * かんたんモード時のビュー制御テスト（バナー表示・フォーム無効化）
 */
class AdminModeViewControlsTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        BaseSetting::setValue('site_name', 'Test Site');

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

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // ========================================
    // Partial ページ: パーシャル通知表示
    // ========================================

    public function test_login_page_shows_partial_notice_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        $response->assertSee(__('components/admin/mode-partial-notice.message'));
    }

    public function test_login_page_no_partial_notice_in_advanced_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        $response->assertDontSee(__('components/admin/mode-partial-notice.message'));
    }

    public function test_captcha_page_no_partial_notice_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.captcha'));

        $response->assertStatus(200);
        // CAPTCHAはFullなのでPartial通知は表示しない
        $response->assertDontSee(__('components/admin/mode-partial-notice.message'));
    }

    public function test_two_fa_page_shows_partial_notice_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.two-fa'));

        $response->assertStatus(200);
        $response->assertSee(__('components/admin/mode-partial-notice.message'));
    }

    // ========================================
    // Partial ページ: 自動設定項目の非表示
    // ========================================

    public function test_login_page_hides_attempt_limit_section_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        // 自動設定されるログイン試行制限セクションが非表示
        $response->assertDontSee(__('admin/settings/security/login.default_login_attempt_settings'));
    }

    public function test_login_page_shows_attempt_limit_section_in_advanced_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        // 詳細モードではログイン試行制限セクションが表示
        $response->assertSee(__('admin/settings/security/login.default_login_attempt_settings'));
    }

    public function test_two_fa_page_hides_detailed_settings_in_simple_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.two-fa'));

        $response->assertStatus(200);
        // 自動設定される詳細設定セクションが非表示
        $response->assertDontSee(__('admin/settings/security/two-fa.two_fa_detailed_settings'));
    }

    public function test_two_fa_page_shows_detailed_settings_in_advanced_mode(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.two-fa'));

        $response->assertStatus(200);
        // 詳細モードでは詳細設定セクションが表示
        $response->assertSee(__('admin/settings/security/two-fa.two_fa_detailed_settings'));
    }
}
