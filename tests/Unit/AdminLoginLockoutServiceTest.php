<?php

namespace Tests\Unit;

use App\Models\AdminLoginAttempt;
use App\Models\MemberSetting;
use App\Services\AdminLoginLockoutService;
use Carbon\Carbon;
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
        
        // Set default test settings
        MemberSetting::setValue('login_attempt_limit_enabled', true);
        MemberSetting::setValue('login_attempt_max_attempts', 5);
        MemberSetting::setValue('login_attempt_time_window', 15);
        MemberSetting::setValue('login_attempt_lockout_duration', 30);
    }

    public function test_lockout_is_disabled_when_setting_is_false()
    {
        MemberSetting::setValue('login_attempt_limit_enabled', false);

        $this->assertFalse($this->service->isLockoutEnabled());
        $this->assertFalse($this->service->isLockedOut('test@example.com'));
    }

    public function test_lockout_is_enabled_when_setting_is_true()
    {
        MemberSetting::setValue('login_attempt_limit_enabled', true);

        $this->assertTrue($this->service->isLockoutEnabled());
    }

    public function test_gets_correct_settings_values()
    {
        $this->assertEquals(5, $this->service->getMaxAttempts());
        $this->assertEquals(15, $this->service->getTimeWindow());
        $this->assertEquals(30, $this->service->getLockoutDuration());
    }

    public function test_user_is_not_locked_out_with_few_attempts()
    {
        $email = 'test@example.com';

        // Create 3 failed attempts (below max of 5)
        for ($i = 0; $i < 3; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertFalse($this->service->isLockedOut($email));
    }

    public function test_user_is_locked_out_with_max_attempts()
    {
        $email = 'test@example.com';

        // Create 5 failed attempts (equals max)
        for ($i = 0; $i < 5; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertTrue($this->service->isLockedOut($email));
    }

    public function test_user_is_locked_out_with_more_than_max_attempts()
    {
        $email = 'test@example.com';

        // Create 7 failed attempts (exceeds max of 5)
        for ($i = 0; $i < 7; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $this->assertTrue($this->service->isLockedOut($email));
    }

    public function test_ip_lockout_with_double_max_attempts()
    {
        $ip = '192.168.1.1';

        // Create 10 failed attempts from same IP (double the max of 5)
        for ($i = 0; $i < 10; $i++) {
            AdminLoginAttempt::recordAttempt("user{$i}@example.com", $ip, null, false);
        }

        $this->assertTrue($this->service->isIpLockedOut($ip));
    }

    public function test_ip_is_not_locked_out_with_less_than_double_max_attempts()
    {
        $ip = '192.168.1.1';

        // Create 8 failed attempts from same IP (less than double max of 10)
        for ($i = 0; $i < 8; $i++) {
            AdminLoginAttempt::recordAttempt("user{$i}@example.com", $ip, null, false);
        }

        $this->assertFalse($this->service->isIpLockedOut($ip));
    }

    public function test_lockout_remaining_minutes_calculation()
    {
        $email = 'test@example.com';

        // Create max failed attempts to trigger lockout
        for ($i = 0; $i < 5; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $remainingMinutes = $this->service->getLockoutRemainingMinutes($email);
        
        // Should be close to 30 minutes (lockout duration)
        $this->assertGreaterThan(29, $remainingMinutes);
        $this->assertLessThanOrEqual(30, $remainingMinutes);
    }

    public function test_lockout_remaining_minutes_returns_null_when_not_locked_out()
    {
        $email = 'test@example.com';

        // Create only 2 failed attempts (below max)
        for ($i = 0; $i < 2; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $remainingMinutes = $this->service->getLockoutRemainingMinutes($email);
        $this->assertNull($remainingMinutes);
    }

    public function test_handle_successful_login_records_attempt_and_clears_failures()
    {
        $email = 'test@example.com';

        // Create some failed attempts
        for ($i = 0; $i < 3; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        // Verify failed attempts exist
        $this->assertEquals(3, AdminLoginAttempt::getFailedAttemptsCount($email, 15));

        // Handle successful login
        $this->service->handleSuccessfulLogin($email);

        // Verify failed attempts are cleared
        $this->assertEquals(0, AdminLoginAttempt::getFailedAttemptsCount($email, 15));
        
        // Verify successful attempt is recorded
        $this->assertEquals(1, AdminLoginAttempt::where('identifier', $email)->where('successful', true)->count());
    }

    public function test_handle_failed_login_records_attempt()
    {
        $email = 'test@example.com';
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0'
        ]);

        $result = $this->service->handleFailedLogin($request, $email);

        $this->assertFalse($result['locked_out']);
        $this->assertEquals(4, $result['remaining_attempts']); // 5 - 1 = 4
        $this->assertEquals(1, $result['failed_attempts']);
        $this->assertNull($result['lockout_minutes']);
    }

    public function test_handle_failed_login_triggers_lockout()
    {
        $email = 'test@example.com';
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0'
        ]);

        // Create 4 existing failed attempts
        for ($i = 0; $i < 4; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        // This should be the 5th attempt, triggering lockout
        $result = $this->service->handleFailedLogin($request, $email);

        $this->assertTrue($result['locked_out']);
        $this->assertEquals(0, $result['remaining_attempts']);
        $this->assertEquals(5, $result['failed_attempts']);
        $this->assertEquals(30, $result['lockout_minutes']);
    }

    public function test_get_lockout_info_when_disabled()
    {
        MemberSetting::setValue('login_attempt_limit_enabled', false);

        $info = $this->service->getLockoutInfo('test@example.com');

        $this->assertFalse($info['enabled']);
        $this->assertFalse($info['locked_out']);
    }

    public function test_get_lockout_info_when_enabled_and_not_locked_out()
    {
        $email = 'test@example.com';

        // Create 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $info = $this->service->getLockoutInfo($email);

        $this->assertTrue($info['enabled']);
        $this->assertFalse($info['locked_out']);
        $this->assertEquals(2, $info['failed_attempts']);
        $this->assertEquals(5, $info['max_attempts']);
        $this->assertEquals(3, $info['remaining_attempts']);
        $this->assertNull($info['remaining_minutes']);
        $this->assertEquals(15, $info['time_window']);
        $this->assertEquals(30, $info['lockout_duration']);
    }

    public function test_get_lockout_info_when_locked_out()
    {
        $email = 'test@example.com';

        // Create 5 failed attempts to trigger lockout
        for ($i = 0; $i < 5; $i++) {
            AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        }

        $info = $this->service->getLockoutInfo($email);

        $this->assertTrue($info['enabled']);
        $this->assertTrue($info['locked_out']);
        $this->assertEquals(5, $info['failed_attempts']);
        $this->assertEquals(5, $info['max_attempts']);
        $this->assertEquals(0, $info['remaining_attempts']);
        $this->assertNotNull($info['remaining_minutes']);
        $this->assertGreaterThan(0, $info['remaining_minutes']);
    }

    public function test_record_login_attempt()
    {
        $email = 'test@example.com';
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0'
        ]);

        $attempt = $this->service->recordLoginAttempt($request, $email, false);

        $this->assertInstanceOf(AdminLoginAttempt::class, $attempt);
        $this->assertEquals($email, $attempt->identifier);
        $this->assertEquals('192.168.1.1', $attempt->ip_address);
        $this->assertEquals('Mozilla/5.0', $attempt->user_agent);
        $this->assertFalse($attempt->successful);
    }
}
