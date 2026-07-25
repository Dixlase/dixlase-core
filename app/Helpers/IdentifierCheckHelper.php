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

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Login identifier verification helper
 *
 * Common functionality for verifying existence of email address or account name
 * Can be used for both administrator login and user login
 */
class IdentifierCheckHelper
{
    /**
     * Execute identifier verification (with rate limiting)
     *
     * @param  string  $login  Login identifier (email address or account name)
     * @param  string  $ipAddress  IP address
     * @param  string  $userModelClass  User model class name
     * @param  array  $settings  Lockout settings
     * @param  string  $context  Context ('admin' or 'user')
     * @return array ['exists' => bool, 'has_passkey' => bool, 'user' => Model|null]
     *
     * @throws ValidationException
     */
    public static function checkWithRateLimit(
        string $login,
        string $ipAddress,
        string $userModelClass,
        array $settings,
        string $context = 'admin'
    ): array {
        // Skip if rate limiting is disabled
        if (! ($settings['enabled'] ?? false)) {
            return self::performCheck($login, $ipAddress, $userModelClass, $context);
        }

        return self::performCheck($login, $ipAddress, $userModelClass, $context);
    }

    /**
     * Execute identifier verification
     *
     * @param  string  $login  Login identifier
     * @param  string  $ipAddress  IP address
     * @param  string  $userModelClass  User model class name
     * @param  string  $context  Context
     *
     * @throws ValidationException
     */
    protected static function performCheck(
        string $login,
        string $ipAddress,
        string $userModelClass,
        string $context
    ): array {
        // Timing attack countermeasure: always wait a constant time (100-300ms)
        $delayMs = random_int(100, 300);
        usleep($delayMs * 1000);

        // User search based on login identifier mode
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        if ($context === 'admin') {
            // Administrator context: restrict search field based on LoginIdentifierMode
            $mode = \App\Enums\LoginIdentifierMode::tryFrom(
                (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', \App\Enums\LoginIdentifierMode::EmailOrAccountName->value)
            ) ?? \App\Enums\LoginIdentifierMode::EmailOrAccountName;

            if ($isEmail && $mode->supportsEmail()) {
                $user = $userModelClass::where('email', $login)->first();
            } elseif (! $isEmail && $mode->supportsAccountName()) {
                $user = $userModelClass::where('account_name', $login)->first();
            } else {
                $user = null;
            }
        } else {
            // User context: search by email address or account name
            $user = $userModelClass::where('email', $login)
                ->orWhere('account_name', $login)
                ->first();
        }

        if ($user) {
            // User existence verification successful
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_IDENTIFIER_CHECK, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $user,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::info('Login identifier check successful', [
                'user_id' => $user->id,
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // Check passkey registration status
            $hasPasskey = self::hasPasskey($user);

            return [
                'exists' => true,
                'has_passkey' => $hasPasskey,
                'user' => $user,
            ];
        } else {
            // User existence check failed (security log)
            AuditLog::logSecurity(AuditLog::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, [
                'severity' => AuditLog::SEVERITY_WARNING,
                'outcome' => AuditLog::OUTCOME_FAILURE,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::warning('Login identifier not found', [
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // Record login attempt (for lockout protection)
            \App\Models\MemberLoginAttempt::recordAttempt(
                $login,
                $ipAddress,
                request()->userAgent() ?? 'Unknown',
                false
            );

            // Check lockout status
            $lockoutInfo = \App\Helpers\LoginLockoutHelper::checkLockoutStatus(
                request(),
                $login,
                self::getLockoutSettings(\App\Models\SecuritySetting::class),
                \App\Models\SecuritySetting::class
            );

            // Throw exception only when locked out (access restriction is maintained)
            if ($lockoutInfo['is_ip_locked_out']) {
                $lockoutDuration = $lockoutInfo['settings']['lockout_duration'] ?? 30;
                throw ValidationException::withMessages([
                    'login' => __('auth.lockout', ['minutes' => $lockoutDuration]),
                ]);
            } elseif ($lockoutInfo['is_locked_out']) {
                throw ValidationException::withMessages([
                    'login' => __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]),
                ]);
            }

            // Return the same response format whether user exists or not (prevent user enumeration)
            return [
                'exists' => false,
                'has_passkey' => false,
                'user' => null,
            ];
        }
    }

    /**
     * Check if passkey is registered
     *
     * @param  mixed  $user  User model
     */
    protected static function hasPasskey($user): bool
    {
        // Check if webauthnCredentials relation exists
        if (method_exists($user, 'webauthnCredentials')) {
            return $user->webauthnCredentials()->count() > 0;
        }

        // Check if twoFaPasskeys relation exists (for administrator)
        if (method_exists($user, 'twoFaPasskeys')) {
            return $user->twoFaPasskeys()->count() > 0;
        }

        // Check if passkeys relation exists (general purpose)
        if (method_exists($user, 'passkeys')) {
            return $user->passkeys()->count() > 0;
        }

        return false;
    }

    /**
     * Get lockout settings
     *
     * @param  string  $settingModelClass  Settings model class name
     */
    public static function getLockoutSettings(string $settingModelClass): array
    {
        return [
            'enabled' => (bool) $settingModelClass::getValue('login_attempt_limit_enabled', false),
            'max_attempts' => (int) $settingModelClass::getValue('login_attempt_max_attempts', 5),
            'max_attempts_ip' => (int) $settingModelClass::getValue('login_attempt_max_attempts_ip', null),
            'time_window' => (int) $settingModelClass::getValue('login_attempt_time_window', 15),
            'lockout_duration' => (int) $settingModelClass::getValue('login_attempt_lockout_duration', 30),
        ];
    }
}
