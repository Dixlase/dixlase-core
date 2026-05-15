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

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Trait that provides common logic for login attempt limiting
 *
 * This Trait provides common logic for member and user login attempt limiting
 * processing
 *
 * Controllers using this must implement the following abstract methods:
 * - getLoginAttemptModelClass(): Returns the login attempt model class name
 * - getSetting(): Retrieves settings values
 */
trait LoginLockoutTrait
{
    /**
     * Get the login attempt model class name
     */
    abstract protected function getLoginAttemptModelClass(): string;

    /**
     * Check if login attempt limiting is enabled
     *
     * @param  string  $settingKey  Default: 'login_attempt_limit_enabled'
     */
    public function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, false);
    }

    /**
     * Check if lockout notification is enabled
     *
     * @param  string  $settingKey  Default: 'lockout_notification_enabled'
     */
    public function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, true);
    }

    /**
     * Get the maximum number of attempts
     *
     * @param  string  $settingKey  Default: 'login_attempt_max_attempts'
     */
    public function getMaxAttempts(string $settingKey = 'login_attempt_max_attempts'): int
    {
        return $this->getIntegerSetting($settingKey, 5);
    }

    /**
     * Get the time window (minutes)
     *
     * @param  string  $settingKey  Default: 'login_attempt_time_window'
     */
    public function getTimeWindow(string $settingKey = 'login_attempt_time_window'): int
    {
        return $this->getIntegerSetting($settingKey, 15);
    }

    /**
     * Get the lockout duration (minutes)
     *
     * @param  string  $settingKey  Default: 'login_attempt_lockout_duration'
     */
    public function getLockoutDuration(string $settingKey = 'login_attempt_lockout_duration'): int
    {
        return $this->getIntegerSetting($settingKey, 30);
    }

    /**
     * Check if the specified identifier is locked out
     *
     * @param  array  $settings  Settings array (optional)
     */
    public function isLockedOut(string $identifier, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (! $this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $failedAttempts = $modelClass::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow($timeWindowKey)
        );

        return $failedAttempts >= $this->getMaxAttempts($maxAttemptsKey);
    }

    /**
     * Check if IP address is locked out
     *
     * @param  array  $settings  Settings array (optional)
     */
    public function isIpLockedOut(string $ipAddress, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (! $this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $failedAttempts = $modelClass::getFailedAttemptsCountByIp(
            $ipAddress,
            $this->getTimeWindow($timeWindowKey)
        );

        // IP address-based lockout is configured more strictly than identifier-based
        $maxAttemptsForIp = $this->getMaxAttempts($maxAttemptsKey) * 2;

        return $failedAttempts >= $maxAttemptsForIp;
    }

    /**
     * Retrieve remaining time until lockout release (in minutes)
     *
     * @param  array  $settings  Settings array (optional)
     */
    public function getLockoutRemainingMinutes(string $identifier, array $settings = []): ?int
    {
        $lockoutDurationKey = $settings['lockout_duration_key'] ?? 'login_attempt_lockout_duration';

        if (! $this->isLockedOut($identifier, $settings)) {
            return null;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $lastFailedAttempt = $modelClass::getLastFailedAttempt($identifier);
        if (! $lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->addMinutes($this->getLockoutDuration($lockoutDurationKey));
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($lockoutUntil)) {
            return 0; // Lockout period ended
        }

        return $now->diffInMinutes($lockoutUntil, false);
    }

    /**
     * Record login attempt
     *
     * @return MemberLoginAttempt
     */
    public function recordLoginAttempt(Request $request, string $identifier, bool $successful = false)
    {
        $behaviorService = app(\App\Services\LoginBehaviorService::class);

        return $behaviorService->recordLoginAttempt($identifier, $request, $successful);
    }

    /**
     * Processing after successful login
     */
    public function handleSuccessfulLogin(string $identifier): void
    {
        $modelClass = $this->getLoginAttemptModelClass();

        // Record successful login (with behavioral analysis data)
        $behaviorService = app(\App\Services\LoginBehaviorService::class);
        $behaviorService->recordLoginAttempt($identifier, request(), true);

        // Clear failed attempt records
        $modelClass::clearFailedAttempts($identifier);
    }

    /**
     * Processing after failed login
     *
     * @param  array  $settings  Settings array (optional)
     * @return array Lockout information
     */
    public function handleFailedLogin(Request $request, string $identifier, array $settings = []): array
    {
        // Record failed attempt
        $this->recordLoginAttempt($request, $identifier, false);

        $lockoutInfo = [
            'is_locked_out' => false,
            'remaining_attempts' => null,
            'lockout_minutes' => null,
            'is_ip_locked_out' => false,
        ];

        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (! $this->isLockoutEnabled($enabledKey)) {
            return $lockoutInfo;
        }

        $modelClass = $this->getLoginAttemptModelClass();

        // Retrieve current failure count
        $failedAttempts = $modelClass::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow($timeWindowKey)
        );

        $maxAttempts = $this->getMaxAttempts($maxAttemptsKey);

        // Check lockout status
        if ($failedAttempts >= $maxAttempts) {
            $lockoutInfo['is_locked_out'] = true;
            $lockoutInfo['lockout_minutes'] = $this->getLockoutRemainingMinutes($identifier, $settings);
        } else {
            $lockoutInfo['remaining_attempts'] = $maxAttempts - $failedAttempts;
        }

        // Also check IP address-based lockout
        $lockoutInfo['is_ip_locked_out'] = $this->isIpLockedOut($request->ip(), $settings);

        return $lockoutInfo;
    }

    /**
     * Retrieve detailed lockout status information
     *
     * @param  array  $settings  Settings array (optional)
     */
    public function getLockoutStatus(string $identifier, string $ipAddress, array $settings = []): array
    {
        return [
            'is_enabled' => $this->isLockoutEnabled($settings['enabled_key'] ?? 'login_attempt_limit_enabled'),
            'is_locked_out' => $this->isLockedOut($identifier, $settings),
            'is_ip_locked_out' => $this->isIpLockedOut($ipAddress, $settings),
            'remaining_minutes' => $this->getLockoutRemainingMinutes($identifier, $settings),
            'max_attempts' => $this->getMaxAttempts($settings['max_attempts_key'] ?? 'login_attempt_max_attempts'),
            'time_window' => $this->getTimeWindow($settings['time_window_key'] ?? 'login_attempt_time_window'),
            'lockout_duration' => $this->getLockoutDuration($settings['lockout_duration_key'] ?? 'login_attempt_lockout_duration'),
        ];
    }

    /**
     * Abstract method to retrieve settings value (defined in implementation class)
     *
     * @param  mixed  $default
     * @return mixed
     */
    abstract protected function getSetting(string $key, $default = null);

    /**
     * Retrieve boolean settings value
     */
    protected function getBooleanSetting(string $key, bool $default = false): bool
    {
        return (bool) $this->getSetting($key, $default);
    }

    /**
     * Retrieve integer settings value
     */
    protected function getIntegerSetting(string $key, int $default = 0): int
    {
        return (int) $this->getSetting($key, $default);
    }
}
