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
    'max_attempts' => 'Max Attempts',
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
    'plugin_custom_hint' => 'Plugins (such as DixlaseUsers) can set their own login attempt limits. When plugin custom settings are enabled, they take priority.',
    
    'settings_updated' => 'Login attempt limit settings have been updated.',
];
