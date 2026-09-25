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

namespace App\Services\Member;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Enforce the member hierarchy for every write a member makes on another member.
 *
 * Holding the members menu is not enough to act on any member: an actor may not
 * act on a member ranked above them, grant a role above their own, change their
 * own role or status, or change the role or status of the initial admin. Without
 * these checks an ADMIN could promote itself to SUPER_ADMIN, or take over or
 * disable the initial admin through the ordinary member form.
 */
class MemberHierarchyGuard
{
    /** The initial admin created by the installer. */
    public const INITIAL_ADMIN_ID = 1;

    /**
     * Refuse when the actor ranks below the target.
     *
     * @throws AuthorizationException
     */
    public static function assertCanManage(Member $actor, Member $target): void
    {
        if (self::rank($target) > self::rank($actor)) {
            throw new AuthorizationException(
                "Member [{$actor->id}] cannot manage member [{$target->id}], who holds a higher role."
            );
        }
    }

    /**
     * Refuse when the requested role is above the actor's own.
     *
     * @throws ValidationException
     */
    public static function assertCanAssignRole(Member $actor, mixed $role): void
    {
        if ($role === null || $role === '') {
            return;
        }

        if ((int) $role > self::rank($actor)) {
            throw ValidationException::withMessages([
                'role' => __('admin/members/validation.role_above_own'),
            ]);
        }
    }

    /**
     * Refuse role / status changes that the hierarchy forbids on an existing member.
     *
     * @param  array<string, mixed>  $data  Validated form data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public static function assertCanUpdate(Member $actor, Member $target, array $data): void
    {
        self::assertCanManage($actor, $target);

        $roleChanged = array_key_exists('role', $data)
            && $data['role'] !== null && $data['role'] !== ''
            && (int) $data['role'] !== self::rank($target);

        $statusChanged = array_key_exists('status', $data)
            && $data['status'] !== null && $data['status'] !== ''
            && (int) $data['status'] !== self::statusValue($target);

        if ($target->id === self::INITIAL_ADMIN_ID && ($roleChanged || $statusChanged)) {
            throw ValidationException::withMessages([
                $roleChanged ? 'role' : 'status' => __('admin/members/validation.initial_admin_locked'),
            ]);
        }

        if ($target->id === $actor->id && ($roleChanged || $statusChanged)) {
            throw ValidationException::withMessages([
                $roleChanged ? 'role' : 'status' => __('admin/members/validation.own_role_locked'),
            ]);
        }

        if ($roleChanged) {
            self::assertCanAssignRole($actor, $data['role']);
        }
    }

    /**
     * Roles the actor may choose from in the member form.
     *
     * @return list<MemberRole>
     */
    public static function assignableRoles(Member $actor): array
    {
        $rank = self::rank($actor);

        return array_values(array_filter(
            MemberRole::cases(),
            static fn (MemberRole $role): bool => $role->value <= $rank,
        ));
    }

    /**
     * Highest role value the actor may act on (members at or below it).
     */
    public static function rank(Member $member): int
    {
        $role = $member->getAttribute('role');

        if ($role instanceof MemberRole) {
            return $role->value;
        }

        return (int) $role;
    }

    private static function statusValue(Member $member): int
    {
        $status = $member->getAttribute('status');

        if ($status instanceof MemberStatus) {
            return (int) $status->value;
        }

        return (int) $status;
    }
}
