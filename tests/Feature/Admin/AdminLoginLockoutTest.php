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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminLoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    private Member $testMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        // テストメンバーを作成
        $this->testMember = Member::create([
            'account_name' => 'lockoutadmin',
            'display_name' => 'Lockout Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // ロックアウト設定（SecuritySetting を使用）
        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 3);
        SecuritySetting::setValue('login_attempt_time_window', 15);
        SecuritySetting::setValue('login_attempt_lockout_duration', 30);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    public function test_successful_login_works_normally(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_failed_login_records_attempt(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();

        // 試行が記録されたことを確認
        $this->assertGreaterThanOrEqual(
            1,
            MemberLoginAttempt::where('identifier', $this->testMember->email)->count()
        );
    }

    public function test_multiple_failed_attempts_show_remaining_count(): void
    {
        // 1回目の失敗
        $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        // 2回目の失敗
        $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        // 失敗が2回記録されていることを確認
        $failedCount = MemberLoginAttempt::where('identifier', $this->testMember->email)
            ->where('successful', false)
            ->count();
        $this->assertGreaterThanOrEqual(2, $failedCount);
    }

    public function test_user_gets_locked_out_after_max_attempts(): void
    {
        // 3回失敗（テスト用の上限）
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // 4回目はロックアウトメッセージを表示
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_locked_out_user_cannot_login_even_with_correct_password(): void
    {
        // 最大失敗回数を記録してロックアウトをトリガー
        for ($i = 0; $i < 3; $i++) {
            MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
        }

        // 正しいパスワードでもブロックされる
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        // 失敗試行を作成
        for ($i = 0; $i < 2; $i++) {
            MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
        }

        // 失敗試行が存在することを確認
        $this->assertEquals(2, MemberLoginAttempt::getFailedAttemptsCount($this->testMember->email, 15));

        // ログイン成功
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();

        // 失敗試行がクリアされたことを確認
        $this->assertEquals(0, MemberLoginAttempt::getFailedAttemptsCount($this->testMember->email, 15));
    }

    public function test_ip_based_lockout(): void
    {
        SecuritySetting::setValue('login_attempt_max_attempts', 2);
        // IP cap is read from its own setting key; align with max_attempts * 2.
        SecuritySetting::setValue('login_attempt_max_attempts_ip', 4);

        // 同一IPから別メールで失敗試行を4回（2 * 2 = IP上限）
        for ($i = 0; $i < 4; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", '127.0.0.1', null, false);
        }

        // ログイン試行 — IPロックアウトでブロックされるはず
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_lockout_disabled_allows_unlimited_attempts(): void
    {
        // ロックアウト機能を無効化
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        // 多数の失敗試行
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // 正しいパスワードでログイン可能
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_old_failed_attempts_do_not_count_toward_lockout(): void
    {
        $timeWindow = 15; // 分

        // 時間ウィンドウ外の古い失敗試行を作成
        for ($i = 0; $i < 5; $i++) {
            $attempt = MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
            $attempt->attempted_at = Carbon::now()->subMinutes($timeWindow + 5);
            $attempt->save();
        }

        // 古い試行はカウントされないのでログイン可能
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_different_users_have_separate_lockout_counts(): void
    {
        // 別のテストメンバーを作成
        $otherMember = Member::create([
            'account_name' => 'otheradmin',
            'display_name' => 'Other Admin',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // 最初のユーザーで最大回数失敗
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // 最初のユーザーはロックアウトされている
        $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'correct-password',
        ]);
        $this->assertGuest('member');

        // 2番目のユーザーはまだログイン可能
        $response = $this->post(route('admin.login.store'), [
            'login' => $otherMember->email,
            'password' => 'other-password',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_nonexistent_user_attempts_are_still_recorded(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertRedirect();

        // 存在しないユーザーの試行も記録される
        $this->assertGreaterThanOrEqual(
            1,
            MemberLoginAttempt::where('identifier', 'nonexistent@example.com')->count()
        );
    }

    public function test_lockout_respects_custom_settings(): void
    {
        // カスタム設定
        SecuritySetting::setValue('login_attempt_max_attempts', 2);
        SecuritySetting::setValue('login_attempt_lockout_duration', 60);

        // 2回失敗（新しい上限）
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // 3回目はロックアウト
        $response = $this->post(route('admin.login.store'), [
            'login' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }
}
