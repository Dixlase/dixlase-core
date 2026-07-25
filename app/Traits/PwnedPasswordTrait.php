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

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Have I Been Pwned API integration trait
 *
 * Provides functionality to check if a password is included in the breach database
 * Reusable in user management plugins
 */
trait PwnedPasswordTrait
{
    /**
     * Check if password has been breached
     *
     * @param  string  $password  Password to check
     * @return array ['is_pwned' => bool, 'count' => int, 'error' => string|null]
     */
    public function checkPwnedPassword(string $password): array
    {
        try {
            // Hash password with SHA-1
            $hash = strtoupper(sha1($password));
            $prefix = substr($hash, 0, 5);
            $suffix = substr($hash, 5);

            // Request to Have I Been Pwned API v3
            $apiEndpoint = config('security.pwned_passwords.api_endpoint', 'https://api.pwnedpasswords.com');
            $timeout = config('security.pwned_passwords.timeout', 5);

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'Dixlase-Password-Checker/1.0',
                    'Add-Padding' => 'true', // Keep response size constant to protect privacy
                ])
                ->get("{$apiEndpoint}/range/{$prefix}");

            if (! $response->successful()) {
                Log::warning('Have I Been Pwned API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'is_pwned' => false,
                    'count' => 0,
                    'error' => 'API request failed',
                ];
            }

            // Parse response
            $hashes = $response->body();
            $lines = explode("\n", $hashes);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                [$hashSuffix, $count] = explode(':', $line);

                if (strtoupper($hashSuffix) === $suffix) {
                    return [
                        'is_pwned' => true,
                        'count' => (int) $count,
                        'error' => null,
                    ];
                }
            }

            return [
                'is_pwned' => false,
                'count' => 0,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Pwned password check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'is_pwned' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if password dictionary attack protection is enabled
     *
     * @param  string  $settingKey  Settings key (default: 'pwned_password_check_enabled')
     */
    public function isPwnedPasswordCheckEnabled(string $settingKey = 'pwned_password_check_enabled'): bool
    {
        // Use SecuritySetting class if it exists
        if (class_exists('\App\Models\SecuritySetting')) {
            return filter_var(
                \App\Models\SecuritySetting::get($settingKey, false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // Fallback: use config value
        return config('security.pwned_password_check_enabled', false);
    }

    /**
     * Check password safety (with dictionary attack protection)
     *
     * @param  string  $password  Password to check
     * @param  string  $settingKey  Settings key
     * @return array ['is_safe' => bool, 'message' => string, 'pwned_info' => array]
     */
    public function validatePasswordSafety(string $password, string $settingKey = 'pwned_password_check_enabled'): array
    {
        // Always safe when dictionary attack protection is disabled
        if (! $this->isPwnedPasswordCheckEnabled($settingKey)) {
            return [
                'is_safe' => true,
                'message' => '',
                'pwned_info' => ['is_pwned' => false, 'count' => 0, 'error' => null],
            ];
        }

        $pwnedInfo = $this->checkPwnedPassword($password);

        // Log warning on API error, but allow password
        if ($pwnedInfo['error']) {
            Log::warning('Pwned password check failed, allowing password', [
                'error' => $pwnedInfo['error'],
            ]);

            return [
                'is_safe' => true,
                'message' => __('validation.pwned_password_api_error'),
                'pwned_info' => $pwnedInfo,
            ];
        }

        // If password has been breached
        if ($pwnedInfo['is_pwned']) {
            return [
                'is_safe' => false,
                'message' => __('validation.pwned_password_found', ['count' => number_format($pwnedInfo['count'])]),
                'pwned_info' => $pwnedInfo,
            ];
        }

        // Password is safe
        return [
            'is_safe' => true,
            'message' => '',
            'pwned_info' => $pwnedInfo,
        ];
    }
}
