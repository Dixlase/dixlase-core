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
    // Basic actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'add' => 'Add',
    'update' => 'Update',
    'submit' => 'Submit',
    'reset' => 'Reset',
    'search' => 'Search',
    'clear' => 'Clear',
    'back' => 'Back',
    'next' => 'Next',
    'close' => 'Close',
    'confirm' => 'Confirm',
    'yes' => 'Yes',
    'no' => 'No',
    'ok' => 'OK',
    'required' => 'Required',
    'optional' => 'Optional',
    'finish' => 'Finish',
    'logout' => 'Logout',
    'preview' => 'Preview',
    'upload' => 'Upload',
    'install' => 'Install',
    'uninstall' => 'Uninstall',
    'enable' => 'Enable',
    'disable' => 'Disable',
    'download' => 'Download',
    'execute' => 'Execute',
    'copy' => 'Copy',
    'copied' => 'Copied',
    'index' => 'Index',
    'default_method' => 'Default',
    'login' => 'Login',
    
    // Language & Theme
    'light' => 'Light',
    'auto' => 'Auto',
    'ja' => 'Japanese',
    'en' => 'English',
    
    // Roles & Permissions
    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
        'receptionist' => 'Receptionist',
        'guest' => 'Guest',
    ],
    'permissions' => 'Permissions',
    'role' => 'Role',
    // Status
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        // Descriptions
        'draft_description' => 'Draft status. Not published.',
        'published_description' => 'Published immediately.',
        'scheduled_description' => 'Published at specified date and time.',
    ],
    // Basic attributes
    'id' => 'ID',
    'name' => 'Name',
    'value' => 'Value',
    'email' => 'Email',
    'password' => 'Password',
    'password_confirmation' => 'Password Confirmation',
    'address' => 'Address',
    'phone' => 'Phone',
    'fax' => 'FAX',
    'gender' => 'Gender',
    'birthday' => 'Birthday',
    'description' => 'Description',
    'color' => 'Color',
    'font' => 'Font',
    'file_name' => 'File Name',
    'file_type' => 'File Type',
    'upload_date' => 'Upload Date',
    'uploaded_by' => 'Uploaded By',
    'unknown' => 'Unknown',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'deleted_at' => 'Deleted At',
    
    // Time units
    'minutes' => 'minutes',
    'hours' => 'hours',
    'days' => 'days',
    
    // Basic Attributes
    'site_name' => 'Site Name',
    'required_fields' => 'Required Fields',
    'admin_theme' => 'Admin Theme',
    'appearance_mode' => 'Appearance Mode',
    'locale' => 'Language',
    'timezone' => 'Timezone',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_message' => 'Maintenance Message',
    'actions' => 'Actions',
    'status' => 'Status',
    'warning' => 'Warning',
    'info' => 'Information',
    'error' => 'Error',
    
    // Search & Filter Related
    'search_keyword' => 'Keyword',
    'role_filter' => 'Role',
    'status_filter' => 'Status',
    'clear_button' => 'Clear',
    'title' => 'Title',
    'url' => 'URL',

    // Log related
    'operation' => 'Operation',
    'method' => 'Method',
    'uri' => 'URI',
    'route' => 'Route',
    'controller' => 'Controller',
    'ip' => 'IP Address',
    'user_agent' => 'User Agent',
    'time' => 'Time',

    // Two-Factor Authentication & Login Notification (Generic)
    'two_factor_mode' => [
        'label' => 'Two-Factor Authentication Settings',
        'options' => [
            0 => 'Disabled',
            1 => 'Only for Different Devices/IPs',
            2 => 'Always Enabled',
            3 => 'Use :account_type Profile Settings',
        ]
    ],
    'two_factor_method' => [
        'label' => 'Two-Factor Authentication Method',
        'options' => [
            'email' => 'Email Authentication',
            'device' => 'Device Authentication',
            'biometric' => 'Biometric Authentication',
            'use_profile_setting' => 'Use :account_type Profile Settings',
        ],
        // Numbered options (for admin settings)
        'numbered_options' => [
            0 => 'Email Authentication',
            1 => 'Device Authentication',
            2 => 'Biometric Authentication',
            3 => 'Follow Profile Settings',
        ]
    ],
    'login_notification_mode' => [
        'label' => 'Login Notification Settings',
        'options' => [
            0 => 'Disabled',
            1 => 'Only for Different Devices/IPs',
            2 => 'Always Enabled',
            3 => 'Use :account_type Profile Settings',
        ]
    ],

    // Account Types
    'account_types' => [
        'member' => 'Member',
        'user' => 'User',
    ],

    // Confirmation Dialogs
    'save_confirmation' => 'Save Confirmation',
    'update_confirmation' => 'Update Confirmation',
    'create_confirmation' => 'Create Confirmation',


    // Status Descriptions (Detailed)
    'status_descriptions' => [
        'draft_description' => 'Draft status. Not published.',
        'published_description' => 'Published immediately.',
        'scheduled_description' => 'Published at specified date and time.',
    ],

    // Two-Factor Authentication Method Help Text (Generic)
    'two_factor_method_help' => [
        'single' => 'This authentication method is enabled in :account_type global settings.',
        'multiple' => 'Please select the authentication method to use. You can choose from methods enabled in :account_type global settings.',
        'email' => 'An authentication code will be sent to your registered email address.',
        'device' => 'Authenticate using your registered device.',
        'biometric' => 'Use biometric authentication such as fingerprint or face recognition.',
    ],

    // Two-Factor Authentication Mode Options (Profile)
    'two_factor_mode_options' => [
        'disabled' => 'Disabled',
        'only_new_device' => 'Only for New Devices/IPs',
        'always' => 'Always Enabled',
    ],

    // Two-Factor Authentication & Login Notification Generic Help Text
    'two_factor_help' => 'Set when to use two-factor authentication.',
    'two_factor_method' => 'Two-Factor Authentication Method',
    'two_factor_method_help' => 'Select the authentication method to use for two-factor authentication.',
    'two_factor_global_setting_fixed' => 'This setting is fixed by global settings.',
    'two_factor_method_global_setting_fixed' => 'This authentication method is fixed by global settings.',
    'login_notification_mode' => 'Login Notification Settings',
    'login_notification_help' => 'Set when to send login notifications.',

    // Global Setting Control Messages (Account Type Support)
    'global_setting_controlled' => [
        'two_factor' => 'This setting is controlled by the :account_type global settings and cannot be changed.',
        'two_factor_method' => 'This authentication method is controlled by the :account_type global settings and cannot be changed.',
    ],

    // Profile & Settings Generic Items
    'login_notification' => 'Login Notification',
    'two_factor_authentication' => 'Two-Factor Authentication',
    'two_factor_mode' => 'Two-Factor Authentication Mode',
    'notification_settings' => 'Notification Settings',
    'two_factor_settings' => 'Two-Factor Authentication Settings',
    'login_notification_settings' => 'Login Notification Settings',

    // Settings Sections (Generic)
    'basic_info' => 'Basic Information',
    'password_settings' => 'Password Settings',
    'security_settings' => 'Security Settings',
    'account_settings' => 'Account Settings',
    'management_operations' => 'Management Operations',
    'appearance_settings' => 'Appearance Settings',
    'language_settings' => 'Language Settings',

    // Authentication Method Related
    'available_methods' => 'available methods',

    // Save Confirmation Dialog (Detailed)
    'save_confirmation_title' => 'Save Confirmation',
    'save_confirmation_message' => 'Do you want to save the changes?',
    'update_confirmation_title' => 'Update Confirmation',
    'update_confirmation_message' => 'Do you want to update the settings with this content?',
];
