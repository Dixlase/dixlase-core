<?php

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
    
    // Password Reset Email
    'reset_password' => [
        'subject' => 'Reset Password Notification',
        'greeting' => 'Hello!',
        'line1' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'line2' => 'This password reset link will expire in :count minutes.',
        'line3' => 'If you did not request a password reset, no further action is required.',
        'regards' => 'Regards,',
    ],

    // Login Notification Email
    'login_notification' => [
        'subject_user' => '[Login Notification] :name, you have logged in',
        'subject_system' => '[System Notification] Admin login detected',
        'title' => 'Login Notification',
        'user_message' => ':name, you have logged in.',
        'system_message' => 'System Notification',
        'details_title' => 'Login Details:',
        'datetime' => 'Date & Time:',
        'ip_address' => 'IP Address:',
        'user_agent' => 'User-Agent:',
        'security_notice' => 'If you do not recognize this login, please change your password immediately.',
        'regards' => 'Regards',
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
        'mail_test_description' => 'Send a test email with current settings. Test email will be sent to the from email address.',
        'mail_test_description_2' => 'To enable mail sending functionality, be sure to run connection test and mail send test.',
        'test_connection_button' => 'Connection Test',
        'test_mail_button' => 'Send Test Mail',
        'testing_connection' => 'Connecting...',
        'testing_mail' => 'Sending...',
        'mail_test_error' => 'An error occurred during mail send test.',
        'last_test_date' => 'Last Test Date',
        'mail_server_warning' => 'Mail Server Not Configured',
        'mail_server_warning_message' => 'Mail sending functionality is not available because mail server settings and tests have not been executed.',
        'mail_server_test_passed' => 'Mail server connection test passed. Mail sending functionality is available.',
        'save_settings_reminder' => 'Please save settings',
        'save_settings_reminder_message' => 'Be sure to save settings to enable changes.',
    ],

    // Mail Test Features
    'test' => [
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
    ],

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

    // Mail Verification Success Page
    'verification_success' => [
        'title' => 'Mail Receipt Verification Complete',
        'heading' => 'Mail receipt verification completed',
        'description' => 'Mail functionality test completed successfully.',
        'next_steps_title' => 'Next Steps',
        'next_steps' => [
            'close_window' => 'Please close this window',
            'save_settings' => 'Press the "Update" button on the basic settings screen to save settings',
            'data_saved' => 'Test results will be saved and mail functionality will be enabled',
        ],
        'important_notice_title' => 'Important Notice',
        'important_notice' => 'Test results are temporarily stored. Be sure to save settings.',
        'close_button' => 'Close Window',
    ],

    // Controller Messages
    'controller_messages' => [
        'settings_updated' => 'Settings have been updated.',
        'test_session_cleared' => 'Test session has been cleared.',
        'mailer_not_supported' => 'Mailer ":mailer" does not support connection testing.',
        'connection_success' => 'Mail server connection verified successfully.',
        'connection_failed' => 'Mail server connection failed: :error',
        'verification_token_invalid' => 'Mail verification token is invalid.',
        'verification_error' => 'An error occurred during mail verification: :error',
    ],
];
