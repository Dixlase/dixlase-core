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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseSetting;
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
        try {
            $member = auth('member')->user();
            if ($member && $member->is_admin) {
                return $next($request);
            }
        } catch (\Exception $e) {
            // Guard not available, continue with IP check
        }

        // Check if base_settings table exists
        try {
            if (!Schema::hasTable('base_settings')) {
                return $next($request);
            }
        } catch (\Exception $e) {
            return $next($request);
        }

        $enableAllowedFrontIps = BaseSetting::getValue('enable_allowed_front_ips', false);
        $allowedFrontIps = BaseSetting::getValue('allowed_front_ips', '');
        $enableBlockedFrontIps = BaseSetting::getValue('enable_blocked_front_ips', false);
        $blockedFrontIps = BaseSetting::getValue('blocked_front_ips', '');

        $userIp = $request->ip();

        // Allowed IPs check
        if (!empty($enableAllowedFrontIps)) {
            $allowedIps = collect(explode(',', $allowedFrontIps ?? ''))
                ->map(fn ($ip) => trim($ip))
                ->filter();

            if ($allowedIps->isNotEmpty() && !$allowedIps->contains($userIp)) {
                abort(403);
            }
        }

        // Blocked IPs check
        if (!empty($enableBlockedFrontIps)) {
            $blockedIps = collect(explode(',', $blockedFrontIps ?? ''))
                ->map(fn ($ip) => trim($ip))
                ->filter();

            if ($blockedIps->isNotEmpty() && $blockedIps->contains($userIp)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
