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

namespace App\Http\Middleware;

use App\Enums\SafeMode;
use App\Services\SafeModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin route blocking middleware
 *
 * Blocks routes to plugin controllers when plugin safe mode is enabled
 * and redirects to the admin panel dashboard
 */
class BlockPluginRoutes
{
    public function __construct(
        protected SafeModeService $safeModeService
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->safeModeService->isActive(SafeMode::Plugins)) {
            return $next($request);
        }

        // Check if the route's controller is in a plugin namespace
        $route = $request->route();
        if ($route === null) {
            return $next($request);
        }

        $controller = $route->getControllerClass();
        if ($controller !== null && str_starts_with($controller, 'Plugins\\')) {
            return redirect()->route('admin.dashboard')
                ->with('warning', __('admin/safe-mode.plugins_route_blocked'));
        }

        return $next($request);
    }
}
