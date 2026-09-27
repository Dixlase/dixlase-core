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
    'password-reset' => [
        'subject' => 'Password Reset Notification',
        'greeting' => 'Hello!',
        'line1' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'line2' => 'This password reset link will expire in :count minutes.',
        'line3' => 'If you did not request a password reset, no further action is required.',
        'regards' => 'Regards',
    ],

    'login-notification' => [
        'subject_user' => '[Login Notification] :name, a login was detected on :context',
        'subject_system' => '[System Notification] A login was detected on :context',
        'title' => 'Login Notification',
        'user_message' => ':name, a login was detected on :context.',
        'system_message' => 'System Notification',
        'details_title' => 'Login Details:',
        'datetime' => 'Date/Time:',
        'ip_address' => 'IP Address:',
        'user_agent' => 'User-Agent:',
        'user_id' => 'User ID:',
        'member_id' => 'Member ID:',
        'security_notice' => 'If you do not recognize this login, please change your password immediately.',
        'access_site' => 'Access Site',
        'action_subcopy' => 'If you\'re having trouble clicking the ":button_text" button, copy and paste the URL below into your web browser:',
        'regards' => 'Regards,',
        'context' => [
            'admin' => 'Admin Panel',
        ],
    ],

    'verify-email' => [
        'member' => [
            'subject' => 'Verify Email Address',
            'subject_account' => 'Verify Your :type Account',
            'greeting' => 'Hello :name!',
            'message_create' => 'Thank you for registering. Please click the button below to verify your email address.',
            'message_email_change' => 'Your email address has been changed. Please click the button below to confirm the change.',
            'message_resend' => 'Email verification is required. Please click the button below to complete verification.',
            'action_verify_account' => 'Verify Email Address',
            'action_change_email' => 'Confirm Email Change',
            'manual_verification' => 'If the button above doesn\'t work, copy and paste the following URL into your web browser:',
            'expiration' => 'This link will expire in :minutes minutes.',
            'security_notice' => 'If you did not create an account, no further action is required.',
            'regards' => 'Regards,',
        ],
        'member_verification_completed' => [
            'subject' => 'Member Account Verified',
            'greeting' => 'Hello :name!',
            'message' => 'Your email address has been verified.',
            'member_info' => '[Member Information]',
            'name' => 'Name',
            'email' => 'Email Address',
            'login_info' => 'You can log in to the admin panel from the following URL.',
            'url_info' => '[URL Information]',
            'front_url' => 'Front Page URL',
            'admin_url' => 'Admin Panel URL',
            'thanks' => 'Thank you for using our service.',
            'regards' => 'Regards,',
        ],
    ],

    'two-fa' => [
        'security_notice' => 'If you do not recognize this login attempt, someone may have tried to access your account. Please change your password immediately or contact your system administrator.',
        'regards' => 'Regards,',
        'details_title' => 'Login Attempt Details',
        'ip_address' => 'IP Address',
        'user_agent' => 'Browser/Device',
        'datetime' => 'Date/Time',
        'timestamp' => 'Date/Time',
        'email' => [
            'subject' => '[:app_name] Two-Factor Authentication Code',
            'greeting' => 'Two-Factor Authentication',
            'message' => 'Please enter the following authentication code to log in.',
            'instructions' => 'This code is valid for 10 minutes. If you did not request this, please ignore this email.',
        ],
        'device' => [
            'subject' => 'Login Approval Request',
            'title' => 'Login Attempt from a New Device',
            'greeting' => ':name',
            'message' => 'A login was attempted from a new device.',
            'action_prompt' => 'Please approve or deny this login.',
            'approve_button' => 'Approve Login',
            'deny_button' => 'Deny Login',
        ],
    ],

    'lockout' => [
        'subject' => '[Security Alert] Admin Login Lockout',
        'title' => 'Admin Login Lockout Notification',
        'message' => 'A login lockout has occurred on the admin panel. This may indicate unauthorized login attempts.',
        'details' => 'Lockout Details',
        'identifier' => 'Email Address',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'datetime' => 'Date/Time',
        'timestamp' => 'Occurred At',
        'settings' => 'Lockout Settings',
        'max_attempts' => 'Maximum Attempts',
        'time_window' => 'Time Window',
        'lockout_duration' => 'Lockout Duration',
        'times' => ':count times',
        'minutes' => ':minutes minutes',
        'action_required' => 'For security reasons, please review this login lockout and take appropriate action as necessary.',
        'thanks' => 'Regards,',
    ],

    'file-integrity' => [
        'subject' => '[:site_name] File Integrity Alert - :status',
        'title' => 'File Integrity Alert',
        'greeting' => 'A file integrity check on :site_name has detected issues.',
        'intro' => 'Scan result: :status',
        'scan_info' => 'Scan Information',
        'scan_date' => 'Scan Date',
        'status' => 'Status',
        'status_critical' => 'Critical',
        'status_warning' => 'Warning',
        'status_unknown' => 'Unknown',
        'files_scanned' => 'Files Scanned',
        'issues_summary' => 'Detected Issues',
        'changed_files' => 'Changed Files',
        'added_files' => 'Added Files',
        'removed_files' => 'Removed Files',
        'suspicious_files' => 'Suspicious Files',
        'action_required' => 'Please review the details and take action as necessary.',
        'view_details_button' => 'View Details',
        'thanks' => 'Regards,',
    ],

    'extension' => [
        'subject_installed' => '[:app_name] :type ":name" has been installed',
        'subject_uninstalled' => '[:app_name] :type ":name" has been uninstalled',
        'subject_enabled' => '[:app_name] :type ":name" has been enabled',
        'subject_disabled' => '[:app_name] :type ":name" has been disabled',
        'subject_unhealthy_warning' => '[:app_name Warning] A :type with health concerns has been operated',
        'type_plugin' => 'Plugin',
        'type_theme' => 'Theme',
        'greeting' => 'Dear System Administrator',
        'message_installed' => ':type ":name" has been installed.',
        'message_uninstalled' => ':type ":name" has been uninstalled.',
        'message_enabled' => ':type ":name" has been enabled.',
        'message_disabled' => ':type ":name" has been disabled.',
        'message_unhealthy_warning' => 'A :type with a health status other than "Healthy" has been operated. Please review the details.',
        'details_title' => 'Operation Details',
        'extension_name' => 'Extension Name',
        'extension_type' => 'Type',
        'operation' => 'Operation',
        'operation_installed' => 'Install',
        'operation_uninstalled' => 'Uninstall',
        'operation_enabled' => 'Enable',
        'operation_disabled' => 'Disable',
        'operated_by' => 'Operated By',
        'operated_at' => 'Operated At',
        'health_status' => 'Health Status',
        'health_healthy' => 'Healthy',
        'health_warning' => 'Warning',
        'health_needs_attention' => 'Needs Attention',
        'health_not_verified' => 'Not Verified',
        'version' => 'Version',
        'unhealthy_notice' => 'This extension has a health status of ":level". We recommend reviewing the features and permissions it uses.',
        'auto_notification' => 'This notification is sent automatically based on your security settings.',
        'regards' => 'Regards,',
    ],

    'member-notification' => [
        'admin_notification' => [
            'member_verified' => [
                'subject' => 'Member Account Verification Complete',
                'greeting' => 'Dear System Administrator',
                'title' => 'Member Account Verification Complete',
                'message' => 'A member account has been verified.',
                'member_info' => '[Member Information]',
                'name' => 'Name',
                'email' => 'Email Address',
                'verified_at' => 'Verified At',
                'login_available' => 'This member is now able to log in.',
                'urls' => '[URL Information]',
                'front_url' => 'Front Page URL',
                'admin_url' => 'Admin Panel URL',
                'notification_time' => 'Notification Time',
                'regards' => 'Regards,',
            ],
        ],
    ],

    'send_success' => 'The email has been sent.',
    'queued_success' => 'The email has been queued for sending.',
];
