<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Traits;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Services\PermissionService;

/**
 * HasPermissions Trait
 *
 * Provides permission checking methods for Member model.
 */
trait HasPermissions
{
    /**
     * Check if the member has a permission
     */
    public function hasPermission(Permission|string $permission): bool
    {
        return PermissionService::memberCan($this, $permission);
    }

    /**
     * Check if the member has any of the given permissions
     *
     * @param  array<Permission|string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the member has all of the given permissions
     *
     * @param  array<Permission|string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the member has a minimum role
     */
    public function hasRole(MemberRole $role): bool
    {
        return PermissionService::memberHasRole($this, $role);
    }

    /**
     * Check if the member is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(MemberRole::SUPER_ADMIN);
    }

    /**
     * Check if the member is at least an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(MemberRole::ADMIN);
    }

    /**
     * Check if the member is at least an editor
     */
    public function isEditor(): bool
    {
        return $this->hasRole(MemberRole::EDITOR);
    }

    /**
     * Check if the member is at least a contributor
     */
    public function isContributor(): bool
    {
        return $this->hasRole(MemberRole::CONTRIBUTOR);
    }

    /**
     * Get all permissions the member has
     *
     * @return array<Permission>
     */
    public function getPermissions(): array
    {
        return PermissionService::getMemberPermissions($this);
    }

    /**
     * Get the member's role as MemberRole enum
     */
    public function getMemberRole(): ?MemberRole
    {
        if ($this->role instanceof MemberRole) {
            return $this->role;
        }

        return MemberRole::tryFrom($this->role);
    }

    /**
     * Check if the member can perform a dangerous action
     */
    public function canPerformDangerous(Permission $permission): bool
    {
        if (! $permission->isDangerous()) {
            return $this->hasPermission($permission);
        }

        // For dangerous actions, require super admin
        return $this->isSuperAdmin();
    }
}
