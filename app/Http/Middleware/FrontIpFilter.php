<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontIpFilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Do not apply restrictions in the local environment
        if (app()->environment('local')) {
            return $next($request);
        }

        // Bypass if an admin is logged in
        if (auth('admin')->check()) {
            return $next($request);
        }

        $settings = settings([
            'enable_allowed_front_ips',
            'allowed_front_ips',
            'enable_blocked_front_ips',
            'blocked_front_ips',
        ]);

        $userIp = $request->ip();

        // Allowed IPs check
        if (!empty($settings['enable_allowed_front_ips'])) {
            $allowedIps = collect(explode(',', $settings['allowed_front_ips'] ?? ''))
                ->map(fn ($ip) => trim($ip))
                ->filter();

            if ($allowedIps->isNotEmpty() && !$allowedIps->contains($userIp)) {
                abort(403);
            }
        }

        // Blocked IPs check
        if (!empty($settings['enable_blocked_front_ips'])) {
            $blockedIps = collect(explode(',', $settings['blocked_front_ips'] ?? ''))
                ->map(fn ($ip) => trim($ip))
                ->filter();

            if ($blockedIps->isNotEmpty() && $blockedIps->contains($userIp)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
