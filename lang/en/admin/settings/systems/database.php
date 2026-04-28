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
    'heading' => 'Database Management',
    'description' => 'Clean up old database records to maintain system performance',
    'core_cleanup_heading' => 'Core Table Cleanup',
    'core_cleanup_description' => 'Cleanup configuration for tables used by Dixlase core system.',
    'default_retention' => 'Default retention: :days days',
    'expired_only' => 'Expired only',
    'all_cleanup_button' => 'Clean Up All',
    'all_cleanup_description' => 'Clean up all database tables with a common retention period',
    'all_cleanup_warning' => 'This operation cannot be undone.',
    'all_tables' => 'All Tables',
    'all_days_label' => 'Common Retention Days',
    'all_days_help' => 'Setting 0 days will delete all records. Use individual cleanup for specific retention periods.',
    'info_title' => 'Cleanup Target Description',
    'info_login_attempts' => 'Delete old login attempt records.',
    'info_password_reset' => 'Delete expired password reset tokens.',
    'info_two_fa_attempts' => 'Delete old two-factor authentication attempt records.',
    'info_two_fa_tokens' => 'Delete expired two-factor authentication tokens.',
    'info_recovery_codes' => 'Delete old used recovery codes.',
    'info_passkeys' => 'Permanently delete old deleted PASSKEYs.',
    'info_cache' => 'Delete expired cache entries.',
    'info_sessions' => 'Delete old session records.',
    'login_attempts' => [
        'name' => 'Login Attempts',
        'description' => 'Clean up old login attempt records',
        'invalid_days' => 'Days must be a non-negative integer.',
    ],
    'info_panel' => [
        'title' => 'Important Notes',
        'notes' => [
            'irreversible' => 'Database cleanup is an irreversible operation. We recommend backing up necessary data before execution.',
            'performance' => 'Regular cleanup helps improve system performance.',
            'production' => 'Execute carefully in production environments, preferably during maintenance windows.',
            'defaults' => 'Default settings are based on recommended values. Adjust as needed.',
        ],
    ],
    'modal' => [
        'title' => 'Database Cleanup Confirmation',
        'message' => 'Do you want to execute this operation?',
        'message_single' => 'Do you want to clean up :name?',
        'message_all' => 'Do you want to clean up all database tables?',
        'message_plugin' => 'Do you want to clean up :name from plugin ":plugin"?',
    ],
    'plugin_cleanup_heading' => 'Plugin Data Cleanup',
    'plugin_cleanup_description' => 'Cleanup targets provided by enabled plugins. Defined in each plugin\'s plugin.json.',
    'plugin_cleanup_description_config' => 'Cleanup targets provided by enabled plugins. Defined in each plugin\'s database-cleanup.php.',
    'password_reset_tokens' => [
        'name' => 'Password Reset Tokens',
        'description' => 'Clean up old password reset token records',
        'invalid_days' => 'Days must be a non-negative integer.',
    ],
    'two_fa_attempts' => [
        'name' => 'Two-Factor Authentication Attempts',
        'description' => 'Clean up old two-factor authentication attempt records',
        'invalid_days' => 'Days must be a non-negative integer.',
    ],
    'two_fa_tokens' => [
        'name' => 'Two-Factor Authentication Tokens (Email)',
        'description' => 'Clean up expired two-factor authentication (email) codes',
        'default_days' => '7 days',
    ],
    'recovery_codes' => [
        'name' => 'Recovery Codes',
        'description' => 'Clean up used/invalidated recovery codes',
        'default_days' => '90 days',
    ],
    'passkeys' => [
        'name' => 'Two-Factor PASSKEY (Biometric)',
        'description' => 'Clean up old deleted two-factor PASSKEYs (biometric)',
        'default_days' => '90 days',
    ],
    'backup_records' => [
        'name' => 'Backup Records',
        'description' => 'Delete expired or removed backup records',
        'default_days' => '365 days',
    ],
    'restore_records' => [
        'name' => 'Restore Records',
        'description' => 'Delete restore history records older than the specified retention period',
        'default_days' => '365 days',
    ],
    'audit_logs' => [
        'name' => 'Audit Logs',
        'description' => 'Delete audit log records older than the specified retention period',
        'default_days' => '365 days',
    ],
    'api_request_logs' => [
        'name' => 'API Request Logs',
        'description' => 'Delete API request log records older than the specified retention period',
        'default_days' => '90 days',
    ],
    'cache_data' => [
        'name' => 'Cache Data',
        'description' => 'Clean up expired cache entries and locks',
        'default_days' => 'Expired only',
    ],
    'sessions' => [
        'name' => 'Sessions',
        'description' => 'Clean up old session records',
        'default_days' => '7 days',
    ],
    'cleanup_success' => 'Successfully cleaned up :count record(s).',
    'cleanup_error' => 'Error occurred during cleanup: :error',
    'confirm_cleanup' => 'Are you sure you want to clean up :type records?',
    'days_label' => 'Retention Days',
    'days_zero_info' => 'Setting 0 days will delete all records',
    'cleanup_button' => 'Clean Up',
];
