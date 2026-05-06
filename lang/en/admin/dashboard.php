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
    'heading' => 'Dashboard',
    'description' => 'You can check the site overview.',

    // Site health
    'site_health' => 'Site Health',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_mode_active' => 'Maintenance mode is currently active. The site is not accessible to visitors.',
    'maintenance_mode_inactive' => 'Maintenance mode is off. The site is accessible.',
    'safe_mode' => 'Safe Mode',
    'safe_mode_active' => 'Safe mode is currently active. Some features are restricted.',
    'safe_mode_inactive' => 'Safe mode is off. All features are available.',
    'environment_settings' => 'Environment',
    'environment_local' => 'Running in local development mode. Set APP_ENV to production before going live.',
    'environment_staging' => 'Running in staging mode.',
    'environment_production' => 'Running in production mode.',
    'environment_other' => 'Running in ":env" mode.',
    'https_status' => 'HTTPS',
    'https_force_ssl_enabled' => 'Force SSL is enabled. All requests are redirected to HTTPS.',
    'https_production_no_force' => 'Production environment is not enforcing HTTPS. Enabling Force SSL is recommended.',
    'https_current_secure' => 'Current request is over HTTPS. Force SSL is not enabled.',
    'https_disabled' => 'HTTPS is not in use. Enabling HTTPS is strongly recommended.',
    'csp_mode' => 'CSP Mode',
    'csp_disabled' => 'CSP is disabled. This significantly increases XSS attack risk.',
    'csp_development_warning' => 'CSP is running in development mode (Report-Only). Switch to standard mode for production.',
    'csp_mode_ok' => 'CSP mode is properly configured.',
    'debug_mode' => 'Debug Mode',
    'debug_mode_warning' => 'Debug mode is enabled in production. This may expose sensitive information.',
    'debug_mode_dev_ok' => 'Debug mode is enabled (development/staging).',
    'debug_mode_ok' => 'Debug mode is disabled.',
    'extension_mode' => 'Extension Security Settings',
    'extension_mode_strict' => 'Strict preset is active. Signatures required and only healthy extensions allowed.',
    'extension_mode_balanced' => 'Balanced preset is active. Trusted sources and monitored extensions allowed.',
    'extension_mode_development' => 'Development preset is active. Security checks are relaxed — not recommended for production.',
    'extension_mode_custom' => 'Custom preset is active with user-defined settings.',
    'public_key_status' => 'Public Key Authority',
    'public_key_available' => 'Public keys are being fetched from the key authority.',
    'public_key_unavailable' => 'Public key authority is unavailable. Plugin signature verification is disabled.',
    'error_notification_status' => 'Error Notifications',
    'error_notification_enabled' => 'Error notifications are enabled. Critical events will be sent by email.',
    'error_notification_disabled' => 'Error notifications are disabled. Enabling them is recommended for production.',
    'file_integrity_status' => 'File Integrity',
    'file_integrity_ok' => 'No file integrity issues detected in the latest scan.',
    'file_integrity_warning' => ':count file(s) changed since the last baseline scan.',
    'file_integrity_critical' => ':count suspicious file(s) detected. Investigate immediately.',
    'file_integrity_no_baseline' => 'No baseline scan has been run yet. Running an initial scan is recommended.',
    'two_fa_status' => 'Two-Factor Authentication',
    'two_fa_enabled' => 'Enabled (:method)',
    'two_fa_disabled' => 'Not configured. Setting up 2FA is recommended.',

    // Mail status
    'mail_status' => 'Mail Server',
    'mail_not_configured' => 'Mail server settings are incomplete. Email delivery may fail.',
    'mail_using_log_driver' => 'Using ":driver" driver. Emails will not be delivered.',
    'mail_configured' => 'Mail server is properly configured.',
    'mail_test_not_completed' => 'Mail server is configured, but connection/send tests have not been completed yet.',

    // CAPTCHA status
    'captcha_status' => 'CAPTCHA',
    'captcha_not_configured' => 'CAPTCHA is not configured. Setting it up is recommended to prevent spam.',
    'captcha_configured' => 'CAPTCHA is properly configured.',
    'captcha_test_not_completed' => 'CAPTCHA is configured, but authentication test has not been completed yet.',

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

    // Extension overview
    'extension_overview' => 'Extension Overview',
    'plugins' => 'Plugins',
    'themes' => 'Themes',
    'installed' => 'Installed',
    'enabled' => 'Enabled',
    'health_overview' => 'Health Overview',
    'manage_plugins' => 'Manage Plugins',
    'no_audits' => 'No plugin audits have been performed yet.',
    'updates_available_label' => 'Updates Available',
    'updates_available_summary' => ':plugins plugin(s) / :themes theme(s)',
    'updates_all_up_to_date' => 'All up to date',

    // Member overview
    'member_overview' => 'Member Overview',
    'total_members' => 'Total',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'role_distribution' => 'Role Distribution',
    'two_fa_rate_label' => '2FA Enabled',
    'recent_logins' => 'Recent Logins',
    'no_recent_logins' => 'No recent login records.',
    'manage_members' => 'Manage Members',

    // Recent activity
    'recent_activity' => 'Recent Activity',
    'activity_system' => 'System',
    'activity_no_entries' => 'No activity recorded in the last 24 hours.',
    'activity_failed_count' => ':count failed',
    'activity_warning_count' => ':count warnings',
    'activity_last_24h' => 'Last 24 hours',
    'view_logs' => 'View Logs',
    'outcome_success' => 'Success',
    'outcome_failure' => 'Failure',
    'outcome_denied' => 'Denied',
    'outcome_pending' => 'Pending',
    'outcome_unknown' => 'Unknown',

    // Action labels
    'action_login' => 'Login',
    'action_logout' => 'Logout',
    'action_login_failed' => 'Login Failed',
    'action_login_identifier_check' => 'Identifier Check',
    'action_login_identifier_not_found' => 'Identifier Not Found',
    'action_passkey_auth_success' => 'Passkey Auth',
    'action_passkey_auth_failed' => 'Passkey Auth Failed',
    'action_new_device_login' => 'New Device Login',
    'action_password_changed' => 'Password Changed',
    'action_password_reset' => 'Password Reset',
    'action_email_changed' => 'Email Changed',
    'action_two_fa_enabled' => '2FA Enabled',
    'action_two_fa_disabled' => '2FA Disabled',
    'action_two_fa_code_sent' => '2FA Code Sent',
    'action_two_fa_code_verified' => '2FA Verified',
    'action_two_fa_code_failed' => '2FA Code Failed',
    'action_recovery_code_used' => 'Recovery Code Used',
    'action_passkey_registered' => 'Passkey Registered',
    'action_passkey_revoked' => 'Passkey Revoked',
    'action_device_trusted' => 'Device Trusted',
    'action_device_blocked' => 'Device Blocked',
    'action_device_removed' => 'Device Removed',
    'action_ip_blocked' => 'IP Blocked',
    'action_ip_allowed' => 'IP Allowed',
    'action_lockout_triggered' => 'Lockout Triggered',
    'action_lockout_released' => 'Lockout Released',
    'action_step_up_auth_required' => 'Step-up Auth Required',
    'action_step_up_auth_completed' => 'Step-up Auth Completed',
    'action_session_created' => 'Session Created',
    'action_session_destroyed' => 'Session Destroyed',
    'action_forced_logout' => 'Forced Logout',
    'action_plugin_installed' => 'Plugin Installed',
    'action_plugin_enabled' => 'Plugin Enabled',
    'action_plugin_disabled' => 'Plugin Disabled',
    'action_plugin_uninstalled' => 'Plugin Uninstalled',
    'action_plugin_updated' => 'Plugin Updated',
    'action_theme_installed' => 'Theme Installed',
    'action_theme_enabled' => 'Theme Enabled',
    'action_theme_disabled' => 'Theme Disabled',
    'action_theme_uninstalled' => 'Theme Uninstalled',
    'action_theme_updated' => 'Theme Updated',
    'action_settings_updated' => 'Settings Updated',
    'action_member_created' => 'Member Created',
    'action_member_updated' => 'Member Updated',
    'action_member_deleted' => 'Member Deleted',
    'action_role_changed' => 'Role Changed',

    // Status labels
    'status_ok' => 'OK',
    'status_warning' => 'Warning',
    'status_recommendation' => 'Recommended',
    'status_critical' => 'Critical',
];
