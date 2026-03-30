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

use App\Actions\Member\DeleteMemberAction;
use App\Actors\SystemActor;
use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMemberActionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'account_name' => 'deleteme',
            'display_name' => 'Delete Me',
            'email' => 'delete@example.com',
            'password' => bcrypt('Password123!'),
            'role' => MemberRole::ADMIN->value,
            'status' => 1,
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_deletes_member_successfully(): void
    {
        Audit::shouldReceive('log')->once();

        // Create a placeholder for ID=1 (initial admin), then the actual target
        $this->createMember(['account_name' => 'admin1', 'email' => 'admin@example.com']);
        $member = $this->createMember();
        $memberId = $member->id;
        $action = new DeleteMemberAction($member);

        $result = $action->execute(new SystemActor(), []);

        $this->assertTrue($result->success);
        $this->assertSoftDeleted('members', ['id' => $memberId]);
    }

    public function test_prevents_deletion_of_initial_admin(): void
    {
        Audit::shouldReceive('log')->never();

        // First member gets ID=1 automatically
        $member = $this->createMember(['account_name' => 'admin1', 'email' => 'admin@example.com']);

        $action = new DeleteMemberAction($member);
        $result = $action->execute(new SystemActor(), []);

        $this->assertFalse($result->success);
        $this->assertSame('Cannot delete the initial admin account.', $result->message);
        $this->assertDatabaseHas('members', ['id' => 1]);
    }

    public function test_returns_correct_target_info(): void
    {
        Audit::shouldReceive('log')->once();

        // Create placeholder for ID=1, then the actual target
        $this->createMember(['account_name' => 'admin1', 'email' => 'admin@example.com']);
        $member = $this->createMember(['display_name' => 'Target User']);
        $action = new DeleteMemberAction($member);

        $result = $action->execute(new SystemActor(), []);

        $this->assertSame(Member::class, $result->targetType);
        $this->assertSame('Target User', $result->targetLabel);
    }
}
