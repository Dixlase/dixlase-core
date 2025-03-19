<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Policies;

use App\Models\Member;

class AdminPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }


    public function hasPermission(Member $member, string $requiredRole)
    {
        $rolesHierarchy = config('admin.roles_hierarchy');
        return in_array($member->role, $rolesHierarchy[$requiredRole]);
    }


    public function contributor(Member $member)
    {
        return $this->hasPermission($member, 'contributor');
    }

    public function author(Member $member)
    {
        return $this->hasPermission($member, 'author');
    }

    public function editor(Member $member)
    {
        return $this->hasPermission($member, 'editor');
    }

    public function admin(Member $member)
    {
        return $this->hasPermission($member, 'admin');
    }

    public function superAdmin(Member $member)
    {
        return $this->hasPermission($member, 'super_admin');
    }
}
