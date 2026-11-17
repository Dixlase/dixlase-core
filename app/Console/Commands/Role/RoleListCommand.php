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

use App\Models\CustomRole;
use App\Models\CustomRolePermissionOverride;
use Illuminate\Console\Command;

/**
 * List custom roles, or one role's overrides on every site.
 *
 * Usage:
 *   php artisan dls:role:list
 *   php artisan dls:role:list writer
 */
class RoleListCommand extends Command
{
    protected $signature = 'dls:role:list
                            {slug? : Show the overrides of this role}';

    protected $description = 'List custom roles, or the overrides of one';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        return $slug === null ? $this->listRoles() : $this->listOverrides((string) $slug);
    }

    private function listRoles(): int
    {
        $roles = CustomRole::withCount('members')->orderBy('sort_order')->orderBy('slug')->get();

        if ($roles->isEmpty()) {
            $this->info(__('admin/command/role.no_roles'));

            return self::SUCCESS;
        }

        $this->table(
            [
                __('admin/command/role.col_slug'),
                __('admin/command/role.col_name'),
                __('admin/command/role.col_base'),
                __('admin/command/role.col_actor_type'),
                __('admin/command/role.col_active'),
                __('admin/command/role.col_members'),
            ],
            $roles->map(fn (CustomRole $role): array => [
                $role->slug,
                $role->displayName(),
                $role->base_role->label(),
                $role->actor_type->value,
                $role->is_active ? __('admin/command/role.yes') : __('admin/command/role.no'),
                $role->members_count.($role->max_members !== null ? ' / '.$role->max_members : ''),
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function listOverrides(string $slug): int
    {
        $role = CustomRole::where('slug', $slug)->first();
        if ($role === null) {
            $this->error(__('admin/command/role.role_not_found', ['slug' => $slug]));

            return self::FAILURE;
        }

        $overrides = CustomRolePermissionOverride::allSites()
            ->where('custom_role_id', $role->id)
            ->orderBy('site_id')
            ->orderBy('permission')
            ->get();

        if ($overrides->isEmpty()) {
            $this->info(__('admin/command/role.no_overrides', ['slug' => $slug]));

            return self::SUCCESS;
        }

        $this->table(
            [
                __('admin/command/role.col_site'),
                __('admin/command/role.col_permission'),
                __('admin/command/role.col_type'),
            ],
            $overrides->map(fn (CustomRolePermissionOverride $o): array => [
                $o->site_id,
                $o->permission,
                $o->grant_type->value,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
