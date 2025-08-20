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
    'site_name' => 'Site Name',
    'admin_email' => 'Admin Email',
    'admin_password' => 'Admin Password',
    'admin_password_confirmation' => 'Confirm Password',
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
    'system_title' => 'Installation - Step 2',
    'system_header' => 'System Settings',
    'system_description' => 'Set up your system settings, such as admin panel URL and security settings.',
    'admin_url' => 'Admin Panel URL',
    'enable_allowed_admin_ips' => 'Allow access to the admin panel only from specific IP addresses',
    'enable_blocked_admin_ips' => 'Block access to the admin panel from specific IP addresses',
    'enable_allowed_front_ips' => 'Allow access to the frontend only from specific IP addresses',
    'enable_blocked_front_ips' => 'Block access to the frontend from specific IP addresses',
    'yes' => 'Yes',
    'no' => 'No',

    //step 4
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
    'back' => 'Back',
    'next' => 'Next',

    //confirm
    'confirm_title' => 'Confirm Installation Settings',
    'confirm_header' => 'Confirm Installation',
    'confirm_message' => 'Please review the settings before finalizing the installation.',

    // サイト情報
    'site_name' => 'Site Name',
    'admin_email' => 'Admin Email',
    'admin_password' => 'Admin Password',

    // 管理画面設定
    'admin_url' => 'Admin Panel URL',
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
    'complete_message' => 'The installation has been successfully completed! You can now access your site or the admin panel.',
    'go_to_site' => 'Go to Site',
    'go_to_admin' => 'Go to Admin Panel',
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
