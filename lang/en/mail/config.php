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
    'mailers' => [
        'smtp' => 'SMTP (Standard)',
        'sendmail' => 'Sendmail',
        'log' => 'Log (File Output)',
        'array' => 'Array (Memory Storage)',
        'failover' => 'Failover (Redundancy)',
        'mailgun' => 'Mailgun (External)',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ],
    'encryptions' => [
        '' => 'None',
        'tls' => 'TLS (Recommended)',
        'ssl' => 'SSL',
    ],
    
    // Mail Server Settings Fields
    'server_settings' => [
        'mailer' => 'Mailer',
        'mail_host' => 'Host',
        'mail_port' => 'Port',
        'mail_username' => 'Username',
        'mail_password' => 'Password',
        'mail_encryption' => 'Encryption',
        'mail_from_address' => 'From Address',
        'mail_from_name' => 'From Name',
    ],
    
    // Mail Settings & Test Common
    'settings' => [
        'mailer' => 'Mailer',
        'mail_host' => 'Host Name',
        'mail_port' => 'Port Number',
        'mail_username' => 'Username',
        'mail_password' => 'Password',
        'mail_encryption' => 'Encryption Method',
        'mail_from_address' => 'From Email Address',
        'mail_test' => 'Mail Send Test',
        'mail_test_description' => 'Send a test email with current settings. Test email will be sent to the from address.',
        'mail_test_description_2' => 'To enable mail sending functionality, you must complete both connection test and mail send test.',
        'test_connection_button' => 'Test Connection',
        'test_mail_button' => 'Send Test Email',
        'testing_connection' => 'Connecting...',
        'testing_mail' => 'Sending...',
        'mail_test_error' => 'An error occurred during mail send test.',
        'last_test_date' => 'Last Test Date',
        'mail_server_warning' => 'Mail Server Not Configured',
        'mail_server_warning_message' => 'Mail sending functionality is unavailable because mail server settings and tests have not been completed.',
        'mail_server_test_passed' => 'Mail server connection test passed. Mail sending functionality is available.',
        'save_settings_reminder' => 'Please Save Settings',
        'save_settings_reminder_message' => 'Please save settings to apply changes.',
    ],
    
    // Validation Messages
    'validation' => [
        'mail_mailer_required' => 'Please select a mailer.',
        'mail_host_required' => 'Please enter mail host.',
        'mail_port_required' => 'Please enter mail port.',
        'mail_port_numeric' => 'Mail port must be numeric.',
        'mail_from_address_email' => 'From address must be a valid email address.',
    ],
    
    // Controller Messages
    'controller_messages' => [
        'settings_updated' => 'Settings have been updated.',
        'test_session_cleared' => 'Test session has been cleared.',
        'mailer_not_supported' => 'Mailer ":mailer" does not support connection testing.',
        'connection_success' => 'Mail server connection verified successfully.',
        'connection_failed' => 'Failed to connect to mail server: :error',
        'verification_token_invalid' => 'Mail verification token is invalid.',
        'verification_error' => 'An error occurred during mail verification: :error',
    ],
];
