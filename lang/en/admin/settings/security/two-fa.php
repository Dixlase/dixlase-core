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
    // Basic
    'updated' => 'Authentication settings have been updated.',

    // Two-Factor Authentication Basic Settings
    'two_fa_basic_settings' => 'Two-Factor Authentication Basic Settings',
    'two_fa_basic_settings_description' => 'Configure basic behavior of two-factor authentication.',
    'mail_server_test_warning' => 'To use two-factor authentication, please complete mail server settings and all mail tests in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Base Settings</a>.',

    // Passkey Device Management Settings
    'passkey_device_management' => 'Passkey Device Management Settings',
    'passkey_device_management_description' => 'Configure passkey device registration limits and other settings.',
    'passkey_max_devices' => 'Maximum Registered Devices',
    'passkey_max_devices_help' => 'Maximum number of passkey devices a single user can register. Can be set between 1-10 devices.',
    'devices_unit' => 'devices',

    // Two-Factor Authentication Detailed Settings
    'two_fa_detailed_settings' => 'Two-Factor Authentication Detailed Settings',
    'two_fa_detailed_settings_description' => 'Configure detailed behavior of two-factor authentication.',

    'two_fa_expire_minutes' => 'Code Expiration',
    'two_fa_expire_minutes_help' => 'Authentication code expiration time in minutes. Can be set between 1-60 minutes.',

    'two_fa_resend_interval_seconds' => 'Resend Interval',
    'two_fa_resend_interval_seconds_help' => 'Time in seconds before authentication code can be resent. Can be set between 30-300 seconds.',

    'two_fa_max_attempts' => 'Maximum Attempts',
    'two_fa_max_attempts_help' => 'Maximum number of authentication code input attempts. Can be set between 3-10 attempts.',

    'two_fa_attempt_window' => 'Attempt Window',
    'two_fa_attempt_window_help' => 'Time window in minutes for counting attempts. Can be set between 5-60 minutes.',

    'two_fa_lockout_duration' => 'Lockout Duration',
    'two_fa_lockout_duration_help' => 'Lockout duration in minutes when attempts are exceeded. Can be set between 5-1440 minutes (24 hours).',

    'two_fa_lockout_notification_enabled' => 'Lockout Notification',
    'two_fa_lockout_notification_enabled_help' => 'When enabled, sends email notification to administrators when lockout occurs.',

    'two_fa_recovery_codes_count' => 'Recovery Codes Count',
    'two_fa_recovery_codes_count_help' => 'Number of recovery codes to generate. Can be set between 5-20 codes.',

    'two_fa_recovery_code_regenerate_interval' => 'Recovery Code Regeneration Interval',
    'two_fa_recovery_code_regenerate_interval_help' => 'Number of days before recovery codes can be regenerated. Can be set between 30-365 days.',
];
