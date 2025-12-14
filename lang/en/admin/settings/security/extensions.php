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
    'heading' => 'Extension Security',
    'security' => [
        'title' => 'Extension Security Settings',
        'description' => 'Configure security policies for plugin and theme installation and activation.',
        'preset_label' => 'Security Preset',
        'preset_help' => 'Selecting a preset automatically applies recommended settings. Select "Custom" to configure individually.',
        'dev_only' => 'Development Only',
        'custom_mode_hint' => 'Select "Custom" preset to change individual settings.',
        'preset' => [
            'strict' => 'Strict Mode',
            'strict_description' => 'Signature required, permission definition required. Most secure setting.',
            'balanced' => 'Balanced Mode',
            'balanced_description' => 'Signature recommended, allows up to "Warning" level. Suitable for general operation.',
            'development' => 'Development Mode',
            'development_description' => 'Allows unsigned and undefined. For development/testing environments.',
            'custom' => 'Custom',
            'custom_description' => 'Customize settings individually.',
        ],
        'signature_settings' => 'Signature Requirements',
        'require_signature' => 'Require Signature',
        'require_signature_help' => 'When enabled, prohibits installation/activation of unsigned plugins and themes.',
        'permission_settings' => 'Permission Definition Requirements',
        'require_permission_definition' => 'Require Permission Definition',
        'require_permission_definition_help' => 'When enabled, prohibits installation of extensions without permission information in plugin.json/theme.json.',
        'allow_undefined_permissions' => 'Allow Undefined Permissions',
        'allow_undefined_permissions_help' => 'Allow installation of extensions without permission information. Warnings will be displayed.',
        'plugin_health_level' => 'Plugin Allowed Health Level',
        'plugin_health_level_help' => 'Set the health level of plugins allowed for installation/activation.',
        'theme_health_level' => 'Theme Allowed Health Level',
        'theme_health_level_help' => 'Set the health level of themes allowed for installation/activation.',
        'current_setting' => 'Current Setting',
        'health_level' => [
            'healthy' => 'Healthy',
            'warning' => 'Warning',
            'needs_attention' => 'Needs Attention',
            'not_verified' => 'Not Verified',
        ],
        'health_level_short' => [
            'healthy' => 'Healthy',
            'warning' => 'Warning',
            'needs_attention' => 'Needs Attention',
            'not_verified' => 'Not Verified',
        ],
        'health_level_description' => [
            'healthy' => 'Extensions using only basic features. Most secure setting.',
            'warning' => 'Uses some extended features. Extensions requiring additional permissions beyond basic features.',
            'needs_attention' => 'Uses more features. Includes extensions with database access or external communication.',
            'not_verified' => 'Allows unverified extensions. All extensions can be installed, but thorough review is recommended.',
        ],
        'logic_themes' => 'Themes with Logic',
        'allow_logic_themes' => 'Allow Themes with Logic',
        'allow_logic_themes_help' => 'Allow installation of themes containing PHP logic (ServiceProvider, middleware, etc.).',
        'theme_types_title' => 'About Theme Types',
        'theme_type_pure' => 'Pure Theme: Consists only of template files (Blade/HTML/CSS/JS). Lighter health check.',
        'theme_type_logic' => 'Logic Theme: Executes PHP logic via ThemeServiceProvider. Same scanning and permission control as plugins.',
        'permission_mismatch' => 'Permission Mismatch Behavior',
        'permission_mismatch_help' => 'Configure behavior when declared permissions don\'t match actual code.',
        'mismatch_action' => [
            'warn' => 'Warn Only',
            'warn_description' => 'Only display warning when permission mismatch is detected, but allow installation/activation.',
            'block' => 'Block',
            'block_description' => 'Prohibit installation/activation when permission mismatch is detected.',
        ],
    ],
    'notification' => [
        'title' => 'Extension Operation Notifications',
        'description' => 'Send email notifications to system administrator when plugins or themes are operated. Notification destination is the system administrator email in basic settings.',
        'notify_on_install' => 'Notify on Install',
        'notify_on_install_help' => 'Send email notification when plugins or themes are installed.',
        'notify_on_uninstall' => 'Notify on Uninstall',
        'notify_on_uninstall_help' => 'Send email notification when plugins or themes are uninstalled.',
        'notify_on_enable' => 'Notify on Enable',
        'notify_on_enable_help' => 'Send email notification when plugins or themes are enabled.',
        'notify_on_disable' => 'Notify on Disable',
        'notify_on_disable_help' => 'Send email notification when plugins or themes are disabled.',
        'notify_on_unhealthy' => 'Notify Health Warnings',
        'notify_on_unhealthy_help' => 'Send warning email when extensions with health other than "Healthy" are added, installed, or enabled.',
        'log_operations' => 'Log Operations',
        'log_operations_help' => 'Record extension operation history to log file.',
    ],
    'settings_updated' => 'Extension security settings have been updated.',
];
