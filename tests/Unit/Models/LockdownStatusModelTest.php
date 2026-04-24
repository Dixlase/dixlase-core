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

namespace Tests\Unit\Models;

use App\Models\LockdownStatus;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockdownStatusModelTest extends TestCase
{
    use RefreshDatabase;

    private function createLockdown(string $type = LockdownStatus::TYPE_FULL, array $overrides = []): LockdownStatus
    {
        $member = Member::factory()->create(['role' => \App\Enums\MemberRole::SUPER_ADMIN]);

        return LockdownStatus::create(array_merge([
            'type' => $type,
            'is_active' => true,
            'reason' => 'Test lockdown',
            'triggered_by' => $member->id,
            'triggered_at' => now(),
        ], $overrides));
    }

    public function test_lockdown_can_be_created(): void
    {
        $lockdown = $this->createLockdown();

        $this->assertDatabaseHas('lockdown_status', [
            'id' => $lockdown->id,
            'is_active' => true,
        ]);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $lockdown = $this->createLockdown();

        $this->assertIsBool($lockdown->is_active);
        $this->assertTrue($lockdown->is_active);
    }

    public function test_allowed_ips_cast_to_array(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
        ]);

        $lockdown->refresh();
        $this->assertIsArray($lockdown->allowed_ips);
        $this->assertCount(2, $lockdown->allowed_ips);
    }

    public function test_allowed_members_cast_to_array(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'allowed_members' => [1, 2, 3],
        ]);

        $lockdown->refresh();
        $this->assertIsArray($lockdown->allowed_members);
        $this->assertCount(3, $lockdown->allowed_members);
    }

    public function test_is_ip_allowed(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'allowed_ips' => ['192.168.1.1'],
        ]);

        $this->assertTrue($lockdown->isIpAllowed('192.168.1.1'));
        $this->assertFalse($lockdown->isIpAllowed('10.0.0.1'));
    }

    public function test_is_member_allowed(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'allowed_members' => [42],
        ]);

        $this->assertTrue($lockdown->isMemberAllowed(42));
        $this->assertFalse($lockdown->isMemberAllowed(99));
    }

    public function test_should_auto_release(): void
    {
        // 過去の自動解除時刻 → 解除すべき
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'auto_release_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($lockdown->shouldAutoRelease());
    }

    public function test_should_not_auto_release_when_future(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'auto_release_at' => now()->addHour(),
        ]);

        $this->assertFalse($lockdown->shouldAutoRelease());
    }

    public function test_should_not_auto_release_when_null(): void
    {
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'auto_release_at' => null,
        ]);

        $this->assertFalse($lockdown->shouldAutoRelease());
    }

    public function test_triggered_by_member_relationship(): void
    {
        $lockdown = $this->createLockdown();

        $this->assertNotNull($lockdown->triggeredByMember);
        $this->assertInstanceOf(Member::class, $lockdown->triggeredByMember);
    }

    public function test_type_constants_exist(): void
    {
        $this->assertEquals('full', LockdownStatus::TYPE_FULL);
        $this->assertEquals('admin', LockdownStatus::TYPE_ADMIN);
        $this->assertEquals('api', LockdownStatus::TYPE_API);
        $this->assertEquals('login', LockdownStatus::TYPE_LOGIN);
    }

    public function test_metadata_cast_to_array(): void
    {
        $metadata = ['initiated_from' => 'dashboard', 'severity' => 'critical'];
        $lockdown = $this->createLockdown(LockdownStatus::TYPE_FULL, [
            'metadata' => $metadata,
        ]);

        $lockdown->refresh();
        $this->assertIsArray($lockdown->metadata);
        $this->assertEquals($metadata, $lockdown->metadata);
    }
}
