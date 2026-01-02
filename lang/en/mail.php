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
        // Common Messages
        'security_notice' => 'If you did not attempt to log in, a third party may have tried to access your account.  
There is a risk of unauthorized access, so please change your password immediately or contact your system administrator.',
        'regards' => 'Regards',
        'details_title' => 'Login Attempt Details',
        'ip_address' => 'IP Address',
        'user_agent' => 'Browser/Device',
        'timestamp' => 'Date & Time',
        
        // Email Authentication
        'email' => [
            'subject' => '[:app_name] Two-Factor Authentication Code',
            'greeting' => 'Hello!',
            'message' => 'Here is your two-factor authentication code for login.',
            'instructions' => 'Please enter this code on the login screen. The code expires in 10 minutes.',
        ],
        
        // Device Authentication Approval Email
        'device' => [
            'subject' => 'Login Approval Request',
            'title' => 'Login Approval Required',
            'greeting' => 'Hello :name,',
            'message' => 'A login attempt has been made to your account. If you want to approve this login, please click the button below.',
            'action_prompt' => 'Do you want to approve this login?',
            'approve_button' => 'Approve Login',
            'deny_button' => 'Deny Login',
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
        'title' => 'Mail Verification Complete',
        'heading' => 'Mail Verification Completed',
        'description' => 'The mail function test has been completed successfully.',
        'actions' => 'Actions',
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
        'heading' => 'An error occurred during mail verification',
        'invalid_token_description' => 'This mail verification link is invalid or expired.',
        'verification_error_description' => 'An error occurred while processing mail verification.',
        'general_error_description' => 'An unexpected error has occurred.',
        'solution_title' => 'Solution',
        'actions' => 'Actions',
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
        'connection_test_required' => 'Please complete the connection test before running the mail send test.',
        'member_not_found' => 'Logged-in member not found. Please log in again.',
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
        'mail_receive_verified' => 'Mail receipt verification completed',
    ],

    // 3-stage mail test functionality
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

    // Email Verification (Member)
    'member_verify_email' => [
        'subject' => 'Verify Your Email Address',
        'subject_account' => 'Verify Your Member Account',
        'greeting' => 'Hello :name!',
        'message_create' => 'Your member account has been successfully created. Please click the button below to complete your account verification.',
        'message_email_change' => 'Your account email address has been changed. Please click the button below to complete the email address change.',
        'message_resend' => 'Your account verification is required. Please click the button below to complete your account verification.',
        'action_verify_account' => 'Verify Account',
        'action_change_email' => 'Change Email Address',
        'manual_verification' => 'If you cannot click the button, please copy and paste the following URL into your browser:',
        'expiration' => 'This verification link will expire in :minutes minutes.',
        'security_notice' => '【IMPORTANT】If you did not request this email, please ignore it. Your account will not be activated unless you click the verification link. A third party may have mistakenly registered using this email address, but your personal information will not be compromised.',
        'regards' => 'Best regards',
    ],

    // Member verification completed notification
    'member_verification_completed' => [
        'subject' => 'Account Verification Completed',
        'greeting' => 'Hello :name!',
        'message' => 'Your member account verification has been completed.',
        'member_info' => '【Member Information】',
        'name' => 'Name',
        'email' => 'Email Address',
        'login_info' => 'You can now log in to the admin panel using the URL below.',
        'url_info' => '【URL Information】',
        'front_url' => 'Front Page URL',
        'admin_url' => 'Admin Panel URL',
        'thanks' => 'Thank you for using our service.',
        'regards' => 'Best regards',
    ],

    // Admin notifications
    'admin_notification' => [
        'member_verified' => [
            'subject' => 'Member Account Verification Completed',
            'greeting' => 'System Administrator',
            'title' => 'Member Account Verification Completed',
            'message' => 'A member account verification has been completed.',
            'member_info' => '【Member Information】',
            'name' => 'Name',
            'email' => 'Email Address',
            'verified_at' => 'Verified At',
            'login_available' => 'This member can now log in.',
            'urls' => '【URL Information】',
            'front_url' => 'Front Page URL',
            'admin_url' => 'Admin Panel URL',
            'notification_time' => 'Notification Time',
            'regards' => 'Best regards',
        ],
    ],

    // Extension operation notifications
    'extension_operation' => [
        // Subjects
        'subject_installed' => '[:app_name] :type ":name" has been installed',
        'subject_uninstalled' => '[:app_name] :type ":name" has been uninstalled',
        'subject_enabled' => '[:app_name] :type ":name" has been enabled',
        'subject_disabled' => '[:app_name] :type ":name" has been disabled',
        'subject_unhealthy_warning' => '[:app_name Warning] :type with health concerns was operated',
        // Types
        'type_plugin' => 'Plugin',
        'type_theme' => 'Theme',
        // Body
        'greeting' => 'System Administrator',
        'message_installed' => ':type ":name" has been installed.',
        'message_uninstalled' => ':type ":name" has been uninstalled.',
        'message_enabled' => ':type ":name" has been enabled.',
        'message_disabled' => ':type ":name" has been disabled.',
        'message_unhealthy_warning' => 'A :type with health status other than "Healthy" has been operated. Please review the details.',
        // Details
        'details_title' => 'Operation Details',
        'extension_name' => 'Extension Name',
        'extension_type' => 'Type',
        'operation' => 'Operation',
        'operation_installed' => 'Installed',
        'operation_uninstalled' => 'Uninstalled',
        'operation_enabled' => 'Enabled',
        'operation_disabled' => 'Disabled',
        'operated_by' => 'Operated By',
        'operated_at' => 'Operated At',
        'health_status' => 'Health Status',
        'health_healthy' => 'Healthy',
        'health_warning' => 'Warning',
        'health_needs_attention' => 'Needs Attention',
        'health_not_verified' => 'Not Verified',
        'version' => 'Version',
        // Warning message
        'unhealthy_notice' => 'This extension has a health status of ":level". We recommend reviewing its features and permissions.',
        // Footer
        'regards' => 'Best regards',
        'auto_notification' => 'This notification was sent automatically based on security settings.',
    ],
    
    // File Integrity Alert Email
    'file_integrity' => [
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
        'issues_summary' => 'Issues Detected',
        'changed_files' => 'Changed Files',
        'added_files' => 'Added Files',
        'removed_files' => 'Removed Files',
        'suspicious_files' => 'Suspicious Files',
        'action_required' => 'Please review the details and take action if necessary.',
        'view_details_button' => 'View Details',
        'thanks' => 'Best regards',
    ],

];
