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

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Mail sending emergency bypass service (break glass)
 *
 * Temporarily bypass mail-dependent features when SMTP server fails
 *
 * Target features:
 * - 2FA (skip email authentication)
 * - Password reset (skip email sending and allow direct reset)
 *
 * Features:
 * - Time-limited (max 120 minutes)
 * - Specifiable scope (two_fa, password_reset, all)
 * - Always recorded in audit log
 * - Automatic expiration
 */
class MailBypassService
{
    /**
     * Cache key
     */
    private const CACHE_KEY_BYPASS = 'mail_bypass';

    private const CACHE_KEY_BYPASS_DATA = 'mail_bypass_data';

    /**
     * Maximum bypass time (minutes)
     */
    public const MAX_BYPASS_MINUTES = 120;

    /**
     * Enable bypass
     */
    public static function enable(int $minutes, string $scope, string $reason): bool
    {
        // Limit to maximum time
        $minutes = min($minutes, self::MAX_BYPASS_MINUTES);

        $expiresAt = now()->addMinutes($minutes);

        $data = [
            'active' => true,
            'scope' => $scope,
            'reason' => $reason,
            'enabled_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'minutes' => $minutes,
        ];

        try {
            // Store in cache (with expiration)
            Cache::put(self::CACHE_KEY_BYPASS, true, $expiresAt);
            Cache::put(self::CACHE_KEY_BYPASS_DATA, $data, $expiresAt);

            Log::channel('admin_activity')->warning('Mail bypass enabled', [
                'scope' => $scope,
                'reason' => $reason,
                'minutes' => $minutes,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('Failed to enable mail bypass', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disable bypass
     */
    public static function disable(): void
    {
        Cache::forget(self::CACHE_KEY_BYPASS);
        Cache::forget(self::CACHE_KEY_BYPASS_DATA);

        Log::channel('admin_activity')->info('Mail bypass disabled');
    }

    /**
     * Check if bypass is active
     */
    public static function isActive(?string $scope = null): bool
    {
        if (! Cache::get(self::CACHE_KEY_BYPASS, false)) {
            return false;
        }

        // If scope is specified, check scope as well
        if ($scope !== null) {
            $data = Cache::get(self::CACHE_KEY_BYPASS_DATA, []);
            $bypassScope = $data['scope'] ?? 'two_fa';

            // 'all' scope matches everything
            if ($bypassScope === 'all') {
                return true;
            }

            return $bypassScope === $scope;
        }

        return true;
    }

    /**
     * Check if 2FA email authentication should be skipped
     */
    public static function shouldSkipTwoFaMail(): bool
    {
        return self::isActive('two_fa');
    }

    /**
     * Check if password reset email should be skipped
     */
    public static function shouldSkipPasswordResetMail(): bool
    {
        return self::isActive('password_reset');
    }

    /**
     * Get bypass status
     */
    public static function getStatus(): array
    {
        $isActive = Cache::get(self::CACHE_KEY_BYPASS, false);
        $data = Cache::get(self::CACHE_KEY_BYPASS_DATA, []);

        if (! $isActive || empty($data)) {
            return [
                'active' => false,
                'scope' => null,
                'reason' => null,
                'enabled_at' => null,
                'expires_at' => null,
                'remaining_minutes' => 0,
            ];
        }

        $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
        $remainingMinutes = max(0, now()->diffInMinutes($expiresAt, false));

        return [
            'active' => true,
            'scope' => $data['scope'],
            'reason' => $data['reason'],
            'enabled_at' => $data['enabled_at'],
            'expires_at' => $data['expires_at'],
            'remaining_minutes' => $remainingMinutes,
        ];
    }
}
