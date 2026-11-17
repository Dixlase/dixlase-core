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

namespace Tests\Feature\Console\Commands;

use App\Enums\ActorType;
use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Enums\PermissionGrantType;
use App\Models\CustomRole;
use App\Models\CustomRolePermissionOverride;
use App\Models\Member;
use App\Services\CustomRolePermissionResolver;
use App\Services\PermissionRegistry;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * dls:role:create / :override / :assign / :list (#912).
 */
class RoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const PLUGIN = 'FixtureRoleCommandPlugin';

    protected function setUp(): void
    {
        parent::setUp();

        PermissionRegistry::registerPlugin(self::PLUGIN, [
            'things.publish' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
        ]);
    }

    protected function tearDown(): void
    {
        PermissionRegistry::unregisterPlugin(self::PLUGIN);

        parent::tearDown();
    }

    public function test_create_override_and_assign_end_to_end(): void
    {
        $this->artisan('dls:role:create', [
            'slug' => 'writer',
            '--name' => 'Writer',
            '--base' => 'editor',
            '--actor-type' => 'ai',
        ])->assertSuccessful();

        $role = CustomRole::where('slug', 'writer')->firstOrFail();
        $this->assertSame(MemberRole::EDITOR, $role->base_role);
        $this->assertSame(ActorType::Ai, $role->actor_type);
        $this->assertTrue($role->is_active);

        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'media.delete', 'type' => 'deny'])
            ->assertSuccessful();
        $this->artisan('dls:role:override', [
            'slug' => 'writer', 'permission' => 'things.publish', 'type' => 'deny', '--plugin' => self::PLUGIN,
        ])->assertSuccessful();

        $member = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);
        $this->artisan('dls:role:assign', ['slug' => 'writer', '--member' => (string) $member->id])
            ->assertSuccessful();

        $member = $member->fresh();
        $this->assertSame($role->id, $member->custom_role_id);
        $this->assertSame(MemberRole::EDITOR, $member->role);
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_UPLOAD));
        $this->assertFalse(PermissionRegistry::canAccessPlugin(self::PLUGIN, 'things.publish', $member->role, $member));

        $this->artisan('dls:role:list')->assertSuccessful()->expectsOutputToContain('writer');
        $this->artisan('dls:role:list', ['slug' => 'writer'])->assertSuccessful();
    }

    public function test_clear_removes_the_override(): void
    {
        $role = CustomRole::create(['slug' => 'writer', 'name' => ['en' => 'Writer'], 'base_role' => MemberRole::EDITOR]);
        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'media.delete', 'type' => 'deny'])
            ->assertSuccessful();
        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'media.delete', 'type' => 'grant'])
            ->assertSuccessful();

        $this->assertSame(
            PermissionGrantType::Grant,
            CustomRolePermissionOverride::where('custom_role_id', $role->id)->sole()->grant_type,
        );

        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'media.delete', 'type' => 'clear'])
            ->assertSuccessful();

        $this->assertSame(0, CustomRolePermissionOverride::where('custom_role_id', $role->id)->count());
    }

    public function test_remove_keeps_the_preset(): void
    {
        $role = CustomRole::create(['slug' => 'writer', 'name' => ['en' => 'Writer'], 'base_role' => MemberRole::EDITOR]);
        $member = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);
        $role->assignTo($member);

        $this->artisan('dls:role:assign', ['--member' => $member->email, '--remove' => true])->assertSuccessful();

        $member = $member->fresh();
        $this->assertNull($member->custom_role_id);
        $this->assertSame(MemberRole::EDITOR, $member->role);
        $this->assertNull(CustomRolePermissionResolver::activeRole($member));
    }

    public function test_super_admin_base_is_refused(): void
    {
        $this->artisan('dls:role:create', ['slug' => 'root', '--base' => 'super_admin'])->assertFailed();
        $this->artisan('dls:role:create', ['slug' => 'root', '--base' => '10'])->assertFailed();

        $this->assertSame(0, CustomRole::count());
    }

    public function test_assigning_to_a_super_admin_is_refused(): void
    {
        CustomRole::create(['slug' => 'writer', 'name' => ['en' => 'Writer'], 'base_role' => MemberRole::EDITOR]);
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $this->artisan('dls:role:assign', ['slug' => 'writer', '--member' => (string) $member->id])->assertFailed();

        $this->assertSame(MemberRole::SUPER_ADMIN, $member->fresh()->role);
    }

    public function test_unknown_keys_are_refused(): void
    {
        CustomRole::create(['slug' => 'writer', 'name' => ['en' => 'Writer'], 'base_role' => MemberRole::EDITOR]);

        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'no.such', 'type' => 'deny'])
            ->assertFailed();
        $this->artisan('dls:role:override', [
            'slug' => 'writer', 'permission' => 'undeclared', 'type' => 'deny', '--plugin' => self::PLUGIN,
        ])->assertFailed();
        $this->artisan('dls:role:override', ['slug' => 'writer', 'permission' => 'media.delete', 'type' => 'maybe'])
            ->assertFailed();

        $this->assertSame(0, CustomRolePermissionOverride::count());
    }
}
