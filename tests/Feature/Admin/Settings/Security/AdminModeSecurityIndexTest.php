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

namespace Tests\Feature\Admin\Settings\Security;

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
 * かんたんモード時のセキュリティ概要ページカードフィルタリングテスト
 */
class AdminModeSecurityIndexTest extends TestCase
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
    // かんたんモード: Hiddenサブページはサマリーカード（リンクなし）
    // ========================================

    public function test_simple_mode_shows_summary_cards_without_links_for_hidden_subpages(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // Hiddenサブページはリンクなしサマリーカードとして表示
        $response->assertDontSee(route('admin.settings.security.password'));
        $response->assertDontSee(route('admin.settings.security.session'));
        $response->assertDontSee(route('admin.settings.security.notifications'));
        $response->assertDontSee(route('admin.settings.security.csp'));
        $response->assertDontSee(route('admin.settings.security.ip'));
        $response->assertDontSee(route('admin.settings.security.integrity'));
        $response->assertDontSee(route('admin.settings.security.extensions'));
        $response->assertDontSee(route('admin.settings.security.environment'));

        // サマリーカードには自動設定テキストが表示される
        $response->assertSee(__('admin/settings/security/index.auto_configured'));
    }

    public function test_simple_mode_shows_visible_subpage_cards(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // Partial/Full設定のサブページカードはリンク付きで表示
        $response->assertSee(route('admin.settings.security.login'));
        $response->assertSee(route('admin.settings.security.two-fa'));
        $response->assertSee(route('admin.settings.security.captcha'));
    }

    public function test_simple_mode_does_not_show_readonly_banner(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // セキュリティ概要ページはFullなのでReadOnlyバナー非表示
        $response->assertDontSee(__('components/admin/mode-readonly-banner.message'));
    }

    // ========================================
    // 詳細モード: 全サブページカードがリンク付きで表示
    // ========================================

    public function test_advanced_mode_shows_all_subpage_cards(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // 全サブページカードがリンク付きで表示
        $response->assertSee(route('admin.settings.security.password'));
        $response->assertSee(route('admin.settings.security.login'));
        $response->assertSee(route('admin.settings.security.two-fa'));
        $response->assertSee(route('admin.settings.security.captcha'));
        $response->assertSee(route('admin.settings.security.session'));
        $response->assertSee(route('admin.settings.security.notifications'));
        $response->assertSee(route('admin.settings.security.csp'));
        $response->assertSee(route('admin.settings.security.extensions'));
        $response->assertSee(route('admin.settings.security.ip'));
        $response->assertSee(route('admin.settings.security.integrity'));
        $response->assertSee(route('admin.settings.security.environment'));
    }

    public function test_advanced_mode_does_not_show_auto_configured_text(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // 詳細モードでは自動設定テキスト非表示
        $response->assertDontSee(__('admin/settings/security/index.auto_configured'));
    }
}
