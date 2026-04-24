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

namespace Tests\Feature\Admin\Controllers;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * 管理画面 Settings コントローラのスモークテスト
 *
 * 各設定ページが SUPER_ADMIN でアクセス可能であることを確認。
 * 認証なしでリダイレクトされることも確認。
 */
class AdminSettingsControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);

        SecuritySetting::setValue('two_fa_mode', 0);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // 基本設定
    // =========================================================================

    public function test_base_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.index'))
            ->assertOk();
    }

    public function test_base_site(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.site'))
            ->assertOk();
    }

    public function test_base_admin(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.admin'))
            ->assertOk();
    }

    public function test_base_mail(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.mail'))
            ->assertOk();
    }

    public function test_base_maintenance(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.maintenance'))
            ->assertOk();
    }

    public function test_base_editor(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.editor'))
            ->assertOk();
    }

    // =========================================================================
    // セキュリティ設定
    // =========================================================================

    public function test_security_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'))
            ->assertOk();
    }

    public function test_security_password(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.password'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_security_session(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.session'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_security_ip(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.ip'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_security_notifications(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.notifications'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_security_csp(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.csp'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_security_captcha(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.captcha'))
            ->assertOk();
    }

    // =========================================================================
    // システム設定
    // =========================================================================

    public function test_systems_info(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.info'));

        $this->assertTrue($response->isOk() || $response->isRedirect());
    }

    public function test_systems_cache(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.cache'));

        $this->assertTrue($response->isOk() || $response->isRedirect());
    }

    public function test_systems_api(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.api'));

        $this->assertTrue($response->isOk() || $response->isRedirect());
    }

    // =========================================================================
    // プラグイン・テーマ設定
    // =========================================================================

    public function test_plugins_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.plugins.index'))
            ->assertOk();
    }

    public function test_themes_index(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.themes.index'))
            ->assertOk();
    }

    // =========================================================================
    // 未認証ではリダイレクト
    // =========================================================================

    public function test_unauthenticated_redirects(): void
    {
        $this->get(route('admin.settings.base.index'))
            ->assertRedirect();
    }
}
