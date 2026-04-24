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

namespace Tests\Feature\Admin\SafeMode;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SafeMode ミドルウェアのテスト
 *
 * テスト専用ルートを使用してセーフモードの有効化・セッション保持を検証する。
 * admin.dashboard は環境依存が多いため、web ミドルウェアグループのテストルートを使用。
 */
class SafeModeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => AuthenticationMode::Disabled,
        ]);

        // テスト専用ルート（web ミドルウェアグループを通す）
        Route::middleware('web')->get('/test-safe-mode', function () {
            return response('ok');
        })->name('test.safe-mode');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_safe_csp_activates_csp_mode(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=csp');

        $this->assertTrue(session('safe_mode_csp'));
    }

    public function test_safe1_activates_csp_mode_for_backward_compat(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=1');

        $this->assertTrue(session('safe_mode_csp'));
    }

    public function test_safe_plugins_activates_plugins_mode(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=plugins');

        $this->assertTrue(session('safe_mode_plugins'));
    }

    public function test_safe_theme_activates_theme_mode(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=theme');

        $this->assertTrue(session('safe_mode_theme'));
    }

    public function test_comma_separated_activates_multiple_modes(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=csp,plugins');

        $this->assertTrue(session('safe_mode_csp'));
        $this->assertTrue(session('safe_mode_plugins'));
    }

    public function test_unauthenticated_user_cannot_activate_safe_mode(): void
    {
        $this->get('/test-safe-mode?safe=csp');

        $this->assertNull(session('safe_mode_csp'));
    }

    public function test_invalid_mode_is_ignored(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $this->get('/test-safe-mode?safe=invalid');

        $this->assertNull(session('safe_mode_csp'));
        $this->assertNull(session('safe_mode_plugins'));
        $this->assertNull(session('safe_mode_theme'));
    }

    public function test_safe_mode_is_persistent_in_session(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        // セーフモード有効化
        $this->get('/test-safe-mode?safe=csp');
        $this->assertTrue(session('safe_mode_csp'));

        // パラメータなしで再アクセスしてもセッションに残る
        $this->withSession(['safe_mode_csp' => true])
            ->get('/test-safe-mode');
        $this->assertTrue(session('safe_mode_csp'));
    }
}
