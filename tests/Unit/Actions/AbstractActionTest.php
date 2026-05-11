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

namespace Tests\Unit\Actions;

use App\Actions\AbstractAction;
use App\Actors\SystemActor;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AbstractActionTest extends TestCase
{
    public function test_execute_calls_handle_and_returns_result(): void
    {
        Audit::shouldReceive('log')->once();

        $action = new class extends AbstractAction
        {
            protected function handle(Actor $actor, array $data): ActionResult
            {
                return new ActionResult(
                    success: true,
                    message: 'done',
                    targetType: 'App\\Models\\Member',
                    targetId: 1,
                    targetLabel: 'test',
                );
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.executed';
            }
        };

        $result = $action->execute(new SystemActor(), ['key' => 'value']);

        $this->assertTrue($result->success);
        $this->assertSame('done', $result->message);
    }

    public function test_execute_throws_when_actor_lacks_permission(): void
    {
        $actor = $this->createMock(Actor::class);
        $actor->method('hasPermission')->willReturn(false);
        $actor->method('getActorType')->willReturn('member');
        $actor->method('getActorName')->willReturn('limited-user');

        $action = new class extends AbstractAction
        {
            protected function handle(Actor $actor, array $data): ActionResult
            {
                return ActionResult::failure('should not reach');
            }

            protected function requiredPermission(): ?Permission
            {
                return Permission::MEMBERS_CREATE;
            }

            protected function auditAction(): string
            {
                return 'member.created';
            }
        };

        $this->expectException(AuthorizationException::class);

        $action->execute($actor, []);
    }

    public function test_execute_skips_audit_on_failure(): void
    {
        Audit::shouldReceive('log')->never();

        $action = new class extends AbstractAction
        {
            protected function handle(Actor $actor, array $data): ActionResult
            {
                return ActionResult::failure('failed');
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.failed';
            }
        };

        $result = $action->execute(new SystemActor(), []);

        $this->assertFalse($result->success);
    }

    public function test_validate_is_called_between_authorize_and_handle(): void
    {
        Audit::shouldReceive('log')->once();

        $log = [];

        $action = new class($log) extends AbstractAction
        {
            public function __construct(public array &$log) {}

            protected function authorize(Actor $actor, array $data): void
            {
                $this->log[] = 'authorize';
                parent::authorize($actor, $data);
            }

            protected function validate(Actor $actor, array $data): void
            {
                $this->log[] = 'validate';
            }

            protected function handle(Actor $actor, array $data): ActionResult
            {
                $this->log[] = 'handle';

                return new ActionResult(
                    success: true,
                    targetType: 'App\\Models\\Member',
                    targetId: 1,
                );
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.lifecycle';
            }
        };

        $action->execute(new SystemActor(), []);

        $this->assertSame(['authorize', 'validate', 'handle'], $log);
    }

    public function test_execute_aborts_when_validate_throws(): void
    {
        Audit::shouldReceive('log')->never();

        $action = new class extends AbstractAction
        {
            protected function validate(Actor $actor, array $data): void
            {
                throw ValidationException::withMessages([
                    'field' => 'business rule violated',
                ]);
            }

            protected function handle(Actor $actor, array $data): ActionResult
            {
                return ActionResult::failure('should not reach');
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.validate_fails';
            }
        };

        $this->expectException(ValidationException::class);

        $action->execute(new SystemActor(), []);
    }

    public function test_default_validate_is_noop(): void
    {
        Audit::shouldReceive('log')->once();

        $action = new class extends AbstractAction
        {
            protected function handle(Actor $actor, array $data): ActionResult
            {
                return new ActionResult(
                    success: true,
                    targetType: 'App\\Models\\Member',
                    targetId: 1,
                );
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.default_validate';
            }
        };

        $result = $action->execute(new SystemActor(), []);

        $this->assertTrue($result->success);
    }

    public function test_execute_allows_null_permission(): void
    {
        Audit::shouldReceive('log')->once();

        $actor = $this->createMock(Actor::class);
        $actor->method('hasPermission')->willReturn(false);
        $actor->method('toAuditMorph')->willReturn(null);
        $actor->method('getActorId')->willReturn(null);
        $actor->method('getActorName')->willReturn('test');

        $action = new class extends AbstractAction
        {
            protected function handle(Actor $actor, array $data): ActionResult
            {
                return new ActionResult(
                    success: true,
                    targetType: 'App\\Models\\Member',
                    targetId: 1,
                );
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.no_perm';
            }
        };

        $result = $action->execute($actor, []);

        $this->assertTrue($result->success);
    }
}
