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

use App\Actions\Member\CreateMemberAction;
use App\Actors\SystemActor;
use App\Contracts\Action\Actor;
use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMemberActionTest extends TestCase
{
    use RefreshDatabase;

    private function validMemberData(array $overrides = []): array
    {
        return array_merge([
            'account_name' => 'testuser',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'role' => MemberRole::ADMIN->value,
            'status' => 1,
            'email_verified' => '1',
        ], $overrides);
    }

    public function test_creates_member_successfully(): void
    {
        Audit::shouldReceive('log')->once();

        $action = app(CreateMemberAction::class);
        $result = $action->execute(new SystemActor(), $this->validMemberData());

        $this->assertTrue($result->success);
        $this->assertInstanceOf(Member::class, $result->model);
        $this->assertSame('testuser', $result->model->account_name);
        $this->assertDatabaseHas('members', ['account_name' => 'testuser']);
    }

    public function test_hashes_password(): void
    {
        Audit::shouldReceive('log')->once();

        $action = app(CreateMemberAction::class);
        $result = $action->execute(new SystemActor(), $this->validMemberData());

        $this->assertNotSame('Password123!', $result->model->password);
        $this->assertTrue(password_verify('Password123!', $result->model->password));
    }

    public function test_sets_email_verified_at_when_verified(): void
    {
        Audit::shouldReceive('log')->once();

        $action = app(CreateMemberAction::class);
        $result = $action->execute(new SystemActor(), $this->validMemberData([
            'email_verified' => '1',
        ]));

        $this->assertNotNull($result->model->email_verified_at);
    }

    public function test_sets_target_label_from_display_name(): void
    {
        Audit::shouldReceive('log')->once();

        $action = app(CreateMemberAction::class);
        $result = $action->execute(new SystemActor(), $this->validMemberData([
            'display_name' => 'My Display Name',
        ]));

        $this->assertSame('My Display Name', $result->targetLabel);
    }

    public function test_throws_authorization_exception_without_permission(): void
    {
        $actor = $this->createMock(Actor::class);
        $actor->method('hasPermission')
            ->with(Permission::MEMBERS_CREATE)
            ->willReturn(false);
        $actor->method('getActorType')->willReturn('member');
        $actor->method('getActorName')->willReturn('limited');

        $this->expectException(AuthorizationException::class);

        app(CreateMemberAction::class)->execute($actor, $this->validMemberData());
    }
}
