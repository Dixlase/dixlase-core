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

namespace Tests\Unit\Services\TwoFa;

use App\Models\Member;
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFaAttemptServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFaAttemptService $service;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TwoFaAttemptService::class);
        $this->member = Member::factory()->create();

        SecuritySetting::setValue('two_fa_max_attempts', 3);
        SecuritySetting::setValue('two_fa_attempt_window', 15);
        SecuritySetting::setValue('two_fa_lockout_duration', 30);
    }

    public function test_record_attempt_creates_entry(): void
    {
        $this->service->recordAttempt($this->member, 'email', false);

        $this->assertDatabaseHas('members_two_fa_attempts', [
            'member_id' => $this->member->id,
            'attempt_type' => 'email',
            'successful' => false,
        ]);
    }

    public function test_has_reached_max_attempts(): void
    {
        $this->assertFalse($this->service->hasReachedMaxAttempts($this->member));

        for ($i = 0; $i < 3; $i++) {
            $this->service->recordAttempt($this->member, 'email', false);
        }

        $this->assertTrue($this->service->hasReachedMaxAttempts($this->member));
    }

    public function test_get_remaining_attempts(): void
    {
        $this->assertEquals(3, $this->service->getRemainingAttempts($this->member));

        $this->service->recordAttempt($this->member, 'email', false);

        $this->assertEquals(2, $this->service->getRemainingAttempts($this->member));
    }

    public function test_lockout_triggers_at_max_attempts(): void
    {
        // max_attempts=3 に設定済み
        for ($i = 0; $i < 3; $i++) {
            $this->service->recordAttempt($this->member, 'email', false);
        }

        $this->assertTrue($this->service->hasReachedMaxAttempts($this->member));
        $this->assertEquals(0, $this->service->getRemainingAttempts($this->member));
    }

    public function test_attempts_are_isolated_per_member(): void
    {
        $otherMember = Member::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->service->recordAttempt($this->member, 'email', false);
        }

        $this->assertTrue($this->service->hasReachedMaxAttempts($this->member));
        $this->assertFalse($this->service->hasReachedMaxAttempts($otherMember));
    }

    public function test_is_lockout_notification_enabled(): void
    {
        SecuritySetting::setValue('two_fa_lockout_notification_enabled', true);

        $this->assertTrue($this->service->isLockoutNotificationEnabled());
    }

    /**
     * Regression: the model's fillable list once said `success` while the
     * column (and the service) say `successful`, so every attempt was stored
     * with the column default (false) and five ordinary logins locked the
     * account out.
     */
    public function test_successful_attempt_is_stored_as_successful(): void
    {
        $this->service->recordAttempt($this->member, 'email', true);

        $this->assertDatabaseHas('members_two_fa_attempts', [
            'member_id' => $this->member->id,
            'attempt_type' => 'email',
            'successful' => true,
        ]);
        $this->assertTrue((bool) $this->member->twoFaAttempts()->first()->successful);
    }

    public function test_successful_attempts_never_lock_out(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordAttempt($this->member, 'email', true);
        }

        $this->assertFalse($this->service->hasReachedMaxAttempts($this->member));
        $this->assertFalse($this->service->isLockedOut($this->member));
        $this->assertEquals(3, $this->service->getRemainingAttempts($this->member));
    }

    public function test_failed_attempts_lock_out_and_report_remaining_minutes(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->recordAttempt($this->member, 'email', false);
        }

        $this->assertTrue($this->service->isLockedOut($this->member));
        $this->assertGreaterThan(0, $this->service->getRemainingLockoutTime($this->member));
    }

    public function test_model_failed_count_helpers_use_the_successful_column(): void
    {
        $this->service->recordAttempt($this->member, 'email', false);
        $this->service->recordAttempt($this->member, 'email', true);

        $this->assertSame(1, \App\Models\MemberTwoFaAttempt::getFailedAttemptsCount($this->member->id, 15));
        $this->assertSame(1, \App\Models\MemberTwoFaAttempt::getFailedAttemptsByIpCount((string) request()->ip(), 15));
    }
}
