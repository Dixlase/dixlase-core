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
            'test_connection_button' => 'Connection Test',
            'test_mail_button' => 'Send Test Mail',
            'testing_connection' => 'Connecting...',
            'testing_mail' => 'Sending...',
            'mail_test_error' => 'An error occurred during the mail send test.',
            'test_mail_subject' => 'Mail Send Test',
            'test_mail_body' => 'This is a mail send test from :app_name.

Your mail settings are working correctly.',
            'test_mail_success' => 'Test mail has been sent successfully. Please check your inbox.',
            'test_mail_failed' => 'Mail sending failed: :error',
            'maintenance_settings' => 'Maintenance Mode Settings',
            'maintenance_mode' => 'Maintenance Mode',
            'maintenance_message' => 'Maintenance Display Message',
            'maintenance_message_help' => '※Displayed on the front screen when maintenance mode is enabled.',
            'save_confirmation_title' => 'Save Confirmation',
            'save_confirmation_message' => 'Do you want to save the changes?',
            'save_button' => 'Save',
            'cancel_button' => 'Cancel',
            'yes' => 'Yes',
            'no' => 'No',
            'submit' => 'Update',
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
            'captcha_min_score_description' => '0.0 is most suspicious, 1.0 is most trustworthy. Usually 0.5 is recommended.',
            'captcha_form_settings' => 'Form-specific Settings',
            'captcha_contact_form' => 'Contact Form',
            'captcha_registration_form' => 'Registration Form',
            'captcha_login_form' => 'Login Form',
            'captcha_comment_form' => 'Comment Form',
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
                    0 => 'Reflect member profile settings',
                    1 => 'Disabled',
                    2 => 'Always enabled',
                    3 => 'Enabled only for different devices/IPs',
                ]

            ],
            'two_factor_mode' => [
                'label' => 'Two-Factor Authentication Settings',
                'options' => [
                    0 => 'Reflect member profile settings',
                    1 => 'Disabled',
                    2 => 'Always enabled',
                    3 => 'Enabled only for different devices/IPs',
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

            ],
            'info' => [
                'heading' => 'System Information',
            ],
        ],
    ],

];
