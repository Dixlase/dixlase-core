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

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Common trait for device and environment detection
 */
trait DeviceDetectionTrait
{
    /**
     * Determine whether the access is from a different environment (IP/User-Agent)
     *
     * @param  Model  $user  User model
     * @param  Request|null  $request  Request object (if null, use current request)
     * @return bool Whether it is a different environment
     */
    public function isDifferentEnvironment(Model $user, ?Request $request = null): bool
    {
        $request = $request ?? request();

        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        // If IP cannot be obtained, consider it a different environment (fail-safe)
        if (! $currentIp) {
            \Illuminate\Support\Facades\Log::info('DeviceDetectionTrait: Different environment (no IP)', ['user_email' => $user->email]);

            return true;
        }

        // If User-Agent is null, use default value (for test environment)
        if (! $currentUserAgent) {
            $currentUserAgent = 'Unknown User Agent';
        }

        // Get recent login history (within 24 hours)
        $recentLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->orderBy('attempted_at', 'desc')
            ->first();

        // If first login or no recent login history, consider it a different environment
        if (! $recentLogin) {
            return true;
        }

        // Different environment if IP address or User-Agent differs
        if ($recentLogin->ip_address !== $currentIp || $recentLogin->user_agent !== $currentUserAgent) {
            return true;
        }

        // Check trusted devices (for 2FA)
        if (method_exists($user, 'trustedDevices')) {
            $trustedDeviceToken = $request->cookie('trusted_device');
            if ($trustedDeviceToken && $user->trustedDevices()
                ->where('token', hash('sha256', $trustedDeviceToken))
                ->exists()) {
                return false;
            }
        }

        return false; // 同じ環境からのアクセス
    }

    /**
     * Determine whether the access is from a new device/IP (for login notifications)
     * Exclude current login and compare with past login history
     *
     * @param  Model  $user  User model
     * @param  string  $ip  IP address
     * @param  string  $userAgent  User-Agent
     * @return bool Whether it is a new device
     */
    public function isNewDevice(Model $user, string $ip, string $userAgent): bool
    {
        // Check if logged in with the same IP/User-Agent combination in the past
        // To exclude current login, target logins older than 5 minutes ago
        $previousSameLogin = \App\Models\MemberLoginAttempt::where('identifier', $user->email)
            ->where('successful', true)
            ->where('attempted_at', '>=', now()->subDay())
            ->where('attempted_at', '<', now()->subMinutes(5)) // 5分前より古いログインを対象
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->first();

        // If there is a past login from the same environment, it is an existing device
        return ! $previousSameLogin;
    }
}
