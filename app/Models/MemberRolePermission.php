<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Models;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated このモデルは廃止されました。
 *             For the new approach, use the RolePermissionOverride model and PermissionRegistry service
 *             See docs/role-permission-system.md for details
 */
class MemberRolePermission extends Model
{
    protected $table = 'members_role_permissions';

    protected $fillable = [
        'menu_key',
        'access_roles',
        'view_roles',
    ];

    /**
     * Get access_roles as integer
     * The stored value means "users with this permission level or higher can access"
     * If SUPER_ADMIN(10) is set, it is for privileged administrators only
     *
     * @param  mixed  $value
     */
    public function getAccessRolesAttribute($value): int
    {
        // Return GUEST (lowest permission) if empty or null
        if ($value === null || $value === '') {
            return MemberRole::GUEST->value;
        }

        return (int) $value;
    }

    /**
     * Get view_roles as integer
     * The stored value means "users with this permission level or higher can view"
     * If SUPER_ADMIN(10) is set, it is for privileged administrators only
     *
     * @param  mixed  $value
     */
    public function getViewRolesAttribute($value): int
    {
        // Return GUEST (lowest permission) if empty or null
        if ($value === null || $value === '') {
            return MemberRole::GUEST->value;
        }

        return (int) $value;
    }

    /**
     * Check if the specified user permission can access
     */
    public function canAccess(MemberRole $userRole): bool
    {
        return $userRole->value >= $this->access_roles;
    }

    /**
     * Check if the specified user permission can view
     */
    public function canView(MemberRole $userRole): bool
    {
        return $userRole->value >= $this->view_roles;
    }

    /**
     * Check if it is for privileged administrators only (edit permission)
     */
    public function isSuperAdminOnlyAccess(): bool
    {
        return $this->access_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * Check if it is for privileged administrators only (view permission)
     */
    public function isSuperAdminOnlyView(): bool
    {
        return $this->view_roles === MemberRole::SUPER_ADMIN->value;
    }
}
