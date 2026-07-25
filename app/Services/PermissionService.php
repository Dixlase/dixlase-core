<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Service
 *
 * Centralized permission checking for the application.
 * Combines role-based permissions with menu-based permissions.
 */
class PermissionService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    protected const CACHE_TTL = 300;

    /**
     * Check if the current user has a permission
     */
    public static function can(Permission|string $permission): bool
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return false;
        }

        return self::memberCan($member, $permission);
    }

    /**
     * Check if a member has a permission
     */
    public static function memberCan(Member $member, Permission|string $permission): bool
    {
        // Convert string to Permission enum if needed
        if (is_string($permission)) {
            $permission = Permission::tryFrom($permission);
            if (! $permission) {
                return false;
            }
        }

        // Get member's role
        $role = $member->role instanceof MemberRole
            ? $member->role
            : MemberRole::tryFrom($member->role);

        if (! $role) {
            return false;
        }

        // Super Admin has all permissions
        if ($role === MemberRole::SUPER_ADMIN) {
            return true;
        }

        // Check role-based permission
        $minimumRole = $permission->minimumRole();
        if ($role->value < $minimumRole->value) {
            return false;
        }

        // Check menu-based permission override (if exists)
        $menuKey = self::permissionToMenuKey($permission);
        if ($menuKey) {
            $effective = PermissionRegistry::getEffective($menuKey);
            if ($effective && $role->value < $effective['access_roles']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current user has any of the given permissions
     *
     * @param  array<Permission|string>  $permissions
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current user has all of the given permissions
     *
     * @param  array<Permission|string>  $permissions
     */
    public static function canAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! self::can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current user has a minimum role
     */
    public static function hasRole(MemberRole $role): bool
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return false;
        }

        return self::memberHasRole($member, $role);
    }

    /**
     * Check if a member has a minimum role
     */
    public static function memberHasRole(Member $member, MemberRole $role): bool
    {
        $memberRole = $member->role instanceof MemberRole
            ? $member->role
            : MemberRole::tryFrom($member->role);

        if (! $memberRole) {
            return false;
        }

        return $memberRole->value >= $role->value;
    }

    /**
     * Check if the current user is a super admin
     */
    public static function isSuperAdmin(): bool
    {
        return self::hasRole(MemberRole::SUPER_ADMIN);
    }

    /**
     * Check if the current user is at least an admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole(MemberRole::ADMIN);
    }

    /**
     * Get all permissions the current user has
     *
     * @return array<Permission>
     */
    public static function getPermissions(): array
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return [];
        }

        return self::getMemberPermissions($member);
    }

    /**
     * Get all permissions a member has
     *
     * @return array<Permission>
     */
    public static function getMemberPermissions(Member $member): array
    {
        $role = $member->role instanceof MemberRole
            ? $member->role
            : MemberRole::tryFrom($member->role);

        if (! $role) {
            return [];
        }

        return Permission::forRole($role);
    }

    /**
     * Get menu permission from PermissionRegistry
     */
    protected static function getMenuPermission(string $menuKey): ?array
    {
        return PermissionRegistry::getEffective($menuKey);
    }

    /**
     * Convert a Permission enum to a menu key
     */
    protected static function permissionToMenuKey(Permission $permission): ?string
    {
        // Map permissions to menu keys
        return match ($permission) {
            Permission::DASHBOARD_VIEW => 'dashboard',
            Permission::MEMBERS_VIEW,
            Permission::MEMBERS_CREATE,
            Permission::MEMBERS_UPDATE,
            Permission::MEMBERS_DELETE => 'members',
            Permission::SETTINGS_VIEW => 'settings',
            Permission::SETTINGS_BASE => 'settings.base',
            Permission::SETTINGS_SECURITY => 'settings.security',
            Permission::SETTINGS_MEMBERS => 'settings.members',
            Permission::SETTINGS_SYSTEM => 'settings.system',
            Permission::SETTINGS_API => 'settings.api',
            Permission::PLUGINS_VIEW,
            Permission::PLUGINS_INSTALL,
            Permission::PLUGINS_UNINSTALL,
            Permission::PLUGINS_ENABLE,
            Permission::PLUGINS_DISABLE,
            Permission::PLUGINS_SETTINGS => 'plugins',
            Permission::THEMES_VIEW,
            Permission::THEMES_INSTALL,
            Permission::THEMES_UNINSTALL,
            Permission::THEMES_ENABLE,
            Permission::THEMES_DISABLE,
            Permission::THEMES_SETTINGS => 'themes',
            Permission::MEDIA_VIEW,
            Permission::MEDIA_UPLOAD,
            Permission::MEDIA_DELETE => 'media',
            Permission::AUDIT_LOGS_VIEW,
            Permission::AUDIT_LOGS_EXPORT => 'audit_logs',
            Permission::SYSTEM_LOGS_VIEW,
            Permission::SYSTEM_LOGS_DELETE,
            Permission::SYSTEM_CACHE_CLEAR,
            Permission::SYSTEM_MAINTENANCE,
            Permission::SYSTEM_BACKUP,
            Permission::SYSTEM_RESTORE => 'settings.system',
            Permission::API_KEYS_VIEW,
            Permission::API_KEYS_CREATE,
            Permission::API_KEYS_DELETE => 'settings.api',
            Permission::WEBHOOKS_VIEW,
            Permission::WEBHOOKS_CREATE,
            Permission::WEBHOOKS_UPDATE,
            Permission::WEBHOOKS_DELETE => 'webhooks',
            default => null,
        };
    }

    /**
     * Clear permission cache for a menu key
     */
    public static function clearMenuCache(string $menuKey): void
    {
        PermissionRegistry::clearMenuCache($menuKey);
    }

    /**
     * Clear all permission caches
     */
    public static function clearAllCache(): void
    {
        PermissionRegistry::clearCache();
    }

    /**
     * Authorize or throw exception
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorize(Permission|string $permission): void
    {
        if (! self::can($permission)) {
            abort(403, __('common.errors.unauthorized'));
        }
    }

    /**
     * Authorize role or throw exception
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorizeRole(MemberRole $role): void
    {
        if (! self::hasRole($role)) {
            abort(403, __('common.errors.unauthorized'));
        }
    }
}
