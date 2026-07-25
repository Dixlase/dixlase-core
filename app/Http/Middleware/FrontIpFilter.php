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

use App\Helpers\IpAccessControlHelper;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        // Check if site_settings table exists
        try {
            if (! Schema::hasTable('site_settings')) {
                return $next($request);
            }
        } catch (\Exception $e) {
            return $next($request);
        }

        $enableAllowedFrontIps = SiteSetting::getValue('enable_allowed_front_ips', false);
        $allowedFrontIps = (string) SiteSetting::getValue('allowed_front_ips', '');
        $enableBlockedFrontIps = SiteSetting::getValue('enable_blocked_front_ips', false);
        $blockedFrontIps = (string) SiteSetting::getValue('blocked_front_ips', '');

        $userIp = $request->ip();

        // Allowed IPs check
        if (! empty($enableAllowedFrontIps)) {
            $allowedIps = IpAccessControlHelper::parseList($allowedFrontIps);

            if ($allowedIps !== [] && ! IpAccessControlHelper::ipMatchesAny($userIp, $allowedIps)) {
                $this->logDenial($request, $userIp, 'allowlist', $allowedIps);
                abort(403);
            }
        }

        // Blocked IPs check
        if (! empty($enableBlockedFrontIps)) {
            $blockedIps = IpAccessControlHelper::parseList($blockedFrontIps);

            if (IpAccessControlHelper::ipMatchesAny($userIp, $blockedIps)) {
                $this->logDenial($request, $userIp, 'blocklist', $blockedIps);
                abort(403);
            }
        }

        return $next($request);
    }

    /**
     * Record a rejected front-end request so operators can diagnose IP-filter issues.
     *
     * The client IP logged here is the address the application actually
     * observed; comparing it against the configured list quickly reveals
     * trusted-proxy misconfiguration or IPv4/IPv6 mismatches.
     *
     * @param  array<int, string>  $configuredIps
     */
    private function logDenial(Request $request, ?string $clientIp, string $reason, array $configuredIps): void
    {
        Log::warning('Front IP filter denied access', [
            'reason' => $reason,
            'client_ip' => $clientIp,
            'path' => $request->path(),
            'configured_ips' => $configuredIps,
        ]);
    }
}
