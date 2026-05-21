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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    // Mail Test Features
    'title' => 'Mail Test',
    'description' => 'Test mail server settings.',
    'description_admin_email' => 'Test email will be sent to admin email address.',
    'three_stage_test_incomplete' => '3-stage mail test incomplete',
    'three_stage_test_complete' => '3-stage mail test complete',
    'connection_test' => 'Server Connection Test',
    'send_test' => 'Mail Send Test',
    'receive_test' => 'Mail Receipt Verification',
    'test_passed' => 'Test Passed',
    'test_not_completed' => 'Not Executed',
    'mail_test_complete' => 'Mail functionality test complete',
    'mail_test_incomplete' => 'Mail functionality test incomplete',
    'mail_test_warning_features' => 'To use member settings lockout notifications, password reset, login notifications, and two-factor authentication features, please complete all mail tests.',
    'mail_test_warning_temporary' => 'Test results are temporarily stored. Settings and test results will not be saved until you press the update button.',
    'mail_receive_test_completed' => 'Mail receive test completed. Please save settings.',
    'connection_test_required' => 'Please run connection test first.',

    // Test Mail Content
    'test_mail' => [
        'subject' => 'Mail Send Test',
        'greeting' => 'Hello!',
        'body' => 'This is a test email from :app_name.

Mail send test has completed successfully.
To complete mail receipt verification, please click the following link:

:verification_url

Clicking this link will complete the full mail functionality test.',
        'body_with_verification' => 'This is a test email from :app_name.

Mail send test has completed successfully.
To complete mail receipt verification, please click the following link:

:verification_url

Clicking this link will complete the full mail functionality test.',
        'test_details_title' => 'Mail Send Test',
        'app_name' => 'Application Name:',
        'test_datetime' => 'Test Execution Date & Time:',
        'verification_required' => 'To confirm that this email is being received properly, please click the button below.',
        'verify_button' => 'Confirm Email Receipt',
        'manual_verification' => 'If the button does not work, please copy and access the following URL directly in your browser:',
        'regards' => 'Regards,',
        'success' => 'Test email sent successfully. Please check your inbox.',
        'failed' => 'Mail send failed: :error',
    ],

    // Mail Test Functions (Common)
    'test_functions' => [
        'test_connection_button' => 'Test Connection',
        'test_mail_button' => 'Send Test Mail',
        'testing' => 'Testing',
        'testing_connection' => 'Connecting...',
        'testing_mail' => 'Sending...',
        'mail_test_description' => 'You can test mail server connection and mail sending.',
        'mail_test_description_2' => 'To enable mail sending functionality, you must run both connection test and mail sending test.',
        'connection_test_error' => 'An error occurred during connection test. Please verify your mail server settings.',
        'mail_send_test_error' => 'Failed to send test mail: :error',
        'mail_send_test_success' => 'Test mail sent to :email.',
        'mail_send_test_failed' => 'Failed to send test mail: :error',
        'connection_test_not_supported' => 'The :mailer mailer does not support connection testing.',
        'connection_test_success' => 'Successfully connected to mail server.',
        'connection_test_failed' => 'Failed to connect to mail server',
        'mail_connection_test_not_supported' => 'The :mailer mailer does not support connection testing.',
        'mail_connection_test_success' => 'Successfully connected to mail server.',
        'mail_connection_test_failed' => 'Failed to connect to mail server: :error',
        'send_test_success' => 'Test mail sent to :email.',
        'send_test_failed' => 'Failed to send test mail',
        'test_mail_success' => 'Test mail has been sent successfully. Please check your inbox and complete the verification by clicking the link in the email.',
        'test_mail_failed' => 'Failed to send mail: :error',
        'connection_test_required' => 'Please complete the connection test before running the mail send test.',
        'member_not_found' => 'Logged-in member not found. Please log in again.',
        'from_address_required' => 'The "from" email address is empty. The test mail is sent to the "from" email address.',
        'three_stage_test_incomplete' => 'Mail test is incomplete',
        'three_stage_test_complete' => 'Mail test is complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receive Verification',
    ],

    // 3-Stage Mail Test Functionality
    'test_advanced' => [
        'test_email_subject' => 'Mail Server Configuration Test',
        'test_email_body' => 'This is a test email for mail server configuration. If you can receive this email normally, your mail server settings are working correctly.',
        'test_email_body_with_verification' => "This is a test email for mail server configuration. If you can receive this email normally, your mail server settings are working correctly.\n\nTo complete the mail receipt verification, please click the following link:\n:verification_url\n\nClicking this link will complete the mail receipt test.",
        'connection_test_not_supported' => ':mailer mailer does not support connection testing.',
        'connection_test_success' => 'Successfully connected to the mail server.',
        'connection_test_failed' => 'Failed to connect to the mail server',
        'smtp_connection_error' => 'Connection error: :error (Error code: :errno)',
        'smtp_response_invalid' => 'Invalid response from SMTP server: :response',
        'smtp_starttls_failed' => 'Failed to start STARTTLS: :response',
        'smtp_tls_crypto_failed' => 'Failed to enable TLS encryption',
        'smtp_auth_login_failed' => 'AUTH LOGIN command failed: :response',
        'smtp_username_auth_failed' => 'Username authentication failed: :response',
        'smtp_password_auth_failed' => 'Password authentication failed: :response',
        'send_test_success' => 'Test email sent to :email.',
        'send_test_failed' => 'Failed to send test email',
        'verification_token_invalid' => 'Mail verification token is invalid.',
        'verification_error' => 'An error occurred during mail verification: :error',
        'verification_success' => [
            'title' => 'Mail Receipt Verification Complete',
            'heading' => 'Mail Receipt Verification Completed',
            'description' => 'It has been confirmed that your mail server settings are working correctly.',
            'next_steps_title' => 'Next Steps',
            'next_steps' => [
                'close_window' => 'Close this window',
                'continue_install' => 'Return to the installation screen to continue setup',
            ],
            'close_button' => 'Close Window',
            'completed_message' => 'Mail receipt verification completed',
        ],
        'three_stage_test_incomplete' => '3-stage mail test incomplete',
        'three_stage_test_complete' => '3-stage mail test complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receipt Verification',
    ],

    // JavaScript Messages
    'js_messages' => [
        'test_route_not_set' => 'Test route is not configured',
        'mail_test_route_not_set' => 'Mail test route is not configured',
        'connection_test_first' => 'Please run the connection test first',
        'testing' => 'Testing...',
        'mail_test_failed_side_note' => 'Please verify your mail server settings or check the mail server status.',
        'connection_test_success_default' => 'Connection test succeeded',
        'connection_test_failed_default' => 'Connection test failed',
        'mail_test_success_default' => 'Mail sending test succeeded',
        'mail_test_failed_default' => 'Mail sending test failed',
        'connection_test_error' => 'An error occurred during connection test.',
        'mail_test_error' => 'An error occurred during mail sending test',
        'mail_receive_test_completed' => 'Mail receive test completion detected',
        'mail_receive_verified' => 'Mail receipt verification completed',
    ],
];
