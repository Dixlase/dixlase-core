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
 */

return [
    'heading' => 'Login Attempt Limit Settings',
    'description' => 'Manage login attempt limits and lockout settings.',

    // Default Login Attempt Settings
    'default_login_attempt_settings' => 'Default Login Attempt Limit Settings',
    'default_login_attempt_description' => 'Default login attempt limits applied to members and when plugin custom settings are disabled.',

    // Basic Settings
    'basic_settings' => 'Basic Settings',
    'enabled' => 'Enable Login Attempt Limits',
    'enabled_help' => 'When enabled, accounts or IP addresses will be temporarily locked out after the specified number of failed login attempts.',
    'max_attempts' => 'Maximum Attempts',
    'max_attempts_help' => 'Maximum login attempts per account (1-100)',
    'max_attempts_ip' => 'Max Attempts per IP',
    'max_attempts_ip_help' => 'Maximum login attempts per IP address (1-200)',
    'time_window' => 'Time Window',
    'time_window_help' => 'Time window for counting login attempts (1-1440 minutes)',
    'lockout_duration' => 'Lockout Duration',
    'lockout_duration_help' => 'Waiting time when locked out (1-10080 minutes)',
    'lockout_notification_enabled' => 'Enable Lockout Notifications',
    'lockout_notification_help' => 'When enabled, administrators will receive email notifications when lockouts occur',

    // Hint
    'plugin_custom_hint' => 'Plugins (such as DixlaseUsers) can set their own login attempt limits. If a plugin has custom settings enabled, those will take precedence.',

    // Two-Factor Authentication Detailed Settings
    'two_fa_detailed_settings' => 'Two-Factor Authentication Details',
    'two_fa_detailed_settings_description' => 'Manage detailed settings for two-factor authentication including code expiration, resend interval, attempt limits, and recovery codes.',

    // Login Identifier Mode Settings
    'login_identifier_mode_settings' => 'Login Identifier Settings',
    'login_identifier_mode_description' => 'Select which identifiers (email address / account name) are accepted for admin login.',
    'login_identifier_mode' => 'Login Identifier Mode',

    'settings_updated' => 'Login settings have been updated.',
    'updated' => 'Login settings have been updated.',

    // Login notification settings
    'login_notification_settings' => 'Login Notification Settings',
    'login_notification_description' => 'Manage notification settings when members log in.',
    'login_notification_mode' => 'Login Notification Mode',
];
