<?php

namespace Tests\Unit;

use App\Models\AdminLoginAttempt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_login_attempt()
    {
        $attempt = AdminLoginAttempt::recordAttempt(
            'test@example.com',
            '192.168.1.1',
            'Mozilla/5.0',
            false
        );

        $this->assertInstanceOf(AdminLoginAttempt::class, $attempt);
        $this->assertEquals('test@example.com', $attempt->identifier);
        $this->assertEquals('192.168.1.1', $attempt->ip_address);
        $this->assertEquals('Mozilla/5.0', $attempt->user_agent);
        $this->assertFalse($attempt->successful);
        $this->assertNotNull($attempt->attempted_at);
    }

    public function test_can_get_failed_attempts_count()
    {
        $email = 'test@example.com';
        $timeWindow = 15; // 15 minutes

        // Create some failed attempts
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Create a successful attempt (should not be counted)
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, true);

        $count = AdminLoginAttempt::getFailedAttemptsCount($email, $timeWindow);
        $this->assertEquals(3, $count);
    }

    public function test_failed_attempts_count_respects_time_window()
    {
        $email = 'test@example.com';
        $timeWindow = 15; // 15 minutes

        // Create an old failed attempt (outside time window)
        $oldAttempt = AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        $oldAttempt->attempted_at = Carbon::now()->subMinutes(20);
        $oldAttempt->save();

        // Create recent failed attempts (within time window)
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        $count = AdminLoginAttempt::getFailedAttemptsCount($email, $timeWindow);
        $this->assertEquals(2, $count); // Only recent attempts should be counted
    }

    public function test_can_get_failed_attempts_count_by_ip()
    {
        $ip = '192.168.1.1';
        $timeWindow = 15;

        // Create failed attempts from same IP with different emails
        AdminLoginAttempt::recordAttempt('user1@example.com', $ip, null, false);
        AdminLoginAttempt::recordAttempt('user2@example.com', $ip, null, false);
        AdminLoginAttempt::recordAttempt('user3@example.com', $ip, null, false);

        // Create attempt from different IP (should not be counted)
        AdminLoginAttempt::recordAttempt('user4@example.com', '192.168.1.2', null, false);

        $count = AdminLoginAttempt::getFailedAttemptsCountByIp($ip, $timeWindow);
        $this->assertEquals(3, $count);
    }

    public function test_can_get_last_failed_attempt()
    {
        $email = 'test@example.com';

        // Create multiple failed attempts
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        sleep(1); // Ensure different timestamps
        $lastAttempt = AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        $retrieved = AdminLoginAttempt::getLastFailedAttempt($email);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($lastAttempt->attempted_at->timestamp, $retrieved->timestamp);
    }

    public function test_can_clear_failed_attempts()
    {
        $email = 'test@example.com';

        // Create failed attempts
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Verify attempts exist
        $this->assertEquals(2, AdminLoginAttempt::getFailedAttemptsCount($email, 15));

        // Clear failed attempts
        AdminLoginAttempt::clearFailedAttempts($email);

        // Verify attempts are cleared
        $this->assertEquals(0, AdminLoginAttempt::getFailedAttemptsCount($email, 15));
    }

    public function test_clear_failed_attempts_does_not_affect_successful_attempts()
    {
        $email = 'test@example.com';

        // Create failed and successful attempts
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, true);

        // Clear failed attempts
        AdminLoginAttempt::clearFailedAttempts($email);

        // Verify only failed attempts are cleared
        $this->assertEquals(0, AdminLoginAttempt::where('identifier', $email)->where('successful', false)->count());
        $this->assertEquals(1, AdminLoginAttempt::where('identifier', $email)->where('successful', true)->count());
    }

    public function test_can_cleanup_old_attempts()
    {
        $email = 'test@example.com';

        // Create old attempt
        $oldAttempt = AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        $oldAttempt->attempted_at = Carbon::now()->subDays(35);
        $oldAttempt->save();

        // Create recent attempt
        AdminLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Cleanup attempts older than 30 days
        $deletedCount = AdminLoginAttempt::cleanupOldAttempts(30);

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, AdminLoginAttempt::count()); // Only recent attempt should remain
    }
}
