<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Dashboard',
    'description' => 'You can check the site overview.',

    // Mode toggle
    'simple_mode' => 'Simple',
    'detailed_mode' => 'Detailed',

    // Security overview
    'security_overview' => 'Security Overview',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_mode_active' => 'Maintenance mode is currently active. The site is not accessible to visitors.',
    'maintenance_mode_inactive' => 'Maintenance mode is off. The site is accessible.',
    'safe_mode' => 'Safe Mode',
    'safe_mode_active' => 'Safe mode is currently active. Some features are restricted.',
    'safe_mode_inactive' => 'Safe mode is off. All features are available.',
    'csp_mode' => 'CSP Mode',
    'csp_development_warning' => 'Production environment is using development CSP mode (Report-Only). Consider switching to standard mode.',
    'csp_mode_ok' => 'CSP mode is properly configured.',
    'debug_mode' => 'Debug Mode',
    'debug_mode_warning' => 'Debug mode is enabled in production. This may expose sensitive information.',
    'debug_mode_ok' => 'Debug mode is disabled.',
    'two_fa_status' => 'Two-Factor Authentication',
    'two_fa_enabled' => 'Enabled (:method)',
    'two_fa_disabled' => 'Not configured. Setting up 2FA is recommended.',

    // Mail status
    'mail_status' => 'Mail Server',
    'mail_not_configured' => 'Mail server settings are incomplete. Email delivery may fail.',
    'mail_using_log_driver' => 'Using ":driver" driver. Emails will not be delivered.',
    'mail_configured' => 'Mail server is properly configured.',

    // CAPTCHA status
    'captcha_status' => 'CAPTCHA',
    'captcha_not_configured' => 'CAPTCHA is not configured. Setting it up is recommended to prevent spam.',
    'captcha_configured' => 'CAPTCHA is properly configured.',

    // System info
    'system_info' => 'System Information',
    'php_version' => 'PHP Version',
    'laravel_version' => 'Laravel Version',
    'dixlase_version' => 'Dixlase Version',

    // Content overview
    'content_overview' => 'Content Overview',

    // Plugin notifications
    'plugin_notifications' => 'Plugin Notifications',
    'view_settings' => 'View Settings',
    'status_info' => 'Info',

    // Status labels
    'status_ok' => 'OK',
    'status_warning' => 'Warning',
    'status_recommendation' => 'Recommended',
];
