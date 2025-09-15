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

    // Two-Factor Authentication Email
    'two_factor' => [
        'default' => [
            'subject' => '[:app_name] Two-Factor Authentication Code',
            'greeting' => 'Hello!',
            'message' => 'Here is your two-factor authentication code for login.',
            'instructions' => 'Please enter this code on the login screen. The code expires in 10 minutes.',
            'security_notice' => 'If you do not recognize this login attempt, please change your password immediately.',
            'regards' => 'Regards',
        ],
        'admin' => [
            'subject' => '[:app_name] Admin Two-Factor Authentication Code',
            'greeting' => 'Hello!',
            'message' => 'Here is your two-factor authentication code for admin login.',
            'instructions' => 'Please enter this code on the admin login screen. The code expires in 10 minutes.',
            'security_notice' => 'If you do not recognize this login attempt, please change your password immediately and contact the system administrator.',
            'regards' => 'Regards',
        ],
        'user' => [
            'subject' => '[:app_name] User Two-Factor Authentication Code',
            'title' => 'User Two-Factor Authentication Code',
            'greeting' => 'Hello!',
            'message' => 'Here is your two-factor authentication code for user login.',
            'code_label' => 'Authentication Code',
            'instructions' => 'Please enter this code on the login screen.',
            'expire_notice' => 'The code expires in :minutes minutes.',
            'security_notice' => 'If you do not recognize this login attempt, please change your password immediately.',
            'thanks' => 'Regards',
        ],
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
        'connection_test_required' => 'Please run connection test first.',
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
        'already_verified_heading' => 'Mail Receive Already Verified',
        'already_verified_description' => 'This email verification has already been completed.',
        'next_steps_title' => 'Next Steps',
        'next_steps' => [
            'close_window' => 'Please close this window',
            'save_settings' => 'Save settings to confirm test results',
            'data_saved' => 'Data has been saved',
        ],
        'next_steps_install' => [
            'close_window' => 'Please close this window',
            'continue_install' => 'Continue with installation',
        ],
        'important_notice_title' => 'Important Notice',
        'important_notice' => 'Test results are temporary. Settings must be saved to confirm.',
        'close_button' => 'Close Window',
        'completed_message' => 'Mail receive verification completed.',
    ],

    // Mail Verification Error Page
    'verification_error' => [
        'title' => 'Mail Verification Error',
        'heading' => 'Mail verification error occurred',
        'invalid_token_description' => 'This email verification link is invalid or expired.',
        'verification_error_description' => 'An error occurred during mail verification processing.',
        'general_error_description' => 'An unexpected error occurred.',
        'solution_title' => 'Solution',
        'solution_steps' => [
            'Please close this window',
            'Send a new test email from the base settings screen',
            'Complete verification using the link in the new email',
        ],
        'close_button' => 'Close Window',
        'error_occurred' => 'Mail verification error occurred.',
    ],

    // Controller messages
    'controller_messages' => [
        'settings_updated' => 'Settings have been updated.',
        'test_session_cleared' => 'Test session has been cleared.',
        'mailer_not_supported' => 'The ":mailer" mailer does not support connection testing.',
        'connection_success' => 'Mail server connection has been successfully verified.',
        'connection_failed' => 'Failed to connect to mail server: :error',
        'verification_token_invalid' => 'Mail verification token is invalid.',
        'verification_error' => 'An error occurred during mail verification: :error',
    ],

    // Mail server settings fields (common)
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

    // Mail test functions (common)
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
        'three_stage_test_incomplete' => 'Mail test is incomplete',
        'three_stage_test_complete' => 'Mail test is complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receive Verification',
    ],

    // Mail verification functions (common)
    'verification' => [
        'verification_token_invalid' => 'Mail verification token is invalid.',
        'verification_error' => 'An error occurred during mail verification: :error',
        'verification_success' => [
            'title' => 'Mail Verification Complete',
            'heading' => 'Mail verification has been completed',
            'description' => 'It has been confirmed that the mail server settings are working correctly.',
            'next_steps_title' => 'Next Steps',
            'next_steps' => [
                'close_window' => 'Please close this window',
                'continue_install' => 'Return to the installation screen and continue with the setup',
                'save_settings' => 'Please save the settings',
                'data_saved' => 'Data is temporarily saved',
            ],
            'close_button' => 'Close Window',
            'completed_message' => 'Mail verification has been completed'
        ],
    ],

    // Validation messages (common)
    'validation' => [
        'mail_mailer_required' => 'Please select a mailer.',
        'mail_host_required' => 'Please enter a mail host.',
        'mail_port_required' => 'Please enter a mail port.',
        'mail_port_numeric' => 'Mail port must be a number.',
        'mail_from_address_email' => 'From address must be a valid email address.',
    ],

    // JavaScript messages (common)
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
        'connection_test_error' => 'An error occurred during connection test. Please verify your mail server settings.',
        'mail_test_error' => 'An error occurred during mail sending test',
        'mail_receive_test_completed' => 'Mail receive test completion detected',
    ],

    // 3-stage mail test functionality
    'test_advanced' => [
        'test_email_subject' => 'Mail Server Configuration Test',
        'test_email_body' => 'This is a test email for mail server configuration. If you can receive this email normally, your mail server settings are working correctly.',
        'test_email_body_with_verification' => "This is a test email for mail server configuration. If you can receive this email normally, your mail server settings are working correctly.\n\nTo complete the mail receipt verification, please click the following link:\n:verification_url\n\nClicking this link will complete the mail receipt test.",
        'connection_test_not_supported' => ':mailer mailer does not support connection testing.',
        'connection_test_success' => 'Successfully connected to the mail server.',
        'connection_test_failed' => 'Failed to connect to the mail server',
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
                'continue_install' => 'Return to the installation screen to continue setup'
            ],
            'close_button' => 'Close Window',
            'completed_message' => 'Mail receipt verification completed'
        ],
        'three_stage_test_incomplete' => '3-stage mail test incomplete',
        'three_stage_test_complete' => '3-stage mail test complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receipt Verification',
    ],

    // Lockout Notification Email
    'lockout_notification' => [
        'subject' => '[Security Alert] Admin Login Lockout Occurred',
        'title' => 'Admin Login Lockout Notification',
        'message' => 'An admin login lockout has occurred in the system. This may indicate unauthorized login attempts.',
        'details' => 'Lockout Details',
        'identifier' => 'Email Address',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'timestamp' => 'Occurrence Time',
        'settings' => 'Lockout Settings',
        'max_attempts' => 'Maximum Attempts',
        'time_window' => 'Time Window',
        'lockout_duration' => 'Lockout Duration',
        'times' => ' times',
        'minutes' => ' minutes',
        'action_required' => 'For security reasons, please review this login lockout and take appropriate action as necessary.',
        'thanks' => 'Thank you for your attention',
    ],

    // Device Authentication Email
    'device_auth' => [
        'subject' => '[Security Notice] New Device Access Approval Request',
        'greeting' => 'Hello!',
        'new_device_detected' => 'A login attempt from a new device has been detected. Please choose whether to approve this access.',
        'device_info' => 'Device Information',
        'device_name' => 'Device Name',
        'ip_address' => 'IP Address',
        'location' => 'Location',
        'unknown_location' => 'Unknown',
        'time' => 'Date & Time',
        'approval_message' => 'If this access is legitimate, please click the button below to approve it.',
        'approve_button' => 'Approve Device',
        'manual_approval' => 'To approve manually, please enter the following code:',
        'security_notice' => 'If you do not recognize this access, please change your password immediately and deny access using the button below.',
        'deny_button' => 'Deny Access',
        'ignore_message' => 'If you ignore this email, access will be automatically denied.',
        'regards' => 'Regards',
    ],
];
