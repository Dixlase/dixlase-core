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

use App\Services\Plugin\PluginPermissionService;

if (! function_exists('plugin_permission')) {
    /**
     * Get plugin permission service instance
     *
     *
     * @example
     * // Permission check
     * plugin_permission()->check('dixlase-inquiry', 'mail.send');
     *
     * // Get permission summary
     * plugin_permission()->getSummary('dixlase-inquiry');
     */
    function plugin_permission(): PluginPermissionService
    {
        return app(PluginPermissionService::class);
    }
}

if (! function_exists('plugin_can')) {
    /**
     * Check if plugin has a specific permission
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $permission  Permission key (dot notation)
     *
     * @example
     * if (plugin_can('dixlase-inquiry', 'mail.send')) {
     *     // Email sending process
     * }
     */
    function plugin_can(string $pluginSlug, string $permission): bool
    {
        return app(PluginPermissionService::class)->check($pluginSlug, $permission);
    }
}

if (! function_exists('plugin_enforce')) {
    /**
     * Check plugin permission and throw exception on violation
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $permission  Permission key (dot notation)
     * @param  string  $action  Action attempted to execute (for logging)
     *
     * @throws \App\Exceptions\PluginPermissionException
     *
     * @example
     * plugin_enforce('dixlase-inquiry', 'mail.send', 'Sending inquiry notification');
     */
    function plugin_enforce(string $pluginSlug, string $permission, string $action = ''): void
    {
        app(PluginPermissionService::class)->enforce($pluginSlug, $permission, $action);
    }
}
