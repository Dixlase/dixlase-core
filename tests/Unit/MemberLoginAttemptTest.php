<?php

namespace Tests\Unit;

use App\Models\MemberLoginAttempt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLoginAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_login_attempt()
    {
        $attempt = MemberLoginAttempt::recordAttempt(
            'test@example.com',
            '192.168.1.1',
            'Mozilla/5.0',
            false
        );

        $this->assertInstanceOf(MemberLoginAttempt::class, $attempt);
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
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Create a successful attempt (should not be counted)
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, true);

        $count = MemberLoginAttempt::getFailedAttemptsCount($email, $timeWindow);
        $this->assertEquals(3, $count);
    }

    public function test_failed_attempts_count_respects_time_window()
    {
        $email = 'test@example.com';
        $timeWindow = 15; // 15 minutes

        // Create an old failed attempt (outside time window)
        $oldAttempt = MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        $oldAttempt->attempted_at = Carbon::now()->subMinutes(20);
        $oldAttempt->save();

        // Create recent failed attempts (within time window)
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        $count = MemberLoginAttempt::getFailedAttemptsCount($email, $timeWindow);
        $this->assertEquals(2, $count); // Only recent attempts should be counted
    }

    public function test_can_get_failed_attempts_count_by_ip()
    {
        $ip = '192.168.1.1';
        $timeWindow = 15;

        // Create failed attempts from same IP with different emails
        MemberLoginAttempt::recordAttempt('user1@example.com', $ip, null, false);
        MemberLoginAttempt::recordAttempt('user2@example.com', $ip, null, false);
        MemberLoginAttempt::recordAttempt('user3@example.com', $ip, null, false);

        // Create attempt from different IP (should not be counted)
        MemberLoginAttempt::recordAttempt('user4@example.com', '192.168.1.2', null, false);

        $count = MemberLoginAttempt::getFailedAttemptsCountByIp($ip, $timeWindow);
        $this->assertEquals(3, $count);
    }

    public function test_can_get_last_failed_attempt()
    {
        $email = 'test@example.com';

        // Create multiple failed attempts
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        sleep(1); // Ensure different timestamps
        $lastAttempt = MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        $retrieved = MemberLoginAttempt::getLastFailedAttempt($email);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($lastAttempt->attempted_at->timestamp, $retrieved->timestamp);
    }

    public function test_can_clear_failed_attempts()
    {
        $email = 'test@example.com';

        // Create failed attempts
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Verify attempts exist
        $this->assertEquals(2, MemberLoginAttempt::getFailedAttemptsCount($email, 15));

        // Clear failed attempts
        MemberLoginAttempt::clearFailedAttempts($email);

        // Verify attempts are cleared
        $this->assertEquals(0, MemberLoginAttempt::getFailedAttemptsCount($email, 15));
    }

    public function test_clear_failed_attempts_does_not_affect_successful_attempts()
    {
        $email = 'test@example.com';

        // Create failed and successful attempts
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, true);

        // Clear failed attempts
        MemberLoginAttempt::clearFailedAttempts($email);

        // Verify only failed attempts are cleared
        $this->assertEquals(0, MemberLoginAttempt::where('identifier', $email)->where('successful', false)->count());
        $this->assertEquals(1, MemberLoginAttempt::where('identifier', $email)->where('successful', true)->count());
    }

    public function test_can_cleanup_old_attempts()
    {
        $email = 'test@example.com';

        // Create old attempt
        $oldAttempt = MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);
        $oldAttempt->attempted_at = Carbon::now()->subDays(35);
        $oldAttempt->save();

        // Create recent attempt
        MemberLoginAttempt::recordAttempt($email, '192.168.1.1', null, false);

        // Cleanup attempts older than 30 days
        $deletedCount = MemberLoginAttempt::cleanupOldAttempts(30);

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, MemberLoginAttempt::count()); // Only recent attempt should remain
    }
}
