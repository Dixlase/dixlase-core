<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SettingSecurity;

class AdminIpFilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enableAllowedIps = SettingSecurity::get('enable_allowed_admin_ips', false);
        $allowedIps = explode(',', SettingSecurity::get('allowed_admin_ips', ''));

        $enableBlockedIps = SettingSecurity::get('enable_blocked_admin_ips', false);
        $blockedIps = explode(',', SettingSecurity::get('blocked_admin_ips', ''));

        if ($enableBlockedIps && in_array($request->ip(), $blockedIps)) {
            abort(403, 'Access Denied.');
        }

        if ($enableAllowedIps && !in_array($request->ip(), $allowedIps)) {
            abort(403, 'Unauthorized Access.');
        }

        return $next($request);
    }
}
