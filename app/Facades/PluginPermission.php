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

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * プラグイン権限ファサード
 * 
 * @method static bool check(string $pluginSlug, string $permission)
 * @method static bool has(string $pluginSlug, string $permission)
 * @method static array|null getPermissions(string $pluginSlug)
 * @method static bool canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read')
 * @method static bool canAccessOtherPlugin(string $pluginSlug, string $targetPlugin, string $access = 'read')
 * @method static array getSummary(string $pluginSlug)
 * @method static void clearCache(?string $pluginSlug = null)
 * @method static void logViolation(string $pluginSlug, string $permission, string $action = '')
 * @method static void enforce(string $pluginSlug, string $permission, string $action = '')
 * 
 * @see \App\Services\Plugin\PluginPermissionService
 */
class PluginPermission extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Plugin\PluginPermissionService::class;
    }
}
