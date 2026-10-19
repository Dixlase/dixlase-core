<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AbstractAction;
use App\Actors\MemberActor;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Core #546: AbstractAction::audit() passed the actor and the target as
 * column-named keys, which AuditLog::log() ignored, so every row the action
 * layer wrote had NULL actor_type / actor_id / target_type / target_id.
 */
class AbstractActionAuditColumnsTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = Member::factory()->create([
            'account_name' => 'auditor',
            'display_name' => 'Auditor',
            'email' => 'auditor@example.test',
            'role' => MemberRole::SUPER_ADMIN,
        ]);
    }

    private function action(ActionResult $result): AbstractAction
    {
        return new class($result) extends AbstractAction
        {
            public function __construct(private ActionResult $result) {}

            protected function handle(Actor $actor, array $data): ActionResult
            {
                return $this->result;
            }

            protected function requiredPermission(): ?Permission
            {
                return null;
            }

            protected function auditAction(): string
            {
                return 'test.action_audited';
            }
        };
    }

    private function auditRow(): AuditLog
    {
        return AuditLog::where('action', 'test.action_audited')->sole();
    }

    public function test_a_row_written_through_an_action_carries_the_actor_and_the_model_target(): void
    {
        $target = Member::factory()->create([
            'account_name' => 'target',
            'display_name' => 'Target',
            'email' => 'target@example.test',
        ]);

        $this->action(ActionResult::success($target, label: 'Target'))
            ->execute(new MemberActor($this->member), []);

        $row = $this->auditRow();

        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($this->member->id, (int) $row->actor_id);
        $this->assertSame('Auditor', $row->actor_name);
        $this->assertSame(Member::class, $row->target_type);
        $this->assertSame($target->id, (int) $row->target_id);
        $this->assertSame('Target', $row->target_label);
    }

    public function test_the_hash_chain_now_covers_the_actor_of_an_action_row(): void
    {
        $this->action(ActionResult::success($this->member))
            ->execute(new MemberActor($this->member), []);

        $row = $this->auditRow();
        $row->saveWithHashChain();

        $this->assertTrue($row->verifyHash());

        $row->actor_id = $this->member->id + 1;

        $this->assertFalse($row->verifyHash());
    }

    public function test_a_target_that_is_not_a_model_keeps_its_type_and_id(): void
    {
        $this->action(new ActionResult(
            success: true,
            targetType: 'remote_feed',
            targetId: 42,
            targetLabel: 'Feed 42',
        ))->execute(new MemberActor($this->member), []);

        $row = $this->auditRow();

        $this->assertSame($this->member->id, (int) $row->actor_id);
        $this->assertSame('remote_feed', $row->target_type);
        $this->assertSame(42, (int) $row->target_id);
    }

    public function test_log_honours_column_named_keys_given_without_a_model(): void
    {
        $row = AuditLog::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => 'test.action_audited',
            'actor_type' => Member::class,
            'actor_id' => $this->member->id,
            'target_type' => 'plugin',
            'target_id' => '7',
        ]);

        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($this->member->id, (int) $row->actor_id);
        $this->assertSame('plugin', $row->target_type);
        $this->assertSame(7, (int) $row->target_id);
    }

    public function test_a_model_wins_over_the_column_named_keys(): void
    {
        $row = AuditLog::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => 'test.action_audited',
            'actor' => $this->member,
            'actor_type' => 'something_else',
            'actor_id' => 999,
        ]);

        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($this->member->id, (int) $row->actor_id);
    }

    public function test_a_non_numeric_id_is_dropped_instead_of_losing_the_row(): void
    {
        $row = AuditLog::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => 'test.action_audited',
            'target_type' => 'remote_feed',
            'target_id' => 'feed-abc',
            'target_label' => 'feed-abc',
        ]);

        $this->assertSame('remote_feed', $row->target_type);
        $this->assertNull($row->target_id);
        $this->assertSame('feed-abc', $row->target_label);
    }
}
