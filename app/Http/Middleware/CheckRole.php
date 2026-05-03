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

use App\Enums\MemberRole;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Role Middleware
 *
 * Checks if the authenticated user has the required minimum role.
 *
 * Usage in routes:
 *   ->middleware('role:admin')
 *   ->middleware('role:super_admin')
 */
class CheckRole
{
    /**
     * Role name to enum mapping
     */
    protected const ROLE_MAP = [
        'super_admin' => MemberRole::SUPER_ADMIN,
        'admin' => MemberRole::ADMIN,
        'editor' => MemberRole::EDITOR,
        'contributor' => MemberRole::CONTRIBUTOR,
        'guest' => MemberRole::GUEST,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  string  $role  Role name (e.g., 'admin', 'super_admin')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $roleName = strtolower(trim($role));

        if (! isset(self::ROLE_MAP[$roleName])) {
            abort(500, "Invalid role specified: {$role}");
        }

        $requiredRole = self::ROLE_MAP[$roleName];

        if (! PermissionService::hasRole($requiredRole)) {
            abort(403, __('common.errors.unauthorized'));
        }

        return $next($request);
    }
}
