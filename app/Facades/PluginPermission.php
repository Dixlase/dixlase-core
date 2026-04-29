<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @api Stable API available for plugins/themes
 *
 * PluginPermission Facade — convenience accessor for PluginPermissionService.
 * Plugins/themes may use this facade to verify their declared permissions
 * (e.g. PluginPermission::check('my-plugin', 'database.own_tables')).
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
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Plugin\PluginPermissionService::class;
    }
}
