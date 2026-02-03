<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    'expire_settings' => 'Authentication Expiration',
    'expire_minutes' => 'Authentication Expiration',
    'expire_minutes_help' => 'Set the expiration time for two-factor authentication codes in minutes.',
    'resend_interval_seconds' => 'Authentication Email Resend Interval',
    'resend_interval_seconds_help' => 'Set the waiting time in seconds before authentication emails can be resent.',
    'attempt_limit_settings' => '2FA Attempt Limit Settings',
    'max_attempts' => 'Maximum Attempts',
    'max_attempts_help' => 'Set the maximum number of two-factor authentication attempts.',
    'attempt_window' => 'Attempt Limit Time Window',
    'attempt_window_help' => 'Set the time window in minutes for counting attempts.',
    'lockout_duration' => 'Lockout Duration',
    'lockout_duration_help' => 'Set the lockout duration in minutes when attempt limit is exceeded.',
    'lockout_notification_enabled' => '2FA Lockout Notification',
    'lockout_notification_enabled_help' => 'Notify administrators when locked out from two-factor authentication.',
    'recovery_code_settings' => 'Recovery Code Settings',
    'recovery_codes_count' => 'Recovery Code Generation Count',
    'recovery_codes_count_help' => 'Set the number of recovery codes to generate.',
    'recovery_code_regenerate_interval' => 'Recovery Code Regeneration Interval',
    'recovery_code_regenerate_interval_help' => 'Set the waiting time in hours before recovery codes can be regenerated.',
];
