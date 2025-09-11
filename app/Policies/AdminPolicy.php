<?php

/**
 * This file is part of Dixlase.
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
use App\Enums\MemberRole;

class AdminPolicy
{


    public function hasPermission(Member $member, MemberRole $requiredRole)
    {
        $memberRole = $member->role instanceof MemberRole
            ? $member->role
            : MemberRole::from($member->role);

        return $memberRole->canAccess($requiredRole);
    }

    public function superAdmin(Member $member)
    {
        return $this->hasPermission($member, MemberRole::SUPER_ADMIN);
    }

    public function admin(Member $member)
    {
        return $this->hasPermission($member, MemberRole::ADMIN);
    }

    public function editor(Member $member)
    {
        return $this->hasPermission($member, MemberRole::EDITOR);
    }

    public function author(Member $member)
    {
        return $this->hasPermission($member, MemberRole::AUTHOR);
    }

    public function contributor(Member $member)
    {
        return $this->hasPermission($member, MemberRole::CONTRIBUTOR);
    }

    public function receptionist(Member $member)
    {
        return $this->hasPermission($member, MemberRole::RECEPTIONIST);
    }

    public function guest(Member $member)
    {
        return $this->hasPermission($member, MemberRole::GUEST);
    }
}
