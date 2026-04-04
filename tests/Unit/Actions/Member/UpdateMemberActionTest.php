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

namespace Tests\Unit\Actions\Member;

use App\Actions\Member\UpdateMemberAction;
use App\Actors\SystemActor;
use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMemberActionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'account_name' => 'existing',
            'display_name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('OldPassword123!'),
            'role' => MemberRole::ADMIN->value,
            'status' => 1,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_updates_member_fields(): void
    {
        Audit::shouldReceive('log')->once();
        Audit::shouldReceive('diff')->once()->andReturn([]);

        $member = $this->createMember();
        $action = new UpdateMemberAction($member);

        $result = $action->execute(new SystemActor(), [
            'display_name' => 'Updated Name',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame('Updated Name', $member->fresh()->display_name);
    }

    public function test_hashes_password_when_provided(): void
    {
        Audit::shouldReceive('log')->once();
        Audit::shouldReceive('diff')->once()->andReturn([]);

        $member = $this->createMember();
        $action = new UpdateMemberAction($member);

        $result = $action->execute(new SystemActor(), [
            'password' => 'NewPassword456!',
        ]);

        $this->assertTrue($result->success);
        $this->assertTrue(password_verify('NewPassword456!', $member->fresh()->password));
    }

    public function test_ignores_empty_password(): void
    {
        Audit::shouldReceive('log')->once();
        Audit::shouldReceive('diff')->once()->andReturn([]);

        $member = $this->createMember();
        $originalPassword = $member->password;
        $action = new UpdateMemberAction($member);

        $result = $action->execute(new SystemActor(), [
            'display_name' => 'No Password Change',
            'password' => '',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame($originalPassword, $member->fresh()->password);
    }

    public function test_returns_before_state_in_metadata(): void
    {
        Audit::shouldReceive('log')->once();
        Audit::shouldReceive('diff')->once()->andReturn([]);

        $member = $this->createMember();
        $action = new UpdateMemberAction($member);

        $result = $action->execute(new SystemActor(), [
            'display_name' => 'Changed',
        ]);

        $this->assertArrayHasKey('before', $result->metadata);
        $this->assertSame('Existing User', $result->metadata['before']['display_name']);
    }
}
