<?php

/**
 * This file is part of MySoftware.
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

    /*
    |--------------------------------------------------------------------------
    | Admin Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during admin for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'login' => [
        'title' => 'Admin Login',
        'header' => 'Admin Login',
        'description' => 'Please log in to access the admin panel.',
        'email' => 'Email',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'login_button' => 'Log in',
        'captcha' => 'Security Verification',
    ],

    'auth' => [
        'forgot_password' => [
            'title' => 'Password Reset',
            'header' => 'Password Reset',
            'description' => 'Forgot your password? Enter your email address and we will send you a password reset link.',
            'email' => 'Email',
            'send_reset_link' => 'Send Password Reset Link',
            'back_to_login' => 'Back to Login',
        ],
        'reset_password' => [
            'title' => 'Set New Password',
            'header' => 'Set New Password',
            'description' => 'Please set your new password.',
            'email' => 'Email Address',
            'password' => 'New Password',
            'password_confirmation' => 'Confirm Password',
            'reset_password_button' => 'Reset Password',
        ],
    ],

    'common' => [
        'search' => 'Search',
        'submit' => 'Update',
        'install' => 'Install',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'logout' => 'Logout',
        'cancel' => 'Cancel',
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'unique' => 'The :attribute has already been taken.',
        'min' => 'The :attribute must be at least :min characters.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'attributes' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
        ],
        'theme' => [
            'auto' => 'Auto',
            'dark' => 'Dark',
            'light' => 'Light',
        ],
    ],

    'locales' => [
        'ja' => 'Japanese',
        'en' => 'English',
    ],
    'role' => 'Role',
    'roles' => [
        'SUPER_ADMIN' => 'Super Administrator',
        'ADMIN' => 'Administrator',
        'EDITOR' => 'Editor',
        'AUTHOR' => 'Author',
        'CONTRIBUTOR' => 'Contributor',
    ],
    'status' => [
        'Inactive' => 'Inactive',
        'Active' => 'Active',
    ],
    'nav' => [
        'dashboard' => 'Dashboard',
        'front' => [
            'text' => 'Front Page Management',
            'index' => 'Front Page Master',
            'design' => 'Front Page Design',
            'settings' => 'Front Page Settings',
        ],
        'media' => [
            'text' => 'Media Management',
            'index' => 'Media Master',
            'upload' => 'Media Upload',
            'settings' => 'Media Settings'
        ],
        'settings' => [
            'text' => 'Global Settings',
            'base' => 'Basic Settings',
            'security' => 'Security Settings',
            'members' => [
                'text' => 'Member Management',
                'index' => 'Member Master',
                'create' => 'Create New Member',
                'edit' => 'Edit',
                'profile' => 'Profile Settings',
                'roles' => 'Role Settings',
                'settings' => 'Member Global Settings',
            ],
            'themes' => [
                'text' => 'Theme Settings',
                'index' => 'List',
                'install' => 'Install',
            ],
            'plugins' => [
                'text' => 'Plugin Settings',
                'index' => 'Plugin Master',
                'install'  => 'Install',
            ],
            'systems' => [
                'text' => 'System',
                'cache' => 'Cache',
                'database_cleanup' => 'Database Cleanup',
                'logs' => 'Logs',
                'info'  => 'System Information',
            ],
        ],
    ],


    // Dashboard
    'dashboard' => [
        'heading' => 'Dashboard',
        'description' => 'You can check the site overview.',
    ],

    // Front Page
    'front' => [
        'index' => [
            'heading' => 'Front Page Master',
            'description' => 'You can check the front page preview.',
        ],
        'design' => [
            'heading' => 'Front Page Design',
            'description' => 'Design the front page.',
        ],
        'settings' => [
            'heading' => 'Front Page Settings',
            'description' => 'Configure front page settings.',
        ],

    ],

    // Media
    'media' => [
        'index' => [
            'heading' => 'Media Master',
            'upload_new_file' => 'Upload New File',
            'download' => 'Download',
            'preview' => 'Preview',
            'delete' => 'Delete',
        ],
        'upload' => [
            'heading' => 'Media Upload',
            'select_file' => 'Select Media File:',
            'drag_drop_text' => 'Drag files here or click to upload',
            'supported_formats' => 'Supported formats:',
            'upload_button' => 'Upload',
        ],
        'preview' => [
            'heading' => 'Media Preview',
            'no_preview' => 'File cannot be previewed.',
            'file_name' => 'File Name:',
            'file_type' => 'File Type:',
            'upload_date' => 'Upload Date:',
            'uploaded_by' => 'Uploaded by:',
            'unknown' => 'Unknown',
            'media_url' => 'Media URL',
            'copy' => 'Copy',
            'copied' => 'Copied',
            'url_description' => 'Use this URL to directly access the media file.',
            'back' => 'Back',
            'download' => 'Download',
            'delete' => 'Delete',
            'delete_confirmation' => 'Delete Confirmation',
            'delete_message' => 'Are you sure you want to delete this media file?',
            'cancel' => 'Cancel',
            'copy_failed' => 'Copy failed. Please manually select and copy the URL.',
        ],
        'settings' => [
            'heading' => 'Media Settings',
            'allowed_file_types' => 'Allowed File Types',
            'max_file_size' => 'Maximum File Size',
            'file_size_range' => '(1MB - 100MB)',
            'save_settings' => 'Save',
            'save_confirmation_title' => 'Media Settings Save Confirmation',
            'save_confirmation_message' => 'Do you want to save the media settings?',
            'save_button' => 'Save',
            'cancel_button' => 'Cancel',
        ],

    ],




    // Settings
    'settings' => [
        // Basic
        'base' => [
            'heading' => 'Basic Settings',
            'site_settings' => 'Site Settings',
            'app_name' => 'Application Name',
            'locale' => 'Language Settings',
            'timezone' => 'Timezone',
            'mail_server_settings' => 'Mail Server Settings',
            'mailer' => 'Mailer',
            'mail_host' => 'Host Name',
            'mail_port' => 'Port Number',
            'mail_username' => 'Username',
            'mail_password' => 'Password',
            'mail_encryption' => 'Encryption Method',
            'mail_from_address' => 'From Email Address',
            'mail_test' => 'Mail Send Test',
            'mail_test_description' => 'Send a test email with the current settings. A test email will be sent to the from email address.',
            'mail_test_description_2' => 'To enable mail sending functionality, please execute both connection test and mail send test.',
            'test_connection_button' => 'Connection Test',
            'test_mail_button' => 'Send Test Mail',
            'testing_connection' => 'Connecting...',
            'testing_mail' => 'Sending...',
            'mail_test_error' => 'An error occurred during mail send test.',
            'test_mail_subject' => 'Mail Send Test',
            'test_mail_body' => 'This is a test email from :app_name.

Mail send test has been completed successfully.
To complete mail receive verification, please click the following link:

:verification_url

Clicking this link will complete the full mail function test.',
            'test_mail_success' => 'Test email has been sent successfully. Please check your inbox.',
            'test_mail_failed' => 'Failed to send email: :error',
            'maintenance_settings' => 'Maintenance Mode Settings',
            'maintenance_mode' => 'Maintenance Mode',
            'maintenance_message' => 'Maintenance Message',
            'maintenance_message_help' => '※Displayed on the front screen when maintenance mode is enabled.',
            'save_confirmation_title' => 'Save Confirmation',
            'save_confirmation_message' => 'Do you want to save the changes?',
            'save_button' => 'Save',
            'cancel_button' => 'Cancel',
            'yes' => 'Yes',
            'no' => 'No',
            'submit' => 'Update',
            'last_test_date' => 'Last Test Date',
            'mail_server_warning' => 'Mail Server Not Configured',
            'mail_server_warning_message' => 'Mail sending functionality is not available because mail server configuration and testing have not been completed.',
            'mail_server_test_passed' => 'Mail server connection test passed. Mail sending functionality is available.',
            'save_settings_reminder' => 'Please Save Settings',
            'save_settings_reminder_message' => 'Please make sure to save the settings to apply the changes.',
            'validation' => [
                'app_name_required' => 'Application name is required.',
                'locale_required' => 'Please select a language.',
                'timezone_invalid' => 'Please select a valid timezone.',
                'mail_mailer_required' => 'Please select a mail driver.',
                'mail_host_required' => 'Please enter mail host.',
                'mail_port_required' => 'Please enter mail port.',
                'mail_port_numeric' => 'Mail port must be a number.',
                'maintenance_mode_required' => 'Please select maintenance mode setting.',
            ],
            'controller_messages' => [
                'settings_updated' => 'Settings have been updated.',
                'test_session_cleared' => 'Test session has been cleared.',
                'mailer_not_supported' => 'Mailer ":mailer" does not support connection testing.',
                'connection_success' => 'Connection to mail server has been successfully verified.',
                'connection_failed' => 'Failed to connect to mail server: :error',
                'verification_token_invalid' => 'Mail verification token is invalid.',
                'verification_error' => 'An error occurred during mail verification: :error',
            ],
            'mail_verification_success' => [
                'title' => 'Mail Receive Verification Complete',
                'heading' => 'Mail receive verification completed',
                'description' => 'Mail function test has been completed successfully.',
                'next_steps_title' => 'Next Steps',
                'next_steps' => [
                    'close_window' => 'Please close this window',
                    'save_settings' => 'Press the "Update" button on the base settings screen to save your settings',
                    'data_saved' => 'Test results will be saved and mail function will be enabled',
                ],
                'important_notice_title' => 'Important Notice',
                'important_notice' => 'Test results are temporarily stored. Please make sure to save your settings.',
                'close_button' => 'Close Window',
            ],
            'view_messages' => [
                'mail_test_complete' => 'Mail Function Test Complete',
                'mail_test_incomplete' => 'Mail Function Test Incomplete',
                'mail_test_warning_features' => 'To use lockout notifications, password reset, login notifications, and two-factor authentication features in member settings, please complete all mail tests.',
                'mail_test_warning_temporary' => 'Test results are temporarily stored. Settings and test results will not be saved until you press the update button.',
                'connection_test' => 'Server Connection Test',
                'send_test' => 'Mail Send Test',
                'receive_test' => 'Mail Receive Test',
                'test_passed' => 'Test Passed',
                'test_not_completed' => 'Not Executed',
                'mail_receive_test_completed' => 'Mail receive test completed. Please save your settings.',
            ],
            // System Error Notification Settings
            'notification_settings' => 'System Error Notification Settings',
            'notification_settings_description' => 'Send notifications to specified email addresses when system errors or application issues occur.',
            'notification_enabled' => 'Error Notification Function',
            'notification_enabled_help' => 'Set whether to send email notifications when system errors occur.',
            'notification_email' => 'Notification Email Address',
            'notification_email_help' => 'Enter the email address to receive system error notifications.',
            'notification_mail_test_required' => 'To use the error notification function, please complete all mail function tests above.',
            'maintenance_message_help' => '※Displayed on the front screen when maintenance mode is enabled.',
        ],
        // Security
        'security' => [
            'heading' => 'Security Settings',
            'basic_security_settings' => 'Basic Security Settings',
            'ip_access_control' => 'IP Access Control Settings',
            'admin_url' => 'Admin URL',
            'enable_allowed_admin_ips' => 'Allow access only from specific IP addresses',
            'allowed_admin_ips' => 'Allowed IP Addresses',
            'enable_blocked_admin_ips' => 'Block specific IP addresses',
            'blocked_admin_ips' => 'Blocked IP Addresses',
            'force_ssl' => 'Force SSL',
            'recaptcha_settings' => 'reCAPTCHA Settings',
            'captcha_enabled' => 'Enable reCAPTCHA',
            'captcha_driver' => 'CAPTCHA Provider',
            'captcha_google_site_key' => 'Google reCAPTCHA Site Key',
            'captcha_google_secret_key' => 'Google reCAPTCHA Secret Key',
            'captcha_google_version' => 'reCAPTCHA Version',
            'captcha_google_min_score' => 'Minimum Score (0.0-1.0)',
            'captcha_turnstile_site_key' => 'Cloudflare Turnstile Site Key',
            'captcha_turnstile_secret_key' => 'Cloudflare Turnstile Secret Key',
            'captcha_min_score_description' => '0.0 is most suspicious, 1.0 is most trustworthy. Usually 0.5 is recommended.',
            'captcha_form_settings' => 'reCAPTCHA Form Settings',
            'captcha_forms' => [
                'admin_login' => 'Admin Login',
            ],
            'captcha_version_options' => [
                'v3' => 'v3 (Recommended - Non-interactive)',
                'v2_checkbox' => 'v2 Checkbox',
                'v2_invisible' => 'v2 Invisible',
            ],
            'save_confirmation_title' => 'Save Confirmation',
            'save_confirmation_message' => 'Do you want to save the changes?',
            'save_button' => 'Save',
            'back_button' => 'Back',
            'submit' => 'Update',
            'validation' => [
                'allowed_admin_ips_format' => 'Please enter IP addresses in comma-separated format (e.g., 192.168.1.1, 127.0.0.1).',
                'blocked_admin_ips_format' => 'Please enter IP addresses in comma-separated format (e.g., 192.168.1.1, 127.0.0.1).',
                'captcha_google_version_invalid' => 'Please select a valid reCAPTCHA version: v2_checkbox, v2_invisible, or v3.',
                'captcha_google_min_score_range' => 'reCAPTCHA minimum score must be between 0 and 1.',
                'captcha_driver_invalid' => 'Please select a supported CAPTCHA provider.',
            ],
        ],
        // Members
        'members' => [
            'form' => [
                'name' => 'Name',
                'email' => 'Email',
                'password' => 'Password',
                'role' => 'Role',
                'appearance' => 'Appearance Mode',
                'status' => 'Status',
            ],
            'index' => [
                'heading' => 'Member Master',
                'search_title' => 'Administrator Search',
                'search_placeholder' => 'Search by username or email address',
                'search_button' => 'Search',
                'table' => [
                    'id' => 'ID',
                    'name' => 'Name',
                    'email' => 'Email',
                    'role' => 'Role',
                    'actions' => 'Actions',
                    'edit' => 'Edit',
                    'unknown_role' => 'Unknown',
                ],
            ],
            'create' => [
                'heading' => 'Create New Member',
                'name' => 'Name',
                'email' => 'Email',
                'password' => 'Password',
                'password_confirmation' => 'Password Confirmation',
                'role' => 'Role',
                'submit' => 'Register',
                'create_button' => 'Create',
                'create_confirmation_title' => 'Create Confirmation',
                'create_confirmation_message' => 'Do you want to create a new administrator?',
                'back_button' => 'Back',
            ],
            'edit' => [
                'heading' => 'Edit Member',
                'name' => 'Name',
                'email' => 'Email',
                'password' => 'Password',
                'password_confirmation' => 'Password Confirmation',
                'role' => 'Role',
                'submit' => 'Update',
            ],
            'profile' => [
                'heading' => 'Profile Settings',
                'name' => 'Name',
                'description' => 'Description',
                'email' => 'Email',
                'password' => 'Password',
                'password_change_only' => 'Password (only if changing)',
                'password_confirmation' => 'Password Confirmation',
                'appearance_mode' => 'Appearance Mode',
                'appearance_auto' => 'Auto',
                'appearance_light' => 'Light',
                'appearance_dark' => 'Dark',
                'login_notification_setting' => 'Login Notification Email Settings',
                'two_factor_setting' => 'Two-Factor Authentication Settings',
                'always' => 'Individual settings cannot be changed because it is set to "Always Enabled" in the member global settings.',
                'submit' => 'Update Profile',
                'update_button' => 'Update',
                'updated' => 'Profile has been updated.',
                'confirm_title' => 'Profile Update Confirmation',
                'confirm_message' => 'Do you want to update your profile?',
                'confirm_label' => 'Update',
                'cancel_label' => 'Cancel',
            ],
            'settings' => [
                'heading' => 'Member Global Settings',
                'password_conditions' => 'Password Conditions',
                'login_notification_settings' => 'Login Notification Settings',
                'two_factor_settings' => 'Two-Factor Authentication Settings',
                'password' => 'Password',
                'password_confirmation' => 'Password Confirmation',
                'role' => 'Role',
                'submit' => 'Update',
                'confirm_title' => 'Update Settings',
                'confirm_message' => 'Do you want to update the settings with this content?',
                'confirm_label' => 'Update',
                'cancel_label' => 'Back',
                'updated' => 'Member global settings have been updated.',
                'password_min_length' => 'Minimum Password Length',
                'password_min_length_options' => [
                    8 => '8 characters or more',
                    12 => '12 characters or more',
                    16 => '16 characters or more',
                ],
                'password_require_uppercase' => 'Require uppercase letters',
                'password_require_uppercase_options' => [
                    1 => 'Required',
                    0 => 'Not required',
                ],
                'password_require_symbol' => 'Require symbols',
                'password_require_symbol_options' => [
                    1 => 'Required',
                    0 => 'Not required',
                ],
                'login_notification_global_setting' => 'Global Login Notification Email Settings',
                'two_factor_methods_label' => 'Available Two-Factor Authentication Methods',
                'two_factor_methods_help' => 'Select the two-factor authentication methods that users can use. At least one must be enabled.',
                'password_reset_settings' => 'Password Reset Function Settings',
                'password_reset_enabled' => 'Password Reset Function',
                'password_reset_enabled_options' => [
                    'enabled' => 'Enabled',
                    'disabled' => 'Disabled',
                ],
                'password_reset_help' => 'When disabled, the password reset link will be hidden on the admin login screen and the password reset function will not be available. To reset passwords when disabled, please use the member edit screen in the admin panel.',
                'login_attempt_limit_settings' => 'Login Attempt Limiting Settings',
                'login_attempt_limit_enabled' => 'Login Attempt Limiting',
                'login_attempt_limit_enabled_options' => [
                    'enabled' => 'Enabled',
                    'disabled' => 'Disabled',
                ],
                'login_attempt_max_attempts' => 'Maximum Attempts',
                'login_attempt_max_attempts_help' => 'When login failures exceed this number, access will be temporarily restricted.',
                'login_attempt_time_window' => 'Time Window (minutes)',
                'login_attempt_time_window_help' => 'Failed attempts within this time period will be counted.',
                'login_attempt_lockout_duration' => 'Lockout Duration (minutes)',
                'login_attempt_lockout_duration_help' => 'Set the lockout duration in minutes (1-10080 minutes).',
                'lockout_notification_enabled' => 'Lockout Notification',
                'lockout_notification_enabled_options' => [
                    'enabled' => 'Enabled',
                    'disabled' => 'Disabled',
                ],
                'lockout_notification_help' => 'Send system error notifications when lockouts occur due to login attempt limits. The email will be sent to the email address set in basic settings.',
                'lockout_notification_mail_test_required' => 'To use lockout notifications, please complete the mail server test in basic settings.',
                'login_attempt_lockout_duration' => 'Lockout Duration (minutes)',
                'login_attempt_lockout_duration_help' => 'When the limit is reached, login will be blocked for this duration.',
                'login_attempt_limit_help' => 'To prevent brute force attacks, login access will be temporarily restricted when consecutive login failures occur within a short time period.',
                'update_button' => 'Update',

            ],
            'roles' => [
                'heading' => 'Permission Settings',
                'access_roles' => 'Edit Permission (access_roles)',
                'view_roles' => 'View Permission (view_roles)',
                'confirm_title' => 'Permission Settings Update Confirmation',
                'confirm_message' => 'Do you want to update the permission settings?',
                'confirm_label' => 'Update',
                'cancel_label' => 'Cancel',
            ],
            'login_notification_mode' => [
                'label' => 'Login Notification Settings',
                'options' => [
                    0 => 'Disabled',
                    1 => 'Reflect member profile settings',
                    2 => 'Enabled only for different devices/IPs',
                    3 => 'Always enabled',
                ]

            ],
            'two_factor_mode' => [
                'label' => 'Two-Factor Authentication Settings',
                'options' => [
                    0 => 'Disabled',
                    1 => 'Reflect member profile settings',
                    2 => 'Enabled only for different devices/IPs',
                    3 => 'Always enabled',
                ]
            ],
            'two_factor_method' => [
                'label' => 'Two-Factor Authentication Method',
                'options' => [
                    'email' => 'Email Authentication',
                    'device' => 'Device Authentication',
                    'biometric' => 'Biometric Authentication',
                ]
            ],
            'validation' => [
                'mail_server_not_tested' => 'To enable lockout notification function, password reset function, login notification function, and two-factor authentication function, you must pass the mail server connection test in the basic settings.',
                'mail_server_warning' => 'Mail Server Not Configured',
                'mail_server_warning_message' => 'Lockout notification function, password reset function, login notification function, and two-factor authentication function will not work because mail server configuration and testing have not been completed.',
                'mail_server_test_passed' => 'Mail server connection test passed. Lockout notification, password reset function, login notification function, and two-factor authentication function can be used.',
                'please_configure_in' => 'Please configure mail server settings in the basic settings',
                'name_required' => 'Name is required.',
                'email_required' => 'Email address is required.',
                'email_invalid' => 'Email address format is invalid.',
                'email_unique' => 'This email address is already registered.',
                'password_required' => 'Password is required.',
                'password_min' => 'Password must be at least 8 characters.',
                'password_confirmed' => 'Password confirmation does not match.',
                'role_required' => 'Please select a role.',
                'role_invalid' => 'Invalid role selected.',
                'appearance_required' => 'Please select appearance setting.',
                'appearance_invalid' => 'Invalid appearance setting selected.',
                'status_required' => 'Please select status.',
                'status_invalid' => 'Invalid status selected.',
            ],
            'force_setting_1' => 'Individual settings cannot be changed because',
            'force_setting_2' => 'is selected in member global settings.',
            'status' => 'Status',
            'status_options' => [
                1 => 'Active',
                0 => 'Inactive',
            ],
            'role' => 'Role',
            'role_options' => [
                'super_admin' => 'Super Administrator',
                'admin' => 'Administrator',
                'editor' => 'Editor',
                'author' => 'Author',
                'contributor' => 'Contributor',
            ],
        ],
        // Themes
        'themes' => [
            'index' => [
                'heading' => 'Theme Settings',
                'title' => 'Theme Settings',
                'available_themes' => 'Available Themes',
                'currently_active' => 'Currently Active',
                'activate_button' => 'Activate',
                'delete_button' => 'Delete',
                'activate_confirm' => 'Do you want to activate this theme?',
                'delete_confirm' => 'Are you sure you want to delete this?',
                'color' => 'Color',
                'font' => 'Font',
                'submit' => 'Update',
            ],
            'install' => [
                'heading' => 'Theme Installation',
                'upload_title' => 'Upload Theme',
                'file_select_label' => 'Select File',
                'upload_button' => 'Upload',
                'name' => 'Theme Name',
                'submit' => 'Install',
            ],
        ],
        // Plugins
        'plugins' => [
            'index' => [
                'heading' => 'Plugin Master',
                'systems' => [
                    'text' => 'System',
                    'cache' => 'Cache Management',
                    'database_cleanup' => 'Database Cleanup',
                    'logs' => 'System Logs',
                    'info' => 'System Information',
                ],
                'table' => [
                    'id' => 'ID',
                    'name' => 'Plugin Name',
                    'status' => 'Status',
                    'actions' => 'Actions',
                ],
                'status' => [
                    'enabled' => 'Enabled',
                    'disabled' => 'Disabled',
                ],
                'buttons' => [
                    'enable' => 'Enable',
                    'disable' => 'Disable',
                    'uninstall' => 'Uninstall',
                ],
                'uninstall' => [
                    'confirm_title' => 'Uninstall Confirmation',
                    'confirm_message' => 'Do you want to uninstall plugin [{name}]?',
                    'confirm_button' => 'Uninstall',
                    'cancel_button' => 'Cancel',
                    'remove_data_checkbox' => 'Delete database tables created during plugin installation. Warning! Deleting tables will lose all data created by the plugin!',
                ],
            ],
            'install' => [
                'heading' => 'Plugin Installation',
                'upload_title' => 'Plugin Upload',
                'file_select_label' => 'Select ZIP File:',
                'drag_drop_text' => 'Drag files here or click to upload',
                'supported_format' => 'Supported format:',
                'upload_limit' => 'Upload file size limit:',
                'upload_button' => 'Upload and Install',
                'enable_plugin_text' => 'To enable the plugin, click',
                'enable_from_here' => 'here',
                'enable_instruction' => 'to enable it.',
                'name' => 'Plugin Name',
                'submit' => 'Install',
            ],
        ],

        // System Information
        'systems' => [
            'cache' => [
                'heading' => 'Cache Management',
                'description' => 'Clear various application caches',
                'config_cache' => [
                    'name' => 'Configuration Cache',
                    'description' => 'Clear cached application configuration files',
                ],
                'route_cache' => [
                    'name' => 'Route Cache',
                    'description' => 'Clear cached routing information',
                ],
                'view_cache' => [
                    'name' => 'View Cache',
                    'description' => 'Clear compiled view files cache',
                ],
                'application_cache' => [
                    'name' => 'Application Cache',
                    'description' => 'Clear application cache data',
                ],
                'clear_button' => 'Clear',
                'clear_confirm' => 'Clear :name?',
                'clear_all_title' => 'Clear All Caches',
                'clear_all_description' => 'Clear all caches (configuration, route, view, application) at once.',
                'clear_all_warning' => 'This operation may temporarily slow down the application.',
                'clear_all_button' => 'Clear All Caches',
                'clear_all_confirm' => 'Clear all caches? This operation may temporarily reduce performance.',
                'info_title' => 'About Caches',
                'info_config' => 'Cache application configuration files for faster performance',
                'info_route' => 'Cache routing information for faster performance',
                'info_view' => 'Cache compiled Blade templates as PHP files',
                'info_application' => 'Cache various data used within the application',
                'success_config' => 'Configuration cache cleared',
                'success_route' => 'Route cache cleared',
                'success_view' => 'View cache cleared',
                'success_application' => 'Application cache cleared',
                'success_all' => 'All caches cleared',
                'error_invalid_type' => 'Invalid cache type',
                'error_general' => 'Error occurred while clearing cache: :error',
                'warning' => 'Warning:',
            ],
            'logs' => [
                'heading' => 'Log Information',
                'log_type_label' => 'Log Type:',
                'no_logs_found' => 'No logs found.',
                'activity' => 'Admin Activity',
                'error' => 'Error',
                'login' => 'Login',
                'laravel' => 'Laravel',
                'download' => 'Download',
                'clear' => 'Clear Logs',
                'clear_confirm' => 'Are you sure you want to clear the log file contents? This action cannot be undone.',
                'pagination' => [
                    'showing' => ':from - :to of :total entries',
                    'page' => 'Page :current / :total',
                    'previous' => '← Previous',
                    'next' => 'Next →',
                ],
                'fields' => [
                    'operation' => 'Operation',
                    'id' => 'ID',
                    'name' => 'name',
                    'method' => 'method',
                    'uri' => 'uri',
                    'route' => 'route',
                    'controller' => 'controller',
                    'ip' => 'ip',
                    'user_agent' => 'user_agent',
                    'time' => 'time',
                ],
                'messages' => [
                    'download_error' => 'Log file does not exist: :filename',
                    'clear_success' => 'Log file cleared successfully: :filename',
                    'clear_error' => 'Log file does not exist: :filename',
                    'clear_failed' => 'Failed to clear log file: :error',
                    'file_not_found' => 'Log file does not exist: :filename',
                ],
            ],
            'database_cleanup' => [
                'heading' => 'Database Cleanup',
                'description' => 'Clean up old database records to maintain system performance',
                'all_cleanup_button' => 'Clean All',
                'all_cleanup_description' => 'Clean up all database tables with default settings',
                'all_tables' => 'All Tables',
                'login_attempts' => [
                    'name' => 'Login Attempts',
                    'description' => 'Clean up old login attempt records',
                    'default_days' => '30 days',
                ],
                'info_panel' => [
                    'title' => 'Important Notes',
                    'notes' => [
                        'irreversible' => 'Database cleanup is an irreversible operation. We recommend backing up necessary data before execution.',
                        'performance' => 'Regular cleanup helps improve system performance.',
                        'production' => 'In production environments, execute carefully during maintenance hours.',
                        'defaults' => 'Default retention periods for each table are optimized according to data characteristics.',
                    ],
                ],
                'modal' => [
                    'title' => 'Confirm Cleanup',
                    'message' => 'Are you sure you want to execute this database cleanup?',
                    'confirm_message' => 'Are you sure you want to cleanup :name records? This operation cannot be undone.',
                    'execute' => 'Execute',
                    'cancel' => 'Cancel',
                ],
                'password_reset_tokens' => [
                    'name' => 'Password Reset Tokens',
                    'description' => 'Clean up old password reset token records',
                    'default_days' => '30 days',
                ],
                'trusted_devices' => [
                    'name' => 'Trusted Devices',
                    'description' => 'Clean up old trusted device records',
                    'default_days' => '90 days',
                ],
                'two_factor_tokens' => [
                    'name' => 'Two-Factor Tokens',
                    'description' => 'Clean up old two-factor authentication token records',
                    'default_days' => '7 days',
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
                'cleanup_success' => 'Successfully cleaned up :count records.',
                'cleanup_error' => 'Error occurred during cleanup: :error',
                'confirm_cleanup' => 'Are you sure you want to clean up :type records?',
                'days_label' => 'Days to keep',
                'cleanup_button' => 'Clean Up',
                'all_cleanup_button' => 'Clean Up All Types',
            ],
            'info' => [
                'heading' => 'System Information',
            ],
        ],
    ],

    // Member force logout
    'force_logout_success' => ':name has been forcibly logged out.',
    'force_logout_all_success' => 'All members have been forcibly logged out. (:count sessions deleted)',
    'force_logout_all_error' => 'Failed to force logout all members.',
    'force_logout_all_modal' => [
        'title' => 'Confirm Force Logout All Members',
        'message' => 'Do you want to forcibly log out all members (except yourself)?<br><br>This operation will invalidate all currently logged-in member sessions and require them to log in again.<br><br><strong>Warning: This operation cannot be undone.</strong>',
        'confirm_label' => 'Force Logout All Members',
        'cancel_label' => 'Cancel',
    ],
];
