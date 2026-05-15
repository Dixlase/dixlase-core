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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AdminIpFilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip IP restriction in local environment
        if (app()->environment('local')) {
            return $next($request);
        }

        // Set default values
        $enableAllowedIps = false;
        $allowedIps = [];
        $enableBlockedIps = false;
        $blockedIps = [];

        // After multisite consolidation, security settings are also integrated into global_settings
        if (Schema::hasTable('global_settings')) {
            $enableAllowedIps = (bool) SecuritySetting::get('enable_allowed_admin_ips', 0);
            $allowedIps = array_filter(explode(',', (string) SecuritySetting::get('allowed_admin_ips', '')));

            $enableBlockedIps = (bool) SecuritySetting::get('enable_blocked_admin_ips', 0);
            $blockedIps = array_filter(explode(',', (string) SecuritySetting::get('blocked_admin_ips', '')));
        }

        // Skip IP restriction if allow list is not enabled
        if (! $enableAllowedIps && ! $enableBlockedIps) {
            return $next($request);
        }

        if ($enableBlockedIps && in_array($request->ip(), $blockedIps)) {
            abort(403, 'Access Denied.');
        }

        if ($enableAllowedIps && ! in_array($request->ip(), $allowedIps)) {
            abort(403, 'Unauthorized Access.');
        }

        return $next($request);
    }
}
