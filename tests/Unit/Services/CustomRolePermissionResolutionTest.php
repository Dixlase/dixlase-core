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

namespace Tests\Unit\Services;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Enums\PermissionGrantType;
use App\Helpers\AdminHelper;
use App\Models\CustomRole;
use App\Models\CustomRolePermissionOverride;
use App\Models\Member;
use App\Models\Site;
use App\Services\CustomRolePermissionResolver;
use App\Services\PermissionRegistry;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * A custom role is its base preset plus per-site overrides, with deny
 * winning and Super Admin bypassing everything (#912).
 *
 * The base role is an Editor throughout: `media.delete` is an Editor
 * permission the preset allows (so a deny is observable), `members.create`
 * is an Admin permission the preset refuses (so a grant is observable).
 */
class CustomRolePermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    /** A directory name with no plugins/ directory behind it. */
    private const PLUGIN = 'FixtureCustomRolePlugin';

    protected function setUp(): void
    {
        parent::setUp();

        PermissionRegistry::registerPlugin(self::PLUGIN, [
            'things' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.publish' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.settings' => ['access_roles' => MemberRole::ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
        ]);
    }

    protected function tearDown(): void
    {
        PermissionRegistry::unregisterPlugin(self::PLUGIN);

        parent::tearDown();
    }

    private function role(array $attributes = []): CustomRole
    {
        return CustomRole::create($attributes + [
            'slug' => 'writer',
            'name' => ['en' => 'Writer'],
            'base_role' => MemberRole::EDITOR,
        ]);
    }

    private function override(CustomRole $role, string $key, PermissionGrantType $type, ?int $siteId = null): void
    {
        CustomRolePermissionOverride::create([
            'site_id' => $siteId ?? 1,
            'custom_role_id' => $role->id,
            'permission' => $key,
            'grant_type' => $type,
        ]);
    }

    private function memberWith(CustomRole $role, MemberRole $preset = MemberRole::CONTRIBUTOR): Member
    {
        $member = Member::factory()->create(['role' => $preset]);
        $role->assignTo($member);

        return $member->fresh();
    }

    // =========================================================================
    // Core permissions
    // =========================================================================

    public function test_member_without_custom_role_keeps_preset_behaviour(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEMBERS_CREATE));
        $this->assertSame(Permission::forRole(MemberRole::EDITOR), PermissionService::getMemberPermissions($member));
    }

    public function test_custom_role_without_overrides_behaves_as_its_base(): void
    {
        $member = $this->memberWith($this->role());

        $this->assertSame(MemberRole::EDITOR, $member->role);
        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEMBERS_CREATE));
    }

    public function test_grant_widens_the_base_role(): void
    {
        $role = $this->role();
        $this->override($role, Permission::MEMBERS_CREATE->value, PermissionGrantType::Grant);
        $member = $this->memberWith($role);

        $this->assertTrue(PermissionService::memberCan($member, Permission::MEMBERS_CREATE));
        $this->assertTrue($member->hasPermission(Permission::MEMBERS_CREATE));
        $this->assertContains(Permission::MEMBERS_CREATE, PermissionService::getMemberPermissions($member));
    }

    public function test_deny_narrows_the_base_role(): void
    {
        $role = $this->role();
        $this->override($role, Permission::MEDIA_DELETE->value, PermissionGrantType::Deny);
        $member = $this->memberWith($role);

        $this->assertFalse(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
        $this->assertNotContains(Permission::MEDIA_DELETE, PermissionService::getMemberPermissions($member));
        // Other preset permissions are untouched.
        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_UPLOAD));
    }

    public function test_inactive_role_falls_back_to_its_base(): void
    {
        $role = $this->role(['is_active' => false]);
        $this->override($role, Permission::MEDIA_DELETE->value, PermissionGrantType::Deny);
        $this->override($role, Permission::MEMBERS_CREATE->value, PermissionGrantType::Grant);
        $member = $this->memberWith($role);

        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEMBERS_CREATE));
    }

    public function test_deleted_role_falls_back_to_its_base(): void
    {
        $role = $this->role();
        $this->override($role, Permission::MEMBERS_CREATE->value, PermissionGrantType::Grant);
        $member = $this->memberWith($role);
        $role->delete();

        $member = $member->fresh();
        $this->assertNull(CustomRolePermissionResolver::activeRole($member));
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEMBERS_CREATE));
        $this->assertSame(MemberRole::EDITOR, $member->role);
    }

    public function test_override_on_another_site_does_not_apply(): void
    {
        $other = Site::factory()->create();
        $role = $this->role();
        $this->override($role, Permission::MEDIA_DELETE->value, PermissionGrantType::Deny, $other->id);
        $member = $this->memberWith($role);

        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
    }

    public function test_changing_an_override_takes_effect_despite_the_cache(): void
    {
        $role = $this->role();
        $member = $this->memberWith($role);
        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));

        $this->override($role, Permission::MEDIA_DELETE->value, PermissionGrantType::Deny);
        $this->assertFalse(PermissionService::memberCan($member, Permission::MEDIA_DELETE));

        CustomRolePermissionOverride::where('custom_role_id', $role->id)->first()->delete();
        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
    }

    // =========================================================================
    // Super Admin
    // =========================================================================

    public function test_super_admin_bypasses_every_check(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $this->assertTrue(PermissionService::memberCan($member, Permission::SYSTEM_RESTORE));
        $this->assertTrue(PermissionRegistry::canAccessPlugin(self::PLUGIN, 'things.settings', MemberRole::SUPER_ADMIN, $member));
    }

    public function test_custom_role_cannot_have_a_super_admin_base(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->role(['base_role' => MemberRole::SUPER_ADMIN]);
    }

    public function test_super_admin_cannot_be_given_a_custom_role(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        try {
            $this->role()->assignTo($member);
            $this->fail('Assigning a custom role to a Super Admin should be refused.');
        } catch (InvalidArgumentException) {
            $member = $member->fresh();
            $this->assertSame(MemberRole::SUPER_ADMIN, $member->role);
            $this->assertNull($member->custom_role_id);
        }
    }

    // =========================================================================
    // Keeping members.role equal to the base role
    // =========================================================================

    public function test_changing_the_base_role_updates_its_members(): void
    {
        $role = $this->role();
        $member = $this->memberWith($role);
        $bystander = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $role->update(['base_role' => MemberRole::CONTRIBUTOR]);

        $this->assertSame(MemberRole::CONTRIBUTOR, $member->fresh()->role);
        $this->assertSame(MemberRole::EDITOR, $bystander->fresh()->role);
    }

    public function test_member_limit_is_enforced(): void
    {
        $role = $this->role(['max_members' => 1]);
        $this->memberWith($role);

        $this->expectException(InvalidArgumentException::class);
        $this->memberWith($role);
    }

    // =========================================================================
    // Plugin menu keys
    // =========================================================================

    public function test_plugin_deny_refuses_access_and_view(): void
    {
        $role = $this->role();
        $this->override($role, CustomRolePermissionResolver::pluginKey(self::PLUGIN, 'things.publish'), PermissionGrantType::Deny);
        $member = $this->memberWith($role);
        $this->actingAs($member);

        $this->assertFalse(PermissionRegistry::canAccessPlugin(self::PLUGIN, 'things.publish', $member->role, $member));
        $this->assertFalse(AdminHelper::canAccessPluginMenu(self::PLUGIN, 'things.publish'));
        $this->assertFalse(AdminHelper::canEditPluginMenu(self::PLUGIN, 'things.publish'));
        $this->assertFalse(AdminHelper::canViewPluginMenu(self::PLUGIN, 'things.publish'));
        // A sibling key is untouched.
        $this->assertTrue(AdminHelper::canEditPluginMenu(self::PLUGIN, 'things'));
    }

    public function test_plugin_grant_widens_access(): void
    {
        $role = $this->role();
        $this->override($role, CustomRolePermissionResolver::pluginKey(self::PLUGIN, 'things.settings'), PermissionGrantType::Grant);
        $member = $this->memberWith($role);
        $this->actingAs($member);

        $this->assertTrue(AdminHelper::canEditPluginMenu(self::PLUGIN, 'things.settings'));
        // Without the member, the registry judges the preset alone.
        $this->assertFalse(PermissionRegistry::canAccessPlugin(self::PLUGIN, 'things.settings', $member->role));
    }

    public function test_plugin_deny_on_an_ancestor_wins_over_a_grant_on_the_key(): void
    {
        $role = $this->role();
        $this->override($role, CustomRolePermissionResolver::pluginKey(self::PLUGIN, 'things'), PermissionGrantType::Deny);
        $this->override($role, CustomRolePermissionResolver::pluginKey(self::PLUGIN, 'things.settings'), PermissionGrantType::Grant);
        $member = $this->memberWith($role);

        $this->assertSame(
            PermissionGrantType::Deny,
            CustomRolePermissionResolver::pluginOverrideFor($member, self::PLUGIN, 'things.settings'),
        );
        $this->assertFalse(PermissionRegistry::canAccessPlugin(self::PLUGIN, 'things.settings', $member->role, $member));
    }

    public function test_plugin_key_never_matches_a_core_permission_of_the_same_spelling(): void
    {
        $role = $this->role();
        // A plugin menu key spelled like a core permission.
        $this->override($role, CustomRolePermissionResolver::pluginKey(self::PLUGIN, 'media.delete'), PermissionGrantType::Deny);
        $member = $this->memberWith($role);

        $this->assertTrue(PermissionService::memberCan($member, Permission::MEDIA_DELETE));
    }
}
