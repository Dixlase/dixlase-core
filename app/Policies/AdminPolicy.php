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

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Member;

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

    public function contributor(Member $member)
    {
        return $this->hasPermission($member, MemberRole::CONTRIBUTOR);
    }

    public function guest(Member $member)
    {
        return $this->hasPermission($member, MemberRole::GUEST);
    }
}
