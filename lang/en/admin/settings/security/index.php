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
    'heading' => 'Security Settings Overview',
    'description' => 'View the overview and status of each security setting.',
    'password_security' => 'Password Breach Check',
    'login_attempt_desc' => 'Login Attempt Limits & Notifications',
    'two_fa_desc' => 'Two-Factor Authentication & Passkey',
    'session_driver' => 'Session Driver',
    'captcha_active' => 'CAPTCHA Active',
    'captcha_test_required' => 'Test Required',
    'captcha_disabled' => 'Disabled',
    'ip_active' => 'IP Restriction Active',
    'ip_inactive' => 'IP Restriction Inactive',
    'csp_mode' => 'Mode',
    'csp_disabled' => 'Disabled',
    'extensions_desc' => 'Plugin/Theme Security Policy',
    'extensions_preset' => 'Security Preset',
    'auto_configured' => 'This setting is automatically configured in Simple Mode.',
    'notifications_active' => 'Notifications Active',
    'notifications_disabled' => 'Notifications Disabled',
    'mail_test_required' => 'Mail Test Required',
    'integrity_ok' => 'No Issues',
    'integrity_warning' => 'Warnings',
    'integrity_critical' => 'Critical Issues',
    'integrity_no_baseline' => 'No Baseline',
    'integrity_not_scanned' => 'Not Scanned',
    'debug_enabled' => 'Debug ON',
    'latest_integrity_scan' => 'Latest File Integrity Scan',
    'scan_date' => 'Scan Date',
    'files_scanned' => 'Files Scanned',
    'status' => 'Status',
    'view_details' => 'View Details',

    'nav' => [
        'password' => 'Password',
        'login_attempt' => 'Login',
        'two_fa' => 'Two-Factor Authentication',
        'captcha' => 'CAPTCHA',
        'session' => 'Session',
        'notifications' => 'Error Notifications',
        'csp' => 'CSP',
        'extensions' => 'Extensions',
        'ip' => 'IP Access Control',
        'integrity' => 'File Integrity',
        'environment' => 'Environment',
    ],
];
