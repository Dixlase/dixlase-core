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

namespace Tests\Unit;

use App\Helpers\LoginLockoutHelper;
use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use App\Services\AdminLoginLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminLoginLockoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminLoginLockoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminLoginLockoutService();

        // テスト用のデフォルト設定
        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 5);
        SecuritySetting::setValue('login_attempt_time_window', 15);
        SecuritySetting::setValue('login_attempt_lockout_duration', 30);
    }

    public function test_lockout_is_disabled_when_setting_is_false()
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        $this->assertFalse($this->service->isLockoutEnabled());
        $this->assertFalse($this->service->isLockedOut('test@example.com'));
    }

    public function test_lockout_is_enabled_when_setting_is_true()
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', true);

        $this->assertTrue($this->service->isLockoutEnabled());
    }

    public function test_lockout_settings_return_correct_values()
    {
        $settings = LoginLockoutHelper::getLockoutSettings();

        $this->assertTrue($settings['enabled']);
        $this->assertEquals(5, $settings['max_attempts']);
        $this->assertEquals(15, $settings['time_window']);
        $this->assertEquals(30, $settings['lockout_duration']);
    }

    public function test_user_is_not_locked_out_with_few_attempts()
    {
        $email = 'test@example.com';

        // 3回の失敗試行（最大5回未満）
        for ($i = 0; $i < 3; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertFalse($this->service->isLockedOut($email));
    }

    public function test_user_is_locked_out_with_max_attempts()
    {
        $email = 'test@example.com';

        // 5回の失敗試行（最大に到達）
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertTrue($this->service->isLockedOut($email));
    }

    public function test_user_is_locked_out_with_more_than_max_attempts()
    {
        $email = 'test@example.com';

        // 7回の失敗試行（最大5回を超過）
        for ($i = 0; $i < 7; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertTrue($this->service->isLockedOut($email));
    }

    public function test_ip_lockout_with_double_max_attempts()
    {
        $ip = '192.168.1.1';

        // 同一IPから10回の失敗試行（最大の2倍）
        for ($i = 0; $i < 10; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", $ip, null, false);
        }

        $this->assertTrue($this->service->isIpLockedOut($ip));
    }

    public function test_ip_is_not_locked_out_with_less_than_double_max_attempts()
    {
        $ip = '192.168.1.1';

        // 同一IPから8回の失敗試行（2倍の10未満）
        for ($i = 0; $i < 8; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", $ip, null, false);
        }

        $this->assertFalse($this->service->isIpLockedOut($ip));
    }

    public function test_lockout_remaining_minutes_calculation()
    {
        $email = 'test@example.com';

        // ロックアウトを発動する失敗試行を作成
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $remainingMinutes = $this->service->getLockoutRemainingMinutes($email);

        // 30分（ロックアウト期間）に近い値であるべき
        $this->assertGreaterThan(29, $remainingMinutes);
        $this->assertLessThanOrEqual(30, $remainingMinutes);
    }

    public function test_lockout_remaining_minutes_returns_value_based_on_last_attempt()
    {
        $email = 'test@example.com';

        // 失敗試行がない場合は null
        $this->assertNull($this->service->getLockoutRemainingMinutes('no-attempts@example.com'));

        // 2回のみの失敗試行（ロックアウト未発動）でも最終失敗からの経過時間で値が返る
        for ($i = 0; $i < 2; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $remainingMinutes = $this->service->getLockoutRemainingMinutes($email);
        $this->assertIsInt($remainingMinutes);
        $this->assertGreaterThan(0, $remainingMinutes);
    }

    public function test_handle_failed_login_returns_lockout_status()
    {
        $email = 'test@example.com';
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $result = $this->service->handleFailedLogin($request, $email);

        $this->assertFalse($result['is_locked_out']);
        $this->assertEquals(4, $result['remaining_attempts']); // 5 - 1 = 4
        $this->assertEquals(0, $result['lockout_minutes']);
    }

    public function test_handle_failed_login_triggers_lockout()
    {
        $email = 'test@example.com';
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        // 4回の既存失敗試行を作成
        for ($i = 0; $i < 4; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        // 5回目の試行でロックアウト発動
        $result = $this->service->handleFailedLogin($request, $email);

        $this->assertTrue($result['is_locked_out']);
        $this->assertGreaterThan(0, $result['lockout_minutes']);
    }

    public function test_lockout_status_details_when_disabled()
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        $details = $this->service->getLockoutStatusDetails('test@example.com', '192.168.1.1');

        $this->assertFalse($details['is_enabled']);
        $this->assertFalse($details['is_locked_out']);
    }

    public function test_lockout_status_details_when_enabled_and_not_locked_out()
    {
        $email = 'test@example.com';

        // 2回の失敗試行
        for ($i = 0; $i < 2; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $details = $this->service->getLockoutStatusDetails($email, '192.168.1.1');

        $this->assertTrue($details['is_enabled']);
        $this->assertFalse($details['is_locked_out']);
        $this->assertEquals(2, $details['failed_attempts']);
        $this->assertEquals(3, $details['remaining_attempts']);
        // remaining_minutes はロックアウト未発動でも最終失敗からの経過時間に基づき値が返る
        $this->assertIsInt($details['remaining_minutes']);
    }

    public function test_lockout_status_details_when_locked_out()
    {
        $email = 'test@example.com';

        // 5回の失敗試行でロックアウト発動
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $details = $this->service->getLockoutStatusDetails($email, '192.168.1.1');

        $this->assertTrue($details['is_enabled']);
        $this->assertTrue($details['is_locked_out']);
        $this->assertEquals(5, $details['failed_attempts']);
        $this->assertEquals(0, $details['remaining_attempts']);
        $this->assertNotNull($details['remaining_minutes']);
        $this->assertGreaterThan(0, $details['remaining_minutes']);
    }

    public function test_handle_successful_login_clears_failures()
    {
        $email = 'test@example.com';

        // 失敗試行を作成
        for ($i = 0; $i < 3; $i++) {
            MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        // 失敗試行が存在することを確認
        $this->assertEquals(3, MemberLoginAttempt::getFailedAttemptsCount($email, 15));

        // 成功ログイン処理
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);
        $this->service->handleSuccessfulLogin($email, $request);

        // 失敗試行がクリアされることを確認
        $this->assertEquals(0, MemberLoginAttempt::getFailedAttemptsCount($email, 15));
    }
}
