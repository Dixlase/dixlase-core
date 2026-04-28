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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 *
 * Checks if the authenticated user has the required permission(s).
 *
 * Usage in routes:
 *   ->middleware('permission:members.view')
 *   ->middleware('permission:members.create,members.update')  // any of these
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions Permission values (comma-separated for "any")
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Convert permission strings to Permission enums
        $permissionEnums = [];
        foreach ($permissions as $permissionString) {
            // Handle comma-separated permissions
            $parts = explode(',', $permissionString);
            foreach ($parts as $part) {
                $permission = Permission::tryFrom(trim($part));
                if ($permission) {
                    $permissionEnums[] = $permission;
                }
            }
        }

        if (empty($permissionEnums)) {
            // No valid permissions specified, deny access
            abort(403, __('common.errors.unauthorized'));
        }

        // Check if user has any of the required permissions
        if (!PermissionService::canAny($permissionEnums)) {
            abort(403, __('common.errors.unauthorized'));
        }

        return $next($request);
    }
}
