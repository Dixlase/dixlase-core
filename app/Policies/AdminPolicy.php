<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


    public function viewer(Member $member)
    {
        return $this->hasPermission($member, 'viewer');
    }

    public function receptionist(Member $member)
    {
        return $this->hasPermission($member, 'receptionist');
    }

    public function editor(Member $member)
    {
        return $this->hasPermission($member, 'editor');
    }

    public function manager(Member $member)
    {
        return $this->hasPermission($member, 'manager');
    }

    public function superManager(Member $member)
    {
        return $this->hasPermission($member, 'super_manager');
    }
}
