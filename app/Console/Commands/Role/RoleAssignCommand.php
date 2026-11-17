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

namespace App\Console\Commands\Role;

use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Models\CustomRole;
use App\Models\Member;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Assign a custom role to a member, or remove it.
 *
 * Assigning sets the member's preset role to the custom role's base role.
 * Removing leaves the preset as it is, so the member keeps the base role's
 * permissions without the overrides.
 *
 * Usage:
 *   php artisan dls:role:assign writer --member=12
 *   php artisan dls:role:assign --member=writer@example.com --remove
 */
class RoleAssignCommand extends Command
{
    protected $signature = 'dls:role:assign
                            {slug? : Slug of the custom role (not needed with --remove)}
                            {--member= : Member ID or email}
                            {--remove : Remove the member\'s custom role instead}';

    protected $description = 'Assign a custom role to a member, or remove it';

    public function handle(): int
    {
        $identifier = (string) $this->option('member');
        $member = $identifier === ''
            ? null
            : (ctype_digit($identifier) ? Member::find((int) $identifier) : Member::where('email', $identifier)->first());

        if ($member === null) {
            $this->error(__('admin/command/role.member_not_found', ['member' => $identifier]));

            return self::FAILURE;
        }

        $label = $member->display_name ?? $member->account_name ?? (string) $member->id;
        $previousRoleId = $member->custom_role_id;

        if ($this->option('remove')) {
            $member->forceFill(['custom_role_id' => null])->save();

            $this->audit('custom_role_unassigned', $member, $previousRoleId, null);
            $this->info(__('admin/command/role.unassigned', ['member' => $label]));

            return self::SUCCESS;
        }

        $role = CustomRole::where('slug', (string) $this->argument('slug'))->first();
        if ($role === null) {
            $this->error(__('admin/command/role.role_not_found', ['slug' => $this->argument('slug')]));

            return self::FAILURE;
        }

        $previousPreset = $member->getMemberRole();

        // assignTo() enforces these too; checking here gives a translated message.
        if ($previousPreset === MemberRole::SUPER_ADMIN) {
            $this->error(__('admin/command/role.super_admin_refused', ['member' => $label]));

            return self::FAILURE;
        }

        if ($role->max_members !== null
            && $member->custom_role_id !== $role->id
            && $role->members()->count() >= $role->max_members) {
            $this->error(__('admin/command/role.role_full', ['slug' => $role->slug, 'max' => $role->max_members]));

            return self::FAILURE;
        }

        try {
            $role->assignTo($member);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->audit('custom_role_assigned', $member, $previousRoleId, $role->id, [
            'previous_role' => $previousPreset?->name,
            'role' => $role->base_role->name,
        ]);

        if (! $role->is_active) {
            $this->warn(__('admin/command/role.assigned_inactive', ['slug' => $role->slug]));
        }

        $this->info(__('admin/command/role.assigned', [
            'slug' => $role->slug,
            'member' => $label,
            'base' => $role->base_role->label(),
        ]));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function audit(string $action, Member $member, ?int $from, ?int $to, array $extra = []): void
    {
        Audit::logSecurity($action, [
            'severity' => 'warning',
            'outcome' => 'success',
            'context' => [
                'member_id' => $member->id,
                'previous_custom_role_id' => $from,
                'custom_role_id' => $to,
                'triggered_by' => 'cli',
            ] + $extra,
        ]);
    }
}
