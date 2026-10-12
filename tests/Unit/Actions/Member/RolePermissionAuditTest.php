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

namespace Tests\Unit\Actions\Member;

use App\Actions\Member\ResetRolePermissionAction;
use App\Actions\Member\UpdateRolePermissionsAction;
use App\Actors\MemberActor;
use App\Enums\MemberRole;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Core #474: the role / permission matrix used to change with no audit row.
 *
 * `members.index` is used as the subject because its default (ADMIN for both
 * access and view) is far from the values the tests set, so a change is
 * unambiguous. The assertions are about the audit row, not about the matrix.
 */
class RolePermissionAuditTest extends TestCase
{
    use RefreshDatabase;

    private const MENU_KEY = 'members.index';

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = Member::factory()->create([
            'account_name' => 'rootadmin',
            'display_name' => 'Root Admin',
            'email' => 'root@example.test',
            'role' => MemberRole::SUPER_ADMIN,
        ]);

        // Fail loudly if the menu key or the config shape ever moves.
        $this->assertNotNull(
            PermissionRegistry::getEffective(self::MENU_KEY),
            'The menu key this test is built on no longer exists in config/roles.php'
        );
    }

    private function actor(?MemberRole $role = null): MemberActor
    {
        if ($role === null) {
            return new MemberActor($this->superAdmin);
        }

        return new MemberActor(Member::factory()->create([
            'account_name' => 'limited',
            'display_name' => 'Limited',
            'email' => 'limited@example.test',
            'role' => $role,
        ]));
    }

    /**
     * @return array<string, array<string, array<string, int>>>
     */
    private function submission(int $access, int $view): array
    {
        return [
            'permissions' => [
                self::MENU_KEY => ['access_roles' => $access, 'view_roles' => $view],
            ],
        ];
    }

    public function test_changing_a_permission_writes_one_audit_row_with_the_actor_and_the_change(): void
    {
        (new UpdateRolePermissionsAction())->execute(
            $this->actor(),
            $this->submission(MemberRole::SUPER_ADMIN->value, MemberRole::ADMIN->value),
        );

        $rows = AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->get();

        $this->assertCount(1, $rows, 'a permission change must write exactly one audit row');

        $row = $rows->first();
        $this->assertSame(AuditLog::CATEGORY_ACCOUNT, $row->category);
        $this->assertSame($this->superAdmin->id, $row->actor_id);
        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame('Root Admin', $row->actor_name);

        $this->assertSame(1, $row->context['changed_count']);
        $this->assertSame(
            ['access_roles' => MemberRole::ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
            $row->context['changed'][self::MENU_KEY]['from'],
        );
        $this->assertSame(
            ['access_roles' => MemberRole::SUPER_ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
            $row->context['changed'][self::MENU_KEY]['to'],
        );

        $this->assertNotNull(RolePermissionOverride::getCoreOverride(self::MENU_KEY));
    }

    public function test_a_save_that_changes_nothing_writes_no_row(): void
    {
        (new UpdateRolePermissionsAction())->execute(
            $this->actor(),
            $this->submission(MemberRole::ADMIN->value, MemberRole::ADMIN->value),
        );

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->count());
    }

    public function test_setting_a_permission_back_to_its_default_removes_the_override_and_is_recorded(): void
    {
        RolePermissionOverride::setCoreOverride(
            self::MENU_KEY,
            MemberRole::SUPER_ADMIN->value,
            MemberRole::ADMIN->value,
            $this->superAdmin->id,
        );
        PermissionRegistry::clearCache();

        (new UpdateRolePermissionsAction())->execute(
            $this->actor(),
            $this->submission(MemberRole::ADMIN->value, MemberRole::ADMIN->value),
        );

        $this->assertNull(RolePermissionOverride::getCoreOverride(self::MENU_KEY));

        $row = AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->sole();
        $this->assertSame(
            ['access_roles' => MemberRole::ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
            $row->context['changed'][self::MENU_KEY]['to'],
        );
    }

    public function test_reset_removes_the_override_and_writes_a_row(): void
    {
        RolePermissionOverride::setCoreOverride(
            self::MENU_KEY,
            MemberRole::SUPER_ADMIN->value,
            MemberRole::ADMIN->value,
            $this->superAdmin->id,
        );
        PermissionRegistry::clearCache();

        (new ResetRolePermissionAction())->execute($this->actor(), ['menu_key' => self::MENU_KEY]);

        $this->assertNull(RolePermissionOverride::getCoreOverride(self::MENU_KEY));

        $row = AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->sole();
        $this->assertSame(self::MENU_KEY, $row->target_label);
        $this->assertSame(self::MENU_KEY, $row->context['reset']);
        $this->assertSame($this->superAdmin->id, $row->actor_id);
    }

    public function test_reset_of_a_menu_with_no_override_writes_no_row(): void
    {
        (new ResetRolePermissionAction())->execute($this->actor(), ['menu_key' => self::MENU_KEY]);

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->count());
    }

    public function test_edit_below_view_is_rejected_before_anything_is_written(): void
    {
        try {
            (new UpdateRolePermissionsAction())->execute(
                $this->actor(),
                $this->submission(MemberRole::EDITOR->value, MemberRole::SUPER_ADMIN->value),
            );
            $this->fail('an edit permission below the view permission must be rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('permissions', $e->errors());
        }

        $this->assertNull(RolePermissionOverride::getCoreOverride(self::MENU_KEY));
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->count());
    }

    public function test_an_admin_without_the_manage_roles_permission_is_refused(): void
    {
        $this->expectException(AuthorizationException::class);

        (new UpdateRolePermissionsAction())->execute(
            $this->actor(MemberRole::ADMIN),
            $this->submission(MemberRole::SUPER_ADMIN->value, MemberRole::ADMIN->value),
        );
    }
}
