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

namespace Tests\Unit\Actors;

use App\Actors\MemberActor;
use App\Models\Member;
use Tests\TestCase;

class MemberActorTest extends TestCase
{
    public function test_actor_id_returns_member_id(): void
    {
        $member = new Member();
        $member->id = 42;
        $member->account_name = 'testuser';

        $actor = new MemberActor($member);

        $this->assertSame(42, $actor->getActorId());
    }

    public function test_actor_type_is_member(): void
    {
        $member = new Member();
        $member->account_name = 'testuser';

        $actor = new MemberActor($member);

        $this->assertSame('member', $actor->getActorType());
    }

    public function test_actor_name_prefers_display_name(): void
    {
        $member = new Member();
        $member->account_name = 'testuser';
        $member->display_name = 'Test User';

        $actor = new MemberActor($member);

        $this->assertSame('Test User', $actor->getActorName());
    }

    public function test_actor_name_falls_back_to_account_name(): void
    {
        $member = new Member();
        $member->account_name = 'testuser';
        $member->display_name = null;

        $actor = new MemberActor($member);

        $this->assertSame('testuser', $actor->getActorName());
    }

    public function test_to_audit_morph_returns_member_model(): void
    {
        $member = new Member();
        $member->account_name = 'testuser';

        $actor = new MemberActor($member);

        $this->assertSame($member, $actor->toAuditMorph());
    }

    public function test_get_member_returns_underlying_model(): void
    {
        $member = new Member();
        $member->account_name = 'testuser';

        $actor = new MemberActor($member);

        $this->assertSame($member, $actor->getMember());
    }
}
