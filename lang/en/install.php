<?php

return [
    'title' => 'Installation',
    //index
    'header' => 'Dixlase Installation',
    'welcome' => 'Welcome to Dixlase Installation',
    'description' => 'Before proceeding, please check if your server meets the requirements.',
    'server_requirements' => 'Server Requirements',
    'ok' => 'OK',
    'failed' => 'FAILED',
    'permissions' => [
        'storage' => 'Storage directory writable',
        'cache' => 'Bootstrap cache directory writable',
    ],
    'start_button' => 'Start Installation',
    'languages' => [
        'en' => 'English',
        'ja' => 'Japanese',
    ],

    //step 1
    'settings_title' => 'Installation - Step 1',
    'settings_header' => 'Basic Settings',
    'settings_description' => 'Please enter the basic information to set up your site.',
    'step_of_total' => 'Step :current of :total',
    'site_name' => 'Site Name',
    'admin_name' => 'Admin Username',
    'admin_name_placeholder' => 'Enter alphanumeric characters (e.g., siteadmin2025)',
    'admin_name_requirements' => 'Use 3-20 alphanumeric characters.<br>In production, avoid easily guessable names such as admin, administrator, root, user, test, demo, dixlase, manager, or webmaster.',
    'validation' => [
        'admin_name_required' => 'Please enter an admin username.',
        'admin_name_alpha_num' => 'Admin username must contain only alphanumeric characters.',
        'admin_name_length' => 'Admin username must be between 3 and 20 characters.',
    ],
    'admin_email' => 'Admin Email',
    'admin_password' => 'Admin Password',
    'admin_password_confirmation' => 'Confirm Password',
    'admin_password_confirmation_note' => 'Please re-enter the same password for confirmation.',
    
    // Semantic headings
    'site_information' => 'Site Information',
    'admin_account_information' => 'Administrator Account Information',
    'admin_account_details' => 'Administrator Account Details',
    'password_settings' => 'Password Settings',
    'password_setup' => 'Password Setup',
    'form_navigation' => 'Form Navigation',
    'back_to_previous_step' => 'Back to previous step',
    'password_paste_error' => 'Pasting is not allowed in the password confirmation field.',
    
    // Layout related
    'installation_progress' => 'Installation Progress',
    'language_selection' => 'Language Selection',
    'error' => 'Error',
    'validation_errors' => 'Validation Errors',
    
    // Environment settings related
    'environment_settings' => 'Environment Settings',
    'application_environment' => 'Application Environment',
    'url_settings' => 'URL Settings',
    'application_url_configuration' => 'Application URL Configuration',
    'admin_url_configuration' => 'Admin URL Configuration',
    'admin_panel_url' => 'Admin Panel URL',
    'timezone_configuration' => 'Timezone Configuration',
    'application_timezone' => 'Application Timezone',
    
    // Database settings related
    'database_connection_settings' => 'Database Connection Settings',
    'database_connection_details' => 'Database Connection Details',
    'data_preservation_settings' => 'Data Preservation Settings',
    'database_preservation_options' => 'Database Preservation Options',
    'database_connection_test' => 'Database Connection Test',
    
    // Mail server settings related
    'mail_server_settings' => 'Mail Server Settings',
    'mail_connection_test' => 'Mail Connection Test',
    
    // Security settings related
    'ip_address_format_instruction' => 'Enter one IP address per line. Example:',
    'admin_panel_ip_restrictions' => 'Admin Panel IP Restrictions',
    'front_panel_ip_restrictions' => 'Front Panel IP Restrictions',
    
    // Confirmation page related
    'settings_review' => 'Settings Review',
    'next' => 'Next',

    //step 2
    'environment_title' => 'Environment Settings',
    'environment_header' => 'Application Environment Settings',
    'environment_description' => 'Select the environment in which the application will run and configure the URL.',
    'app_env' => 'Application Environment',
    'app_env_options' => [
        'local' => 'Local',
        'staging' => 'Staging',
        'production' => 'Production',
    ],
    'app_debug' => 'Debug Mode',
    'enable_debug' => 'Enable Debug Mode',
    'app_debug_note' => 'Debug mode cannot be selected in the production environment.',

    'app_url' => 'Application URL',
    'app_url_note' => 'The default URL is automatically set based on the current host. Change it if necessary.',
    'timezone' => 'Timezone',

    //step 3
    'database_title' => 'Database Settings',
    'database_header' => 'Enter your database details',
    'database_description' => 'Configure the database that the system will use.',
    'db_connection' => 'Database Type',
    'db_host' => 'Database Host',
    'db_port' => 'Database Port',
    'db_database' => 'Database Name',
    'db_username' => 'Database Username',
    'db_password' => 'Database Password',
    'preserve_database' => 'Do not reset database',
    'preserve_database_help' => 'If checked, only necessary updates will be applied without deleting existing data. If unchecked, all existing data will be deleted during installation.',
    'test_db_connection' => 'Test Connection',
    'db_connection_success' => 'Database connection successful!',
    'db_connection_error' => 'Failed to connect to the database: :error',
    'db_test_required' => '⚠️ Please test the database connection before proceeding to the next step.',
    'db_test_success' => '✅ Database connection successful! You can proceed to the next step!',
    'tooltip_test_db' => 'Please test the database connection.',
    'db_password_required' => 'Database password is required.',
    'back' => 'Back',
    'next' => 'Next',

    //step 3
    'security_title' => 'Security Settings',
    'security_header' => 'Security Settings(optional)',
    'security_description' => 'Set the URL for the admin panel and configure IP restrictions.<br>IP restrictions can also be configured after installation.',
    'admin_url' => 'Admin Panel URL',
    'enable_allowed_admin_ips' => 'Allow access to the admin panel only from specific IP addresses',
    'enable_blocked_admin_ips' => 'Block access to the admin panel from specific IP addresses',
    'enable_allowed_front_ips' => 'Allow access to the frontend only from specific IP addresses',
    'enable_blocked_front_ips' => 'Block access to the frontend from specific IP addresses',
    'yes' => 'Yes',
    'no' => 'No',



    //step 6
    'mail_title' => 'Mail Server Settings',
    'mail_header' => 'Mail Server Settings (Optional)',
    'mail_description' => 'Enter the information for the mail server that the application will use to send emails.<br>This setting can be skipped and configured after installation.',
    'mail_mailer' => 'Mailer',
    'mail_host' => 'Host',
    'mail_port' => 'Port',
    'mail_username' => 'Username',
    'mail_password' => 'Password',
    'mail_encryption' => 'Encryption',
    'mail_from_address' => 'From Address',
    'mail_from_name' => 'From Name',
    
    // Mail test functionality
    'mail_test' => [
        'title' => 'Mail Test',
        'description' => 'Test mail server connection and email sending functionality.',
        'description_admin_email' => 'Test emails will be sent to the admin email address entered in basic settings.',
    ],
    'mail_test_description' => 'Test mail server connection and email sending functionality.',
    'mail_test_description_admin_email' => 'Test emails will be sent to the admin email address entered in basic settings.',
    'test_connection_button' => 'Test Connection',
    'test_mail_button' => 'Test Email Send',
    'testing' => 'Testing',
    'mail_test_advanced' => [
        'three_stage_test_incomplete' => '3-stage mail test incomplete',
        'three_stage_test_complete' => '3-stage mail test complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receipt Verification',
    ],
    'connection_test_error' => 'An error occurred during connection test.',
    'mail_send_test_error' => 'An error occurred during email send test.',
    'mail_connection_test_not_supported' => ':mailer mailer does not support connection testing.',
    'mail_connection_test_success' => 'Successfully connected to mail server.',
    'mail_connection_test_failed' => 'Failed to connect to mail server: :error',
    'admin_email_not_found' => 'Admin email address not found. Please check basic settings.',
    'mail_send_test_success' => 'Test email sent to :email.',
    'mail_send_test_failed' => 'Failed to send test email: :error',

    //confirm
    'confirm_title' => 'Confirm Installation Settings',
    'confirm_header' => 'Confirm Installation',
    'confirm_message' => 'Please review the settings before finalizing the installation.',
    'confirm_description' => 'The installation will proceed with the above settings. <br>Are you ready to continue?',
    'settings_review' => 'Settings Review',
    'basic_settings' => 'Basic Settings',
    'app_settings' => 'Application Settings',
    'database_settings' => 'Database Settings',
    'mail_settings' => 'Mail Settings',
    'security_settings' => 'Security Settings',
    'not_executed' => 'Not Executed',

    // サイト情報
    'site_name' => 'Site Name',
    'admin_email' => 'Admin Email',
    'admin_password' => 'Admin Password',

    // 管理画面設定
    'admin_url' => 'Admin Panel URL',
    'admin_url_security_note' => 'For production environments, it is recommended to set the admin panel URL to something other than "admin" that is difficult to guess.',
    'force_ssl' => 'Force SSL (HTTPS)',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',

    // IP制限
    'ip_restrictions' => 'IP Restrictions',
    'allowed_admin_ips' => 'Allowed Admin Panel IPs',
    'blocked_admin_ips' => 'Blocked Admin Panel IPs',
    'allowed_front_ips' => 'Allowed Frontend IPs',
    'blocked_front_ips' => 'Blocked Frontend IPs',
    'none' => 'None',

    // データベース情報
    'db_connection' => 'Database Connection',
    'db_host' => 'Database Host',
    'db_port' => 'Database Port',
    'db_name' => 'Database Name',
    'db_user' => 'Database Username',
    'db_password' => 'Database Password',

    // ボタン
    'back_button' => 'Back',
    'confirm_button' => 'Confirm & Install',

    //complete
    'complete_title' => 'Installation Complete!',
    'complete_header' => 'Installation Complete',
    'complete_message' => 'The installation has been successfully completed!<br> You can now access your site or the admin panel.',
    'go_to_site' => 'Go to Site',
    'go_to_admin' => 'Go to Admin Panel',
    'site_url' => 'Site URL',
    'admin_login_url' => 'Admin Login URL',
    //errors
    'password_strength_error' => 'Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, and one number.',
    'password_strength_weak' => 'Password is too weak.',
    'password_strength_medium' => 'Password strength is medium.',
    'password_strength_strong' => 'Password is strong.',
    
    // Errors
    'missing_required_fields' => 'Some required fields are missing. Please complete all installation steps in order.',
    'please_complete_previous_steps' => 'Please complete the previous installation steps before proceeding.',
    'please_complete_this_step' => 'Please complete this step before proceeding.',
    
    // Timezone
    'timezone' => [
        'label' => 'Timezone',
        // Timezone translations are now in timezones.php
    ],
    'timezone_note' => 'Select the default timezone for the application.',

    // Installation error messages
    'error' => [
        'installation_failed' => 'An error occurred during installation',
        'technical_details' => 'Show technical details',
        'database_column_missing' => 'Database column not found',
        'database_table_exists' => 'Database table already exists',
        'database_table_missing' => 'Database table not found',
        'database_access_denied' => 'Database access denied',
        'database_general' => 'Database error occurred',
        'migration_failed' => 'Database migration failed',
        'seeder_failed' => 'Database seeder execution failed',
        'file_system' => 'File system error occurred (check permissions)',
        'environment' => 'Environment configuration file update failed',
        'encryption' => 'Data encryption/decryption failed',
        'unknown' => 'An unexpected error occurred',
    ],
];
