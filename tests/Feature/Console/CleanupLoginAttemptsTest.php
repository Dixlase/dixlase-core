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

namespace Tests\Feature\Console;

use App\Models\MemberLoginAttempt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupLoginAttemptsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('admin:cleanup-login-attempts コマンドは dls:cleanup --type=login_attempts に統合済み');
    }

    public function test_cleanup_command_removes_old_attempts()
    {
        // Create old attempts (35 days old)
        for ($i = 0; $i < 3; $i++) {
            $attempt = MemberLoginAttempt::recordAttempt("old{$i}@example.com", '192.168.1.1', null, false);
            $attempt->attempted_at = Carbon::now()->subDays(35);
            $attempt->save();
        }

        // Create recent attempts (10 days old)
        for ($i = 0; $i < 2; $i++) {
            $attempt = MemberLoginAttempt::recordAttempt("recent{$i}@example.com", '192.168.1.1', null, false);
            $attempt->attempted_at = Carbon::now()->subDays(10);
            $attempt->save();
        }

        // Verify all attempts exist
        $this->assertEquals(5, MemberLoginAttempt::count());

        // Run cleanup command with default 30 days
        $this->artisan('admin:cleanup-login-attempts')
            ->expectsOutput('Cleaning up login attempts older than 30 days...')
            ->expectsOutput('Successfully deleted 3 old login attempt records.')
            ->assertExitCode(0);

        // Verify only recent attempts remain
        $this->assertEquals(2, MemberLoginAttempt::count());
    }

    public function test_cleanup_command_with_custom_days()
    {
        // Create attempts of different ages
        $attempt1 = MemberLoginAttempt::recordAttempt('user1@example.com', '192.168.1.1', null, false);
        $attempt1->attempted_at = Carbon::now()->subDays(15);
        $attempt1->save();

        $attempt2 = MemberLoginAttempt::recordAttempt('user2@example.com', '192.168.1.1', null, false);
        $attempt2->attempted_at = Carbon::now()->subDays(8);
        $attempt2->save();

        $attempt3 = MemberLoginAttempt::recordAttempt('user3@example.com', '192.168.1.1', null, false);
        $attempt3->attempted_at = Carbon::now()->subDays(5);
        $attempt3->save();

        // Run cleanup with custom 10 days
        $this->artisan('admin:cleanup-login-attempts', ['--days' => 10])
            ->expectsOutput('Cleaning up login attempts older than 10 days...')
            ->expectsOutput('Successfully deleted 1 old login attempt records.')
            ->assertExitCode(0);

        // Verify only attempts newer than 10 days remain
        $this->assertEquals(2, MemberLoginAttempt::count());
    }

    public function test_cleanup_command_with_no_old_records()
    {
        // Create only recent attempts
        for ($i = 0; $i < 3; $i++) {
            MemberLoginAttempt::recordAttempt("recent{$i}@example.com", '192.168.1.1', null, false);
        }

        $this->artisan('admin:cleanup-login-attempts')
            ->expectsOutput('Cleaning up login attempts older than 30 days...')
            ->expectsOutput('No old login attempt records found to delete.')
            ->assertExitCode(0);

        // All attempts should still exist
        $this->assertEquals(3, MemberLoginAttempt::count());
    }

    public function test_cleanup_command_validates_days_parameter()
    {
        $this->artisan('admin:cleanup-login-attempts', ['--days' => 0])
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);

        $this->artisan('admin:cleanup-login-attempts', ['--days' => -5])
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);
    }

    public function test_cleanup_command_preserves_successful_attempts()
    {
        // Create old failed attempt
        $failedAttempt = MemberLoginAttempt::recordAttempt('user@example.com', '192.168.1.1', null, false);
        $failedAttempt->attempted_at = Carbon::now()->subDays(35);
        $failedAttempt->save();

        // Create old successful attempt
        $successfulAttempt = MemberLoginAttempt::recordAttempt('user@example.com', '192.168.1.1', null, true);
        $successfulAttempt->attempted_at = Carbon::now()->subDays(35);
        $successfulAttempt->save();

        $this->artisan('admin:cleanup-login-attempts')
            ->expectsOutput('Successfully deleted 2 old login attempt records.')
            ->assertExitCode(0);

        // Both old attempts should be deleted (command cleans up all old attempts, not just failed ones)
        $this->assertEquals(0, MemberLoginAttempt::count());
    }

    public function test_cleanup_command_works_with_large_dataset()
    {
        // Create a large number of old attempts
        for ($i = 0; $i < 1000; $i++) {
            $attempt = MemberLoginAttempt::recordAttempt("user{$i}@example.com", '192.168.1.1', null, false);
            $attempt->attempted_at = Carbon::now()->subDays(35);
            $attempt->save();
        }

        // Create some recent attempts
        for ($i = 0; $i < 100; $i++) {
            MemberLoginAttempt::recordAttempt("recent{$i}@example.com", '192.168.1.1', null, false);
        }

        $this->artisan('admin:cleanup-login-attempts')
            ->expectsOutput('Successfully deleted 1000 old login attempt records.')
            ->assertExitCode(0);

        // Only recent attempts should remain
        $this->assertEquals(100, MemberLoginAttempt::count());
    }
}
