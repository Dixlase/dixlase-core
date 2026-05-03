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

namespace App\Helpers;

use Illuminate\Support\Facades\Artisan;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class EnvHelper
{
    // snake_case => ENV_KEY
    protected static array $envMap = [
        'app_name' => 'APP_NAME',
        'app_env' => 'APP_ENV',
        'app_debug' => 'APP_DEBUG',
        'locale' => 'APP_LOCALE',
        'fallback_locale' => 'APP_FALLBACK_LOCALE',
        'faker_locale' => 'APP_FAKER_LOCALE',
        'mail_from_address' => 'MAIL_FROM_ADDRESS',
        'mail_mailer' => 'MAIL_MAILER',
        'mail_host' => 'MAIL_HOST',
        'mail_port' => 'MAIL_PORT',
        'mail_username' => 'MAIL_USERNAME',
        'mail_password' => 'MAIL_PASSWORD',
        'mail_encryption' => 'MAIL_ENCRYPTION',
        'maintenance_mode' => 'MAINTENANCE_MODE',
        // Session settings
        'session_driver' => 'SESSION_DRIVER',
        'session_lifetime' => 'SESSION_LIFETIME',
        'session_encrypt' => 'SESSION_ENCRYPT',
    ];

    public static function toEnvKey(string $snakeCaseKey): string
    {
        return static::$envMap[$snakeCaseKey] ?? strtoupper($snakeCaseKey);
    }

    public static function isEnvKey(string $key): bool
    {
        return array_key_exists($key, static::$envMap);
    }

    public static function getEnvMap(): array
    {
        return static::$envMap;
    }

    public static function update(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $envKey = static::toEnvKey($key);
            $escapedKey = preg_quote($envKey, '/');
            $value = str_replace(["\r", "\n"], '', $value);

            // Properly escape and quote the value
            $formattedValue = static::formatEnvValue($value);

            // Replace existing key or append if not found
            $pattern = "/^{$escapedKey}=.*/m";
            $replacement = "{$envKey}={$formattedValue}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);

        sleep(1);

        Artisan::call('config:clear');
    }

    /**
     * Format value appropriately for .env
     *
     * @param  mixed  $value
     */
    protected static function formatEnvValue($value): string
    {
        // Handle null values
        if ($value === null || $value === '') {
            return '';
        }

        // Convert to string
        $value = (string) $value;

        // Handle true/false
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value);
        }

        // No quotes needed for numeric-only values
        if (is_numeric($value)) {
            return $value;
        }

        // Wrap in double quotes if contains spaces, special characters, or #
        if (preg_match('/[\s#\$\(\)\[\]\{\}\|\&\;\<\>\?\*\'\"]/', $value)) {
            // Escape existing double quotes and backslashes
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return "\"{$escaped}\"";
        }

        // Otherwise leave as-is
        return $value;
    }
}
