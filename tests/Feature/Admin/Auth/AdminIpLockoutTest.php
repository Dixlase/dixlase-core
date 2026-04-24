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

namespace Tests\Feature\Admin\Auth;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * IP レベルロックアウトのセキュリティテスト
 *
 * - 同一 IP からの大量失敗で IP ロックアウト
 * - IP ロックアウト中は別識別子でも拒否
 * - ロックアウト設定の動作確認
 */
class AdminIpLockoutTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 3);
        SecuritySetting::setValue('login_attempt_time_window', 15);
        SecuritySetting::setValue('login_attempt_lockout_duration', 30);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_ip_lockout_triggers_after_threshold(): void
    {
        for ($i = 0; $i < 6; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", '127.0.0.1', 'TestAgent', false);
        }

        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_ip_lockout_blocks_different_identifiers(): void
    {
        $otherAdmin = Member::create([
            'account_name' => 'other',
            'display_name' => 'Other',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);

        for ($i = 0; $i < 6; $i++) {
            MemberLoginAttempt::recordAttempt("attacker{$i}@example.com", '127.0.0.1', 'TestAgent', false);
        }

        $response = $this->post(route('admin.login.store'), [
            'login' => 'other@example.com',
            'password' => 'other-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_identifier_lockout_does_not_affect_other_users(): void
    {
        $otherAdmin = Member::create([
            'account_name' => 'other',
            'display_name' => 'Other',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);
        $this->assertGuest('member');

        $response = $this->post(route('admin.login.store'), [
            'login' => 'other@example.com',
            'password' => 'other-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($otherAdmin, 'member');
    }

    public function test_lockout_disabled_allows_unlimited_ip_attempts(): void
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        for ($i = 0; $i < 20; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", '127.0.0.1', 'TestAgent', false);
        }

        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_successful_login_does_not_clear_ip_lockout_for_others(): void
    {
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt("attacker{$i}@example.com", '127.0.0.1', 'TestAgent', false);
        }

        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($this->admin, 'member');

        $ipFailedCount = MemberLoginAttempt::getFailedAttemptsCountByIp('127.0.0.1', 15);
        $this->assertEquals(5, $ipFailedCount);
    }
}
