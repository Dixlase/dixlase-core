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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated このモデルは廃止されました。
 *             Please use the RolePermissionOverride model and PermissionRegistry service in the new approach.
 *             See docs/role-permission-system.md for details.
 */
class PluginMemberRolePermission extends Model
{
    use HasFactory;

    protected $table = 'plugins_members_role_permissions';

    protected $fillable = [
        'plugin_slug',
        'menu_key',
        'access_roles',
        'view_roles',
    ];

    /**
     * Get access_roles as integer
     */
    public function getAccessRolesAttribute($value): int
    {
        return (int) ($value ?? MemberRole::ADMIN->value);
    }

    /**
     * Get view_roles as integer
     */
    public function getViewRolesAttribute($value): int
    {
        return (int) ($value ?? MemberRole::ADMIN->value);
    }

    /**
     * Check if the specified user has access permission
     */
    public function canAccess(MemberRole $userRole): bool
    {
        // SUPER_ADMIN always has access
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        return $userRole->value >= $this->access_roles;
    }

    /**
     * Check if the specified user has view permission
     */
    public function canView(MemberRole $userRole): bool
    {
        // SUPER_ADMIN can always view
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        return $userRole->value >= $this->view_roles;
    }

    /**
     * Check if the specified user has edit permission
     */
    public function canEdit(MemberRole $userRole): bool
    {
        // SUPER_ADMIN can always edit
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        return $userRole->value >= $this->view_roles;
    }

    /**
     * Whether access permission is SUPER_ADMIN only
     */
    public function isSuperAdminOnlyAccess(): bool
    {
        return $this->access_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * Whether view permission is SUPER_ADMIN only
     */
    public function isSuperAdminOnlyView(): bool
    {
        return $this->view_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * Get permission by plugin slug and menu key
     */
    public static function getPermission(string $pluginSlug, string $menuKey): ?self
    {
        return static::where('plugin_slug', $pluginSlug)
            ->where('menu_key', $menuKey)
            ->first();
    }

    /**
     * Get all permissions for a plugin
     */
    public static function getPluginPermissions(string $pluginSlug): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('plugin_slug', $pluginSlug)->get();
    }

    /**
     * Get all plugin permissions grouped by plugin slug
     */
    public static function getAllGroupedByPlugin(): \Illuminate\Support\Collection
    {
        return static::all()->groupBy('plugin_slug');
    }
}
