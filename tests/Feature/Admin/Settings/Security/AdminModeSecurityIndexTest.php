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
    // かんたんモード: Hiddenサブページのカードが非表示
    // ========================================

    public function test_simple_mode_hides_hidden_subpage_cards(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // Hidden設定のサブページカードが非表示
        $response->assertDontSee(route('admin.settings.security.password'));
        $response->assertDontSee(route('admin.settings.security.session'));
        $response->assertDontSee(route('admin.settings.security.notifications'));
        $response->assertDontSee(route('admin.settings.security.csp'));
        $response->assertDontSee(route('admin.settings.security.ip'));
        $response->assertDontSee(route('admin.settings.security.integrity'));
        $response->assertDontSee(route('admin.settings.security.environment'));
    }

    public function test_simple_mode_shows_visible_subpage_cards(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // Partial/ReadOnly設定のサブページカードは表示
        $response->assertSee(route('admin.settings.security.login'));
        $response->assertSee(route('admin.settings.security.two-fa'));
        $response->assertSee(route('admin.settings.security.captcha'));
        $response->assertSee(route('admin.settings.security.extensions'));
    }

    public function test_simple_mode_shows_readonly_banner(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // セキュリティ概要ページはReadOnlyなのでバナー表示
        $response->assertSee(__('components/admin/mode-readonly-banner.message'));
    }

    // ========================================
    // 詳細モード: 全サブページカードが表示
    // ========================================

    public function test_advanced_mode_shows_all_subpage_cards(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // 全サブページカードが表示
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

    public function test_advanced_mode_does_not_show_readonly_banner(): void
    {
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(200);

        // 詳細モードではバナー非表示
        $response->assertDontSee(__('components/admin/mode-readonly-banner.message'));
    }
}
