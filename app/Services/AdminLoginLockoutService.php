<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services;

use App\Helpers\LoginLockoutHelper;
use App\Models\SecuritySetting;
use Illuminate\Http\Request;

class AdminLoginLockoutService
{
    /**
     * Check if login attempt restrictions are enabled
     */
    public function isLockoutEnabled(): bool
    {
        return LoginLockoutHelper::isLockoutEnabled();
    }

    /**
     * Check if lockout notifications are enabled
     */
    public function isNotificationEnabled(): bool
    {
        return LoginLockoutHelper::isNotificationEnabled();
    }

    /**
     * Check if the specified identifier is locked out
     */
    public function isLockedOut(string $identifier): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (! $settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $settings['time_window']
        );

        return $failedAttempts >= $settings['max_attempts'];
    }

    /**
     * Check if the IP address is locked out
     */
    public function isIpLockedOut(string $ipAddress): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (! $settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $settings['time_window']
        );

        // Get maximum attempts for IP (from security settings)
        $maxAttemptsForIp = SecuritySetting::get('login_attempt_max_attempts_ip', $settings['max_attempts'] * 2);

        return $failedAttempts >= $maxAttemptsForIp;
    }

    /**
     * Get remaining time until lockout release (in minutes)
     */
    public function getLockoutRemainingMinutes(string $identifier): ?int
    {
        $settings = LoginLockoutHelper::getLockoutSettings();

        return LoginLockoutHelper::getLockoutRemainingMinutes($identifier, $settings['lockout_duration']);
    }

    /**
     * Process after successful login
     */
    public function handleSuccessfulLogin(string $identifier, ?\Illuminate\Http\Request $request = null): void
    {
        // Record successful login attempt (with behavioral analysis data)
        $request = $request ?? request();
        $behaviorService = app(\App\Services\LoginBehaviorService::class);
        $behaviorService->recordLoginAttempt($identifier, $request, true);

        // Clear failed attempt records
        LoginLockoutHelper::clearFailedAttempts($identifier);
    }

    /**
     * Process after failed login
     */
    public function handleFailedLogin(Request $request, string $identifier, ?string $failureReason = null): array
    {
        return LoginLockoutHelper::recordAndCheckLockout($request, $identifier, false, failureReason: $failureReason);
    }

    /**
     * Get detailed information about lockout status
     */
    public function getLockoutStatusDetails(string $identifier, string $ipAddress): array
    {
        return LoginLockoutHelper::getLockoutStatusDetails($identifier, $ipAddress);
    }

    /**
     * Generate lockout error message
     */
    public function generateLockoutMessage(array $lockoutInfo): string
    {
        return LoginLockoutHelper::generateLockoutMessage($lockoutInfo, 'admin');
    }
}
