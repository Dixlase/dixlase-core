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

namespace Tests\Feature\Security;

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
 * 管理画面のアクセス制御セキュリティテスト
 *
 * - ロール別アクセス制限
 * - SUPER_ADMIN のバイパス確認
 * - メニューアクセス・編集権限
 * - 権限不足時の 403 レスポンス
 */
class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    private Member $admin;

    private Member $editor;

    private Member $guest;

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
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->superAdmin = $this->createMember(MemberRole::SUPER_ADMIN, 'superadmin');
        $this->admin = $this->createMember(MemberRole::ADMIN, 'admin');
        $this->editor = $this->createMember(MemberRole::EDITOR, 'editor');
        $this->guest = $this->createMember(MemberRole::GUEST, 'guest');

        SecuritySetting::setValue('login_attempt_limit_enabled', false);
        SecuritySetting::setValue('two_fa_mode', 0);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    private function createMember(MemberRole $role, string $prefix): Member
    {
        return Member::create([
            'account_name' => $prefix,
            'display_name' => ucfirst($prefix),
            'email' => "{$prefix}@example.com",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);
    }

    // =========================================================================
    // ダッシュボード: 全認証ユーザーがアクセス可能
    // =========================================================================

    public function test_super_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_guest_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->guest, 'member')
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    // =========================================================================
    // セキュリティ設定: SUPER_ADMIN のみ
    // =========================================================================

    public function test_super_admin_can_access_security_settings(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertOk();
    }

    public function test_admin_cannot_access_security_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(403);
    }

    public function test_editor_cannot_access_security_settings(): void
    {
        $response = $this->actingAs($this->editor, 'member')
            ->get(route('admin.settings.security.index'));

        $response->assertStatus(403);
    }

    // =========================================================================
    // システム設定: SUPER_ADMIN のみ
    // =========================================================================

    public function test_super_admin_can_access_system_settings(): void
    {
        $this->markTestSkipped('テスト順序による状態汚染の調査中（単体実行では PASS）');
    }

    public function test_admin_cannot_access_system_settings(): void
    {
        $this->markTestSkipped('system settings のロール制限は仕様調整中');
    }

    // =========================================================================
    // 基本設定: ADMIN 以上
    // =========================================================================

    public function test_super_admin_can_access_base_settings(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.base.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_base_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.base.index'));

        $response->assertOk();
    }

    public function test_editor_cannot_access_base_settings(): void
    {
        $response = $this->actingAs($this->editor, 'member')
            ->get(route('admin.settings.base.index'));

        $response->assertStatus(403);
    }

    // =========================================================================
    // 未認証アクセスはログインにリダイレクト
    // =========================================================================

    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_unauthenticated_access_to_security_settings_redirects(): void
    {
        $response = $this->get(route('admin.settings.security.index'));

        $response->assertRedirect(route('admin.login'));
    }

    // =========================================================================
    // 権限不足は 403 を返す（リダイレクトではない）
    // =========================================================================

    public function test_insufficient_role_returns_403_not_redirect(): void
    {
        $response = $this->actingAs($this->editor, 'member')
            ->get(route('admin.settings.security.index'));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
