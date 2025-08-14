<?php

namespace Tests\Feature\Admin;

use App\Models\MemberLoginAttempt;
use App\Models\Member;
use App\Models\MemberSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    private Member $testMember;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test member
        $this->testMember = Member::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
        ]);

        // Set default lockout settings
        MemberSetting::setValue('login_attempt_limit_enabled', true);
        MemberSetting::setValue('login_attempt_max_attempts', 3); // Lower for easier testing
        MemberSetting::setValue('login_attempt_time_window', 15);
        MemberSetting::setValue('login_attempt_lockout_duration', 30);
    }

    public function test_successful_login_works_normally()
    {
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->testMember, 'member');
    }

    public function test_failed_login_records_attempt()
    {
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);

        // Verify attempt was recorded
        $this->assertEquals(1, MemberLoginAttempt::where('identifier', $this->testMember->email)->count());
        
        $attempt = MemberLoginAttempt::where('identifier', $this->testMember->email)->first();
        $this->assertFalse($attempt->successful);
        $this->assertEquals('127.0.0.1', $attempt->ip_address);
    }

    public function test_multiple_failed_attempts_show_remaining_count()
    {
        // First failed attempt
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContains('2', session('errors')->first('email')); // 2 attempts remaining

        // Second failed attempt
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContains('1', session('errors')->first('email')); // 1 attempt remaining
    }

    public function test_user_gets_locked_out_after_max_attempts()
    {
        // Make 3 failed attempts (our test max)
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), [
                'email' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // Fourth attempt should show lockout message
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errorMessage = session('errors')->first('email');
        $this->assertStringContains('30', $errorMessage); // 30 minutes lockout
        $this->assertStringContains('ログイン試行回数が上限に達しました', $errorMessage);
    }

    public function test_locked_out_user_cannot_login_even_with_correct_password()
    {
        // Create max failed attempts to trigger lockout
        for ($i = 0; $i < 3; $i++) {
            MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
        }

        // Try to login with correct password - should still be blocked
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest('member');
        
        $errorMessage = session('errors')->first('email');
        $this->assertStringContains('ログイン試行回数が上限に達しました', $errorMessage);
    }

    public function test_successful_login_clears_failed_attempts()
    {
        // Create some failed attempts
        for ($i = 0; $i < 2; $i++) {
            MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
        }

        // Verify failed attempts exist
        $this->assertEquals(2, MemberLoginAttempt::getFailedAttemptsCount($this->testMember->email, 15));

        // Successful login
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        // Verify failed attempts are cleared
        $this->assertEquals(0, MemberLoginAttempt::getFailedAttemptsCount($this->testMember->email, 15));
        
        // Verify successful attempt is recorded
        $this->assertEquals(1, MemberLoginAttempt::where('identifier', $this->testMember->email)
            ->where('successful', true)->count());
    }

    public function test_ip_based_lockout()
    {
        MemberSetting::setValue('login_attempt_max_attempts', 2); // Set lower for IP test

        // Create failed attempts from same IP with different emails (4 attempts = 2 * 2)
        for ($i = 0; $i < 4; $i++) {
            MemberLoginAttempt::recordAttempt("user{$i}@example.com", '127.0.0.1', null, false);
        }

        // Try to login - should be blocked by IP lockout
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        
        $errorMessage = session('errors')->first('email');
        $this->assertStringContains('このIPアドレスからのログイン試行が一時的に制限されています', $errorMessage);
    }

    public function test_lockout_disabled_allows_unlimited_attempts()
    {
        // Disable lockout feature
        MemberSetting::setValue('login_attempt_limit_enabled', false);

        // Make many failed attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('admin.login'), [
                'email' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors(['email']);
            $errorMessage = session('errors')->first('email');
            
            // Should show generic error, not lockout message
            $this->assertEquals('認証情報が正しくありません。', $errorMessage);
        }

        // Should still be able to login with correct password
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->testMember, 'member');
    }

    public function test_old_failed_attempts_do_not_count_toward_lockout()
    {
        $timeWindow = 15; // minutes
        
        // Create old failed attempts (outside time window)
        for ($i = 0; $i < 5; $i++) {
            $attempt = MemberLoginAttempt::recordAttempt($this->testMember->email, '127.0.0.1', null, false);
            $attempt->attempted_at = Carbon::now()->subMinutes($timeWindow + 5); // 20 minutes ago
            $attempt->save();
        }

        // Try to login - should work because old attempts don't count
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->testMember, 'member');
    }

    public function test_different_users_have_separate_lockout_counts()
    {
        // Create another test member
        $otherMember = Member::create([
            'name' => 'Other Admin',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
        ]);

        // Make max failed attempts for first user
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), [
                'email' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // First user should be locked out
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'correct-password',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Second user should still be able to login
        $response = $this->post(route('admin.login'), [
            'email' => $otherMember->email,
            'password' => 'other-password',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_nonexistent_user_attempts_are_still_recorded()
    {
        $response = $this->post(route('admin.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Verify attempt was recorded even for non-existent user
        $this->assertEquals(1, MemberLoginAttempt::where('identifier', 'nonexistent@example.com')->count());
    }

    public function test_lockout_respects_custom_settings()
    {
        // Set custom settings
        MemberSetting::setValue('login_attempt_max_attempts', 2);
        MemberSetting::setValue('login_attempt_lockout_duration', 60);

        // Make 2 failed attempts (new max)
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.login'), [
                'email' => $this->testMember->email,
                'password' => 'wrong-password',
            ]);
        }

        // Third attempt should trigger lockout with custom duration
        $response = $this->post(route('admin.login'), [
            'email' => $this->testMember->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errorMessage = session('errors')->first('email');
        $this->assertStringContains('60', $errorMessage); // Custom 60 minutes lockout
    }
}
