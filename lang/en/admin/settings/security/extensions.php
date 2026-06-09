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
        'signature_required_warning' => 'Signature is required: unsigned plugins and themes cannot be installed or activated.',
        'signature_authority_url_label' => 'Plugin signatures are verified using public keys fetched from the following Authority:',
        'permission_settings' => 'Permission Definition Requirements',
        'require_permission_definition' => 'Require Permission Definition',
        'require_permission_definition_help' => 'When enabled, prohibits installation of extensions without permission information in plugin.json/theme.json.',
        'allow_undefined_permissions' => 'Allow Undefined Permissions',
        'allow_undefined_permissions_help' => 'Allow installation of extensions without permission information. Warnings will be displayed.',
        'plugin_health_level' => 'Plugin Allowed Health Level',
        'plugin_health_level_help' => 'Set the health level of plugins allowed for installation/activation.',
        'theme_health_level' => 'Theme Allowed Health Level',
        'theme_health_level_help' => 'Set the health level of themes allowed for installation/activation.',
        'audit_max_age_days' => 'Audit Scan Expiry (Days)',
        'audit_max_age_days_help' => 'Plugins/themes whose last scan is older than this many days will display a "Scan Expired" badge. Enter a value between 1 and 365 days.',
        'audit_max_age_days_unit' => 'days',
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
    'source' => [
        'title' => 'Extension Sources',
        'description' => 'Configure external sources for downloading and updating plugins and themes.',
        'source_type' => 'Source Type',
        'source_type_help' => 'Select where to download extensions from. Only sources preset by the core are available.',
        'github_description' => 'Download extensions from GitHub repositories using the Releases API.',
        'reference_url' => 'Source URL',
        'owner' => 'Repository Owner',
        'owner_help' => 'GitHub organization or username that owns the extension repositories.',
        'owner_placeholder' => 'e.g. Dixlase',
        'token' => 'Authentication Token (Optional)',
        'token_help' => 'Normally, no input is required. Setting a token increases the API rate limit from 60 to 5,000 requests per hour. This is also required for core or official plugin developers who need access to private repositories. Enter a GitHub Personal Access Token (PAT).',
        'token_placeholder' => 'ghp_...',
        'token_saved' => 'Token is saved',
        'token_not_set' => 'Token is not set',
        'token_clear_hint' => 'Leave empty to keep the current token. Enter a new value to update.',
        'test_connection' => 'Test Connection',
        'testing' => 'Testing...',
        'connection_success' => 'Connection successful',
        'connection_failed' => 'Connection failed',
        'connected_as' => 'Connected as :login',
        'rate_limit_remaining' => 'API rate limit remaining: :count',
        'official_badge' => 'Official',
        'third_party_badge' => 'Third-party',
        'signature_invalid' => 'Signature is invalid',
        'update_check_interval' => 'Update Check Interval',
        'update_check_interval_help' => 'How often to automatically check for extension updates. This setting only takes effect when the Laravel scheduler (`php artisan schedule:run` every minute) is running — see the status indicator below.',
        'interval_daily' => 'Daily (24 hours)',
        'interval_12h' => 'Every 12 hours',
        'interval_6h' => 'Every 6 hours',
        'interval_manual' => 'Manual only',
        'last_checked_at' => 'Last checked: :time',
        'never_checked' => 'Never checked',
        'scheduler' => [
            'label' => 'Laravel scheduler',
            'status_active' => 'Running',
            'status_warning' => 'Delayed (last heartbeat was a while ago)',
            'status_stopped' => 'Stopped (automatic checks will not run)',
            'last_tick' => 'Last heartbeat: :datetime',
            'how_to_fix' => 'On Docker: leave `CRON=true` in .env and re-run setup.sh (verify the `<prefix>-cron` container is running with `docker compose ps`). On other hosts: add `* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1` to the host OS cron.',
        ],
    ],
    'settings_updated' => 'Extension security settings have been updated.',
];
