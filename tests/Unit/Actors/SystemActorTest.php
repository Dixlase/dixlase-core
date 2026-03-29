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

use App\Actors\SystemActor;
use App\Enums\MemberRole;
use App\Enums\Permission;
use Tests\TestCase;

class SystemActorTest extends TestCase
{
    public function test_actor_id_is_null(): void
    {
        $actor = new SystemActor();

        $this->assertNull($actor->getActorId());
    }

    public function test_actor_type_is_system(): void
    {
        $actor = new SystemActor();

        $this->assertSame('system', $actor->getActorType());
    }

    public function test_default_name_is_system(): void
    {
        $actor = new SystemActor();

        $this->assertSame('system', $actor->getActorName());
    }

    public function test_custom_process_name(): void
    {
        $actor = new SystemActor('scheduler');

        $this->assertSame('scheduler', $actor->getActorName());
    }

    public function test_to_audit_morph_is_null(): void
    {
        $actor = new SystemActor();

        $this->assertNull($actor->toAuditMorph());
    }

    public function test_has_all_permissions(): void
    {
        $actor = new SystemActor();

        $this->assertTrue($actor->hasPermission(Permission::MEMBERS_CREATE));
        $this->assertTrue($actor->hasPermission(Permission::SETTINGS_SECURITY));
    }

    public function test_has_all_roles(): void
    {
        $actor = new SystemActor();

        $this->assertTrue($actor->hasRole(MemberRole::SUPER_ADMIN));
        $this->assertTrue($actor->hasRole(MemberRole::GUEST));
    }
}
