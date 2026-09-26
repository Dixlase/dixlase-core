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

return [

    // Admin / front IP allow and deny lists are not configured here. They are
    // security settings stored in the database, edited on the admin security
    // settings screen; `php artisan security:reset-ip` clears them from the
    // command line.

    // Whether to force SSL
    'force_ssl' => env('FORCE_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | Conservative defaults: HSTS is OFF unless the operator explicitly opts in.
    | This is intentional for the beta period — once a browser receives an HSTS
    | header it caches the directive for `max_age` seconds and the operator
    | cannot revoke it from the server side. Mistakes (wrong cert, subdomain
    | misconfig) cause multi-day recovery windows. The `preload` directive is
    | worse: removal requires submission to a Chromium-maintained list and
    | propagation takes weeks.
    |
    | Recommended escalation path (see SECURITY.md "HSTS posture"):
    |   1. Beta:                 max_age=0       (off)
    |   2. After HTTPS proven:   max_age=300     (5 min — recoverable in minutes)
    |   3. After ~1 day stable:  max_age=86400   (1 day)
    |   4. After ~1 week stable: max_age=31536000 (1 year — production posture)
    |   5. After ~1 month:       + include_subdomains
    |   6. Last:                 + preload (irreversible without list removal)
    |
    | The header is only emitted for requests that are already over HTTPS
    | (otherwise the directive is pointless and the browser ignores it).
    |
    */
    'hsts' => [
        // max-age in seconds. 0 disables HSTS entirely (no header sent).
        'max_age' => (int) env('HSTS_MAX_AGE', 0),

        // Apply HSTS to all subdomains. Only enable after every subdomain is
        // known-good over HTTPS — a single HTTP-only subdomain becomes
        // unreachable for visitors whose browser cached this directive.
        'include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', false),

        // Submit to the browser preload list. Effectively irreversible without
        // submission to https://hstspreload.org. Only enable in fully-mature
        // production deployments. Requires max_age >= 31536000 (1 year),
        // include_subdomains=true, and the `preload` directive itself.
        'preload' => (bool) env('HSTS_PRELOAD', false),
    ],

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
        'daily_seal_retention_days' => env('AUDIT_LOG_SEAL_RETENTION_DAYS', 730), // 2 years
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
