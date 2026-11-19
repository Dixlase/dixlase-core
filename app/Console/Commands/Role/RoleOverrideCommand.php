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

use App\Contracts\Site\SiteContextInterface;
use App\Enums\Permission;
use App\Enums\PermissionGrantType;
use App\Facades\Audit;
use App\Models\CustomRole;
use App\Models\CustomRolePermissionOverride;
use App\Models\Site;
use App\Services\CustomRolePermissionResolver;
use App\Services\PermissionRegistry;
use Illuminate\Console\Command;

/**
 * Set or clear one override of a custom role on one site.
 *
 * Usage:
 *   php artisan dls:role:override writer members.view deny
 *   php artisan dls:role:override writer posts.publish deny --plugin=DixlaseBlog
 *   php artisan dls:role:override writer members.view clear --site=2
 */
class RoleOverrideCommand extends Command
{
    protected $signature = 'dls:role:override
                            {slug : Slug of the custom role}
                            {permission : A core permission (members.view), or a plugin menu key with --plugin}
                            {type : grant, deny or clear}
                            {--plugin= : Plugin directory name, when the key is a plugin menu key}
                            {--site= : Site ID (defaults to the current site)}';

    protected $description = 'Grant, deny or clear one permission of a custom role on a site';

    public function handle(): int
    {
        $role = CustomRole::where('slug', (string) $this->argument('slug'))->first();
        if ($role === null) {
            $this->error(__('admin/command/role.role_not_found', ['slug' => $this->argument('slug')]));

            return self::FAILURE;
        }

        $type = (string) $this->argument('type');
        $grantType = PermissionGrantType::tryFrom($type);
        if ($grantType === null && $type !== 'clear') {
            $this->error(__('admin/command/role.invalid_type', ['type' => $type]));

            return self::FAILURE;
        }

        $key = $this->resolveKey((string) $this->argument('permission'), $this->option('plugin'));
        if ($key === null) {
            return self::FAILURE;
        }

        $siteId = $this->resolveSiteId();
        if ($siteId === null) {
            return self::FAILURE;
        }

        $existing = CustomRolePermissionOverride::forSite($siteId)
            ->where('custom_role_id', $role->id)
            ->where('permission', $key)
            ->first();

        if ($grantType === null) {
            // delete() fires the model event that forgets the cache.
            $existing?->delete();
        } elseif ($existing !== null) {
            $existing->update(['grant_type' => $grantType]);
        } else {
            CustomRolePermissionOverride::create([
                'site_id' => $siteId,
                'custom_role_id' => $role->id,
                'permission' => $key,
                'grant_type' => $grantType,
            ]);
        }

        Audit::logSecurity('custom_role_override_'.($grantType === null ? 'cleared' : $grantType->value), [
            'severity' => 'warning',
            'outcome' => 'success',
            'context' => [
                'custom_role_id' => $role->id,
                'slug' => $role->slug,
                'permission' => $key,
                'site_id' => $siteId,
                'previous' => $existing?->grant_type?->value,
                'triggered_by' => 'cli',
            ],
        ]);

        $this->info(__('admin/command/role.override_set', [
            'slug' => $role->slug,
            'permission' => $key,
            'type' => $type,
            'site' => $siteId,
        ]));

        return self::SUCCESS;
    }

    /**
     * The stored key: a core Permission value, or a declared plugin menu key.
     */
    private function resolveKey(string $permission, mixed $plugin): ?string
    {
        if ($plugin === null || $plugin === '') {
            if (Permission::tryFrom($permission) === null) {
                $this->error(__('admin/command/role.unknown_permission', ['permission' => $permission]));

                return null;
            }

            return $permission;
        }

        $plugin = (string) $plugin;

        if (! PermissionRegistry::hasPluginDefinition($plugin, $permission)) {
            $this->error(__('admin/command/role.unknown_plugin_key', ['plugin' => $plugin, 'key' => $permission]));

            return null;
        }

        return CustomRolePermissionResolver::pluginKey($plugin, $permission);
    }

    private function resolveSiteId(): ?int
    {
        $option = $this->option('site');

        if ($option === null) {
            return app(SiteContextInterface::class)->currentSiteId();
        }

        if (! ctype_digit((string) $option) || Site::find((int) $option) === null) {
            $this->error(__('admin/command/role.site_not_found', ['site' => $option]));

            return null;
        }

        return (int) $option;
    }
}
