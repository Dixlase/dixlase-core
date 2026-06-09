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
    'heading' => 'Mail Settings',
    'mail_server_settings' => 'Mail Server Settings',
    'admin_email_settings' => 'System Administrator Email Address',
    'admin_email_settings_description' => 'Set the system administrator email address. Used as the destination for error notifications and important system information.',
    'admin_email' => 'Administrator Email',
    'admin_email_help' => 'Enter the system administrator email address.',
    'admin_email_mail_test_required' => 'Mail server test is not complete. To use error notifications, please complete connection test, send test, and receive test.',
    'settings_updated' => 'Mail settings have been updated.',
    'test_session_cleared' => 'Mail test session has been cleared.',
    'mail_test_complete' => 'Mail Function Test Complete',
    'mail_test_incomplete' => 'Mail Function Test Incomplete',
    'mail_receive_test_completed' => 'Mail receive test completed. Please save the settings.',
    'notification_email_help' => 'Enter the email address to receive system error notifications.',
    'notification_mail_test_required' => 'To use the error notification function, please complete all mail function tests above.',
    'view_messages' => [
        'mail_test_complete' => 'Mail Function Test Complete',
        'mail_test_incomplete' => 'Mail Function Test Incomplete',
        'mail_test_warning_features' => 'To use lockout notification, password reset, login notification, and two-factor authentication features in member global settings, please complete all mail tests.',
        'mail_test_warning_temporary' => 'Test results are temporarily saved. Settings and test results will not be saved until you press the update button.',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receive Confirmation Test',
        'test_passed' => 'Test Passed',
        'test_not_completed' => 'Not Completed',
        'mail_receive_test_completed' => 'Mail receive test completed. Please save the settings.',
    ],
    'mail_verification_success' => [
        'title' => 'Mail Receive Confirmation Complete',
        'heading' => 'Mail receive confirmation completed',
        'description' => 'Mail function test completed successfully.',
        'already_verified_heading' => 'Mail Receive Already Confirmed',
        'already_verified_description' => 'This mail receive confirmation has already been completed.',
        'next_steps_title' => 'Next Steps',
        'next_steps' => [
            'close_window' => 'Close this window',
            'save_settings' => 'Save settings to confirm test results',
            'data_saved' => 'Data has been saved',
        ],
        'next_steps_install' => [
            'close_window' => 'Close this window',
            'continue_install' => 'Continue with installation',
        ],
        'important_notice_title' => 'Important Notice',
        'important_notice' => 'Test results are temporary. They will not be confirmed until you save the settings.',
        'close_button' => 'Close Window',
        'completed_message' => 'Mail receive confirmation completed.',
    ],
    'controller_messages' => [
        'settings_updated' => 'Basic settings have been updated.',
        'test_session_cleared' => 'Mail test session has been cleared.',
        'mailer_not_supported' => 'Connection test is not supported for :mailer driver.',
        'connection_success' => 'Connection to mail server was successful.',
        'connection_failed' => 'Connection to mail server failed: :error',
        'verification_token_invalid' => 'Invalid verification token.',
        'verification_error' => 'An error occurred during mail receive confirmation: :error',
    ],
    'validation' => [
        'app_name_required' => 'Application name is required.',
        'locale_required' => 'Please select a language.',
        'timezone_invalid' => 'Please select a valid timezone.',
        'mail_mailer_required' => 'Please select a mail driver.',
        'mail_host_required' => 'Please enter the mail host.',
        'mail_port_required' => 'Please enter the mail port.',
        'mail_port_numeric' => 'Mail port must be a number.',
        'maintenance_mode_required' => 'Please select a maintenance mode setting.',
    ],
    'connection_test_required' => 'Please run the connection test first.',
    'last_test_date' => 'Last Test Date',
    'mail_server_warning' => 'Mail Server Not Configured',
    'mail_server_warning_message' => 'Mail sending function is not available because mail server configuration and testing have not been completed.',
    'mail_server_test_passed' => 'Mail server connection test passed. Mail sending function is available.',
    'save_settings_reminder' => 'Please save settings',
    'save_settings_reminder_message' => 'Please save settings to apply changes.',
];
