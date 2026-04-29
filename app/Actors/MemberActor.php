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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

declare(strict_types=1);

namespace App\Actors;

use App\Contracts\Action\Actor;
use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Models\Member;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Actor backed by an authenticated Member
 *
 * Wraps a Member model to provide Actor semantics.
 * Permission checks delegate to PermissionService.
 */
class MemberActor implements Actor
{
    public function __construct(
        protected readonly Member $member,
    ) {}

    public function getActorId(): ?int
    {
        return $this->member->id;
    }

    public function getActorType(): string
    {
        return 'member';
    }

    public function getActorName(): string
    {
        return $this->member->display_name ?? $this->member->account_name;
    }

    public function toAuditMorph(): ?Model
    {
        return $this->member;
    }

    public function hasPermission(Permission $permission): bool
    {
        return PermissionService::memberCan($this->member, $permission);
    }

    public function hasRole(MemberRole $role): bool
    {
        return PermissionService::memberHasRole($this->member, $role);
    }

    /**
     * Get the underlying Member model
     */
    public function getMember(): Member
    {
        return $this->member;
    }
}
