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

namespace Tests\Unit\Services;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Models\Member;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(MemberRole $role): Member
    {
        $member = Member::factory()->create(['role' => $role]);
        $this->actingAs($member, 'member');

        return $member;
    }

    // =========================================================================
    // hasRole
    // =========================================================================

    public function test_super_admin_has_all_roles(): void
    {
        $this->actAs(MemberRole::SUPER_ADMIN);

        $this->assertTrue(PermissionService::hasRole(MemberRole::SUPER_ADMIN));
        $this->assertTrue(PermissionService::hasRole(MemberRole::ADMIN));
        $this->assertTrue(PermissionService::hasRole(MemberRole::EDITOR));
        $this->assertTrue(PermissionService::hasRole(MemberRole::GUEST));
    }

    public function test_admin_lacks_super_admin_role(): void
    {
        $this->actAs(MemberRole::ADMIN);

        $this->assertFalse(PermissionService::hasRole(MemberRole::SUPER_ADMIN));
        $this->assertTrue(PermissionService::hasRole(MemberRole::ADMIN));
    }

    public function test_guest_has_only_guest_role(): void
    {
        $this->actAs(MemberRole::GUEST);

        $this->assertTrue(PermissionService::hasRole(MemberRole::GUEST));
        $this->assertFalse(PermissionService::hasRole(MemberRole::EDITOR));
    }

    // =========================================================================
    // isSuperAdmin / isAdmin
    // =========================================================================

    public function test_is_super_admin(): void
    {
        $this->actAs(MemberRole::SUPER_ADMIN);
        $this->assertTrue(PermissionService::isSuperAdmin());
    }

    public function test_admin_is_not_super_admin(): void
    {
        $this->actAs(MemberRole::ADMIN);
        $this->assertFalse(PermissionService::isSuperAdmin());
    }

    public function test_is_admin(): void
    {
        $this->actAs(MemberRole::ADMIN);
        $this->assertTrue(PermissionService::isAdmin());
    }

    public function test_editor_is_not_admin(): void
    {
        $this->actAs(MemberRole::EDITOR);
        $this->assertFalse(PermissionService::isAdmin());
    }

    // =========================================================================
    // can / canAny
    // =========================================================================

    public function test_super_admin_can_do_everything(): void
    {
        $this->actAs(MemberRole::SUPER_ADMIN);

        $this->assertTrue(PermissionService::can(Permission::SETTINGS_SECURITY));
        $this->assertTrue(PermissionService::can(Permission::MEMBERS_DELETE));
        $this->assertTrue(PermissionService::can(Permission::DASHBOARD_VIEW));
    }

    public function test_editor_cannot_access_security_settings(): void
    {
        $this->actAs(MemberRole::EDITOR);

        $this->assertFalse(PermissionService::can(Permission::SETTINGS_SECURITY));
    }

    public function test_can_any_returns_true_if_one_matches(): void
    {
        $this->actAs(MemberRole::EDITOR);

        $this->assertTrue(PermissionService::canAny([
            Permission::SETTINGS_SECURITY,  // エディターには不可
            Permission::DASHBOARD_VIEW,      // エディターに可能
        ]));
    }

    public function test_can_any_returns_false_if_none_match(): void
    {
        $this->actAs(MemberRole::GUEST);

        $this->assertFalse(PermissionService::canAny([
            Permission::SETTINGS_SECURITY,
            Permission::MEMBERS_DELETE,
        ]));
    }

    // =========================================================================
    // getPermissions
    // =========================================================================

    public function test_get_permissions_returns_array(): void
    {
        $this->actAs(MemberRole::ADMIN);

        $permissions = PermissionService::getPermissions();

        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
    }

    public function test_super_admin_has_more_permissions_than_admin(): void
    {
        $this->actAs(MemberRole::SUPER_ADMIN);
        $superAdminPerms = count(PermissionService::getPermissions());

        PermissionService::clearAllCache();
        $this->actAs(MemberRole::ADMIN);
        $adminPerms = count(PermissionService::getPermissions());

        $this->assertGreaterThan($adminPerms, $superAdminPerms);
    }

    // =========================================================================
    // memberCan / memberHasRole
    // =========================================================================

    public function test_member_can_checks_specific_member(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $this->assertTrue(PermissionService::memberCan($superAdmin, Permission::SETTINGS_SECURITY));
        $this->assertFalse(PermissionService::memberCan($guest, Permission::SETTINGS_SECURITY));
    }

    public function test_member_has_role_checks_specific_member(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $this->assertTrue(PermissionService::memberHasRole($admin, MemberRole::EDITOR));
        $this->assertFalse(PermissionService::memberHasRole($admin, MemberRole::SUPER_ADMIN));
    }
}
