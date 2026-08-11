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

namespace App\Http\Middleware;

use App\Helpers\AdminHelper;
use App\Services\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default authorization for plugin admin routes.
 *
 * PluginServiceProvider::loadPluginRoutes() registers every plugin's
 * routes/admin.php behind `web`, `admin.ip`, `auth:member`, `verified` and
 * `log.admin.activity`. None of those is an authorization gate, so before
 * this middleware existed any verified member of any role -- contributor,
 * receptionist -- could reach every plugin admin endpoint. The role
 * restrictions plugins declare in config/admin/roles.php were only ever
 * consulted by CheckMenuAccess, which is not applied there, so they
 * controlled sidebar visibility and nothing else.
 *
 * This is deliberately NOT CheckMenuAccess. That middleware resolves keys
 * against Core's config/roles.php, whose only top-level keys are front,
 * media, members and settings -- no plugin key resolves, and
 * PermissionRegistry::canAccess() is fail-closed, so reusing it would lock
 * every plugin admin route to SUPER_ADMIN. It also shares `menuEditable`
 * from the Core-side check, which would hide the save buttons on plugin
 * screens for everyone below SUPER_ADMIN.
 *
 * @api Stable API available for plugins/themes
 */
class EnsurePluginAdminAccess
{
    /**
     * @param  string  $pluginDirectory  Directory name, passed in by
     *                                   PluginServiceProvider. Deliberately not
     *                                   the kebab slug: PermissionRegistry reads
     *                                   plugins/{$dir}/config/admin/roles.php, and
     *                                   the two differ in ways no case conversion
     *                                   recovers (dixlase-seo -> DixlaseSEO).
     */
    public function handle(Request $request, Closure $next, string $pluginDirectory): Response
    {
        if (! Auth::check()) {
            abort(403, __('http/middleware/ensure_plugin_admin_access.no_access_permission'));
        }

        $menuKey = $this->resolveMenuKey($request, $pluginDirectory);

        $permitted = $this->isWriteRequest($request)
            ? AdminHelper::canEditPluginMenu($pluginDirectory, $menuKey)
            : AdminHelper::canAccessPluginMenu($pluginDirectory, $menuKey);

        if (! $permitted) {
            abort(403, __('http/middleware/ensure_plugin_admin_access.no_access_permission'));
        }

        // Mirrors CheckMenuAccess, but resolved against the plugin's own
        // declarations so the save / delete components on plugin screens
        // reflect the plugin's roles.php rather than Core's.
        View::share('menuEditable', AdminHelper::canEditPluginMenu($pluginDirectory, $menuKey));
        View::share('menuKey', $menuKey);

        return $next($request);
    }

    /**
     * Pick the permission key to judge this request against.
     *
     * Route names look like `dixlase-pages::admin.pages.settings.update`. The
     * part after `admin.` is walked from most specific to least, and the first
     * segment the plugin actually declares wins:
     *
     *     pages.settings.update  (undeclared)
     *     pages.settings         <- declared, used
     *     pages
     *
     * When nothing matches, the full path is returned unchanged. That is not a
     * fallback so much as the safe outcome: getPluginEffective() answers
     * ADMIN/ADMIN for undeclared keys, so an unrecognised route requires ADMIN.
     * Roughly 51 of the 72 plugin admin routes shipping today -- every store,
     * update, destroy, sync and reorder endpoint -- land here, which is the
     * intended default until plugins declare permissions for their write
     * routes.
     */
    private function resolveMenuKey(Request $request, string $pluginDirectory): string
    {
        $routeName = $request->route()?->getName();

        // A plugin admin route with no name cannot be mapped to a declaration.
        // Hand back a key nothing can declare so the ADMIN default applies.
        if (! is_string($routeName) || $routeName === '') {
            return '__unnamed__';
        }

        $path = str_contains($routeName, '::')
            ? substr($routeName, strpos($routeName, '::') + 2)
            : $routeName;

        if (str_starts_with($path, 'admin.')) {
            $path = substr($path, 6);
        }

        $segments = explode('.', $path);

        for ($length = count($segments); $length > 0; $length--) {
            $candidate = implode('.', array_slice($segments, 0, $length));

            if (PermissionRegistry::hasPluginDefinition($pluginDirectory, $candidate)) {
                return $candidate;
            }
        }

        return $path;
    }

    /**
     * Whether this request can change state.
     *
     * GET/HEAD are judged with canAccessPluginMenu() (access_roles OR
     * view_roles, i.e. "may open the page"); everything else with
     * canEditPluginMenu() (access_roles only). Note that several plugins route
     * state changes through POST endpoints that read like views -- preview,
     * bump-version -- so the method, not the route name, decides.
     */
    private function isWriteRequest(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD'], true);
    }
}
