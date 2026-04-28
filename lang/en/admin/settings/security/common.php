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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
    // Categories
    'categories' => [
        'auth' => 'Authentication',
        'login' => 'Login',
        'session' => 'Session',
        'captcha' => 'CAPTCHA',
        'ip' => 'IP Restriction',
        'csp' => 'CSP',
        'extension' => 'Extensions',
        'notification' => 'Notification',
        'api' => 'API',
        'lockdown' => 'Lockdown',
    ],

    // Setting labels
    'labels' => [
        // Authentication
        'two_fa_enabled' => 'Two-Factor Authentication',
        'two_fa_mode' => 'Two-Factor Mode',
        'password_min_length' => 'Minimum Password Length',
        'password_require_mixed_case' => 'Require Mixed Case',
        'password_require_numbers' => 'Require Numbers',
        'password_require_symbols' => 'Require Symbols',
        'password_check_pwned' => 'Check Pwned Passwords',

        // Login
        'login_max_attempts' => 'Max Login Attempts',
        'login_lockout_duration' => 'Lockout Duration (minutes)',
        'login_notification_enabled' => 'Login Notification',
        'lockout_notification_enabled' => 'Lockout Notification',

        // Session
        'session_driver' => 'Session Driver',
        'session_lifetime' => 'Session Lifetime (minutes)',
        'session_encrypt' => 'Session Encryption',
        'members_session_lifetime_enabled' => 'Enable Member Session Lifetime',
        'members_session_lifetime' => 'Member Session Lifetime (minutes)',

        // CAPTCHA
        'captcha_enabled' => 'CAPTCHA Enabled',
        'captcha_driver' => 'CAPTCHA Driver',
        'captcha_site_key' => 'Site Key',
        'captcha_secret_key' => 'Secret Key',
        'captcha_google_version' => 'reCAPTCHA Version',
        'captcha_google_min_score' => 'Minimum Score',

        // IP Restriction
        'enable_allowed_admin_ips' => 'Enable Admin IP Allowlist',
        'allowed_admin_ips' => 'Admin Allowed IPs',
        'enable_blocked_admin_ips' => 'Enable Admin IP Blocklist',
        'blocked_admin_ips' => 'Admin Blocked IPs',
        'enable_allowed_front_ips' => 'Enable Front IP Allowlist',
        'allowed_front_ips' => 'Front Allowed IPs',
        'enable_blocked_front_ips' => 'Enable Front IP Blocklist',
        'blocked_front_ips' => 'Front Blocked IPs',

        // CSP
        'csp_enabled' => 'CSP Enabled',
        'csp_mode' => 'CSP Mode',
        'csp_log_violations' => 'Log CSP Violations',
        'csp_trusted_domains' => 'Trusted Domains',
        'csp_denied_domains' => 'Denied Domains',
        'csp_blocklist_check_enabled' => 'Enable Blocklist Check',
        'csp_blocklist_action' => 'Blocklist Action',

        // Extensions
        'extension_security_preset' => 'Security Preset',
        'extension_require_signature' => 'Require Signature',
        'extension_require_permission_definition' => 'Require Permission Definition',
        'extension_allow_undefined_permissions' => 'Allow Undefined Permissions',
        'extension_plugin_max_health_level' => 'Plugin Max Health Level',
        'extension_theme_max_health_level' => 'Theme Max Health Level',
        'extension_allow_logic_themes' => 'Allow Logic Themes',
        'extension_permission_mismatch_action' => 'Permission Mismatch Action',
        'extension_notify_on_install' => 'Notify on Install',
        'extension_notify_on_uninstall' => 'Notify on Uninstall',
        'extension_log_operations' => 'Log Extension Operations',

        // Notification
        'notification_enabled' => 'Enable System Notifications',
        'notification_log_levels' => 'Notification Log Levels',

        // API
        'api_rate_limit_enabled' => 'Enable Rate Limiting',
        'api_rate_limit_per_minute' => 'Requests per Minute',
        'api_signature_required' => 'Require Signature',
        'api_timestamp_tolerance' => 'Timestamp Tolerance (seconds)',
    ],

    // Help text
    'help' => [
        'password_check_pwned' => 'Check passwords against the Have I Been Pwned database.',
        'csp_mode' => 'development: Relaxed, standard: Standard, strict: Strict',
        'extension_security_preset' => 'relaxed: Relaxed, balanced: Balanced, strict: Strict',
    ],
];
