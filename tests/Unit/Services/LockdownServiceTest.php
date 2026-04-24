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

namespace Tests\Unit\Services;

use App\Models\LockdownStatus;
use App\Models\Member;
use App\Services\LockdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockdownServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        LockdownService::deactivateAll();
        LockdownService::clearCache();
        parent::tearDown();
    }

    public function test_activate_creates_lockdown(): void
    {
        $member = Member::factory()->create();

        $status = LockdownService::activate(
            LockdownStatus::TYPE_FULL,
            'Test lockdown',
            $member->id
        );

        $this->assertInstanceOf(LockdownStatus::class, $status);
        $this->assertTrue($status->is_active);
        $this->assertEquals(LockdownStatus::TYPE_FULL, $status->type);
    }

    public function test_is_locked_returns_true_when_active(): void
    {
        $member = Member::factory()->create();
        LockdownService::activate(LockdownStatus::TYPE_FULL, 'Test', $member->id);

        $this->assertTrue(LockdownService::isLocked());
    }

    public function test_is_locked_returns_false_when_inactive(): void
    {
        $this->assertFalse(LockdownService::isLocked());
    }

    public function test_is_locked_checks_specific_type(): void
    {
        $member = Member::factory()->create();
        LockdownService::activate(LockdownStatus::TYPE_API, 'API lockdown', $member->id);

        $this->assertTrue(LockdownService::isLocked(LockdownStatus::TYPE_API));
        $this->assertFalse(LockdownService::isLocked(LockdownStatus::TYPE_ADMIN));
    }

    public function test_deactivate_releases_lockdown(): void
    {
        $member = Member::factory()->create();
        LockdownService::activate(LockdownStatus::TYPE_FULL, 'Test', $member->id);

        $this->assertTrue(LockdownService::isLocked());

        LockdownService::deactivate($member->id);

        LockdownService::clearCache();
        $this->assertFalse(LockdownService::isLocked());
    }

    public function test_is_access_allowed_for_allowed_ip(): void
    {
        $member = Member::factory()->create();
        LockdownService::activate(
            LockdownStatus::TYPE_FULL,
            'Test',
            $member->id,
            null,
            ['192.168.1.1']
        );

        $this->assertTrue(LockdownService::isAccessAllowed('192.168.1.1'));
        $this->assertFalse(LockdownService::isAccessAllowed('10.0.0.1'));
    }

    public function test_is_access_allowed_for_allowed_member(): void
    {
        $admin = Member::factory()->create();
        $allowedMember = Member::factory()->create();

        LockdownService::activate(
            LockdownStatus::TYPE_FULL,
            'Test',
            $admin->id,
            null,
            null,
            [$allowedMember->id]
        );

        $this->assertTrue(LockdownService::isAccessAllowed(null, $allowedMember));
        $this->assertFalse(LockdownService::isAccessAllowed(null, $admin));
    }

    public function test_auto_release_after_timeout(): void
    {
        $member = Member::factory()->create();
        LockdownService::activate(
            LockdownStatus::TYPE_FULL,
            'Test',
            $member->id,
            0 // 0分 = 即座に解除対象
        );

        // auto_release_at を過去に設定
        $status = LockdownService::getStatus();
        $status->update(['auto_release_at' => now()->subMinute()]);
        LockdownService::clearCache();

        $released = LockdownService::checkAutoRelease();

        $this->assertTrue($released);
        LockdownService::clearCache();
        $this->assertFalse(LockdownService::isLocked());
    }

    public function test_get_status_returns_null_when_no_lockdown(): void
    {
        $this->assertNull(LockdownService::getStatus());
    }
}
