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

return [

    // IP addresses allowed to access the admin panel
    'allowed_admin_ips' => [
        // '127.0.0.1', // Example: Local IP
        // '192.168.1.10',
        '10.5.1.148',
        '0.0.0.0',
    ],
    // IP addresses denied access to the admin panel
    'blocked_admin_ips' => [
        // '123.456.789.0', // 例: 拒否するIP
    ],

    // IP addresses allowed to access the frontend
    'allowed_frontend_ips' => [
        // 例: 許可するIP
    ],
    // IP addresses denied access to the frontend
    'blocked_frontend_ips' => [
        // 例: 拒否するIP
    ],

    // Whether to force SSL
    'force_ssl' => env('FORCE_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | Password Breach Check Settings
    |--------------------------------------------------------------------------
    |
    | Settings for password breach checking using the Have I Been Pwned API
    | The password itself is not sent, only the first 5 characters of the SHA-1 hash are transmitted
    |
    */
    'pwned_passwords' => [
        // Have I Been Pwned API endpoint
        'api_endpoint' => env('PWNED_PASSWORDS_API_ENDPOINT', 'https://api.pwnedpasswords.com'),

        // API request timeout (seconds)
        'timeout' => env('PWNED_PASSWORDS_TIMEOUT', 5),

        // Behavior on failure: 'fail_open' (allow) or 'fail_closed' (deny)
        'on_failure' => env('PWNED_PASSWORDS_ON_FAILURE', 'fail_open'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention Settings
    |--------------------------------------------------------------------------
    |
    | Settings for audit log retention period, archiving, and cleanup
    |
    */
    'audit_log' => [
        // Log retention period (days) - 0 is unlimited
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),

        // Whether to enable archiving
        'archive_enabled' => env('AUDIT_LOG_ARCHIVE_ENABLED', true),

        // Archive destination (path under storage/app)
        'archive_path' => env('AUDIT_LOG_ARCHIVE_PATH', 'audit-archives'),

        // Archive format: 'json' or 'csv'
        'archive_format' => env('AUDIT_LOG_ARCHIVE_FORMAT', 'json'),

        // Minimum days elapsed before archiving
        'archive_after_days' => env('AUDIT_LOG_ARCHIVE_AFTER_DAYS', 90),

        // Whether to enable automatic cleanup
        'auto_cleanup_enabled' => env('AUDIT_LOG_AUTO_CLEANUP', false),

        // Whether to retain archives during cleanup
        'keep_archives' => env('AUDIT_LOG_KEEP_ARCHIVES', true),

        // Retention period by severity (days) - null uses default
        'retention_by_severity' => [
            'critical' => env('AUDIT_LOG_RETENTION_CRITICAL', null), // Indefinite retention recommended
            'error' => env('AUDIT_LOG_RETENTION_ERROR', null),
            'warning' => env('AUDIT_LOG_RETENTION_WARNING', null),
            'info' => env('AUDIT_LOG_RETENTION_INFO', null),
        ],

        // Retention period for Daily Seal (days)
        'daily_seal_retention_days' => env('AUDIT_LOG_SEAL_RETENTION_DAYS', 730), // 2年
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavior when external dependent services fail
    |--------------------------------------------------------------------------
    |
    | Behavior settings when external API is unavailable
    | 'fail_open': Allow operation (reduced security, availability priority)
    | 'fail_closed': Deny operation (security priority, reduced availability)
    |
    */
    'external_services' => [
        // When CAPTCHA fails
        'captcha_on_failure' => env('CAPTCHA_ON_FAILURE', 'fail_closed'),

        // CAPTCHA timeout (seconds)
        'captcha_timeout' => env('CAPTCHA_TIMEOUT', 10),

        // When GeoIP fails (for future use)
        'geoip_on_failure' => env('GEOIP_ON_FAILURE', 'fail_open'),

        // GeoIP timeout (seconds) (for future use)
        'geoip_timeout' => env('GEOIP_TIMEOUT', 5),
    ],

];
