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
    // Basic Operations
    'create' => 'Create',
    'add' => 'Add',
    'edit' => 'Edit',
    'save' => 'Save',
    'delete' => 'Delete',
    'copy' => 'Copy',
    'copied' => 'Copied',

    // System Operations
    'settings' => 'Settings',
    'profile' => 'Profile',
    'language_timezone' => 'Language & Timezone Settings',
    'clear' => 'Clear',
    'cancel' => 'Cancel',
    'confirm' => 'Confirm',

    // Navigation
    'back' => 'Back',
    'next' => 'Next',
    'close' => 'Close',
    'finish' => 'Finish',
    'index' => 'Index',

    // Search & Display
    'search' => 'Search',
    'preview' => 'Preview',

    // File Operations
    'upload' => 'Upload',
    'download' => 'Download',

    // System Operations
    'install' => 'Install',
    'uninstall' => 'Uninstall',
    'enable' => 'Enable',
    'disable' => 'Disable',
    'execute' => 'Execute',

    // Authentication
    'login' => 'Login',
    'logout' => 'Logout',

    // Confirmation & Response
    'yes' => 'Yes',
    'no' => 'No',
    'ok' => 'OK',

    // Status & Attributes
    'required' => 'Required',
    'optional' => 'Optional',
    'default_method' => 'Default',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'available_methods' => 'available methods',
    
    // Theme
    'auto' => 'Auto',
    'light' => 'Light',
    'dark' => 'Dark',

    // Language
    'ja' => 'Japanese',
    'en' => 'English',
    
    // Account Types
    'account_types' => [
        'member' => 'Member',
        'user' => 'User',
    ],
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


    // Basic Attributes
    // Basic Information
    'id' => 'ID',
    'name' => 'Name',
    'title' => 'Title',
    'description' => 'Description',
    'details' => 'Details',
    'version' => 'Version',
    'author' => 'Author',
    'license' => 'License',
    'unknown' => 'Unknown',
    'value' => 'Value',
    'status' => 'Status',

    // Authentication Information
    'email' => 'Email',
    'password' => 'Password',
    'password_confirmation' => 'Password Confirmation',

    // Contact Information
    'address' => 'Address',
    'phone' => 'Phone',
    'fax' => 'FAX',
    'url' => 'URL',

    // Personal Information
    'gender' => 'Gender',
    'birthday' => 'Birthday',

    // File Related
    'file_name' => 'File Name',
    'file_type' => 'File Type',
    'upload_date' => 'Upload Date',
    'uploaded_by' => 'Uploaded By',
    
    // Media Related
    'select_media' => 'Select Media',
    'all_types' => 'All Types',
    'images' => 'Images',
    'videos' => 'Videos',
    'documents' => 'Documents',
    'items_selected' => 'items selected',
    'no_media_found' => 'No media found',
    'error_loading_media' => 'Failed to load media',
    'caption' => 'Caption',
    'caption_placeholder' => 'Enter image caption',
    'alt_text' => 'Alt Text',
    'alt_text_placeholder' => 'Enter alternative text for accessibility',
    'description_placeholder' => 'Enter detailed description of the media',

    // Content Related
    'content' => 'Content',
    'slug' => 'Slug',
    'published_at' => 'Published At',

    // Design & Display
    'color' => 'Color',
    'font' => 'Font',
    'admin_theme' => 'Admin Theme',
    'appearance_mode' => 'Appearance Mode',

    // System Settings
    'site_name' => 'Site Name',
    'locale' => 'Language',
    'timezone' => 'Timezone',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_message' => 'Maintenance Message',

    // UI Elements
    'actions' => 'Actions',
    'required_fields' => 'Required Fields',

    // Messages & Status
    'warning' => 'Warning',
    'info' => 'Information',
    'error' => 'Error',
    'unknown' => 'Unknown',
    
    // Time Units
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'deleted_at' => 'Deleted At',
    'minutes' => 'minutes',
    'hours' => 'hours',
    'days' => 'days',
    // Search & Filter Related
    'search_keyword' => 'Keyword',
    'role_filter' => 'Role',
    'status_filter' => 'Status',
    'clear_button' => 'Clear',
    
    // Filter Options
    'filters' => [
        'all_roles' => 'All Roles',
        'all_statuses' => 'All Statuses',
        'role_filter' => 'Role Filter',
        'status_filter' => 'Status Filter',
        'clear_button' => 'Clear',
    ],

    // Log Related
    'operation' => 'Operation',
    'method' => 'Method',
    'uri' => 'URI',
    'route' => 'Route',
    'controller' => 'Controller',
    'ip' => 'IP Address',
    'user_agent' => 'User Agent',
    'time' => 'Time',

    // ===========================================
    // Two-Factor Authentication (Unified Section)
    // ===========================================
    'two_factor_authentication' => 'Two-Factor Authentication',
    'two_factor_settings' => 'Two-Factor Authentication Settings',
    
    // Two-Factor Authentication Mode
    'two_factor_mode' => [
        'label' => 'Two-Factor Authentication Settings',
        'options' => [
            0 => 'Disabled',
            1 => 'New Device Only',
            2 => 'Always Enabled',
            3 => 'Follow Profile Setting',
        ],
    ],
    
    // Two-Factor Authentication Mode Options (for Profile)
    'two_factor_mode_options' => [
        'disabled' => 'Disabled',
        'only_new_device' => 'Different Device/IP Only',
        'always' => 'Always Enabled',
    ],
    
    // Two-Factor Authentication Method
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
            3 => 'Use Profile Settings',
        ]
    ],
    
    // Two-Factor Authentication Help Text
    'two_factor_help' => 'Set when to use two-factor authentication.',
    'two_factor_global_setting_fixed' => 'This setting is fixed by global settings.',
    'two_factor_method_global_setting_fixed' => 'This authentication method is fixed by global settings.',
    
    // Two-Factor Authentication Method Help Text (Detailed)
    'two_factor_method_help' => [
        'single' => 'This authentication method is enabled in the :account_type global settings.',
        'multiple' => 'Please select the authentication method to use. You can choose from methods enabled in the :account_type global settings.',
        'email' => 'Send authentication code to registered email address.',
        'device' => 'Authenticate with registered device.',
        'biometric' => 'Use biometric authentication such as fingerprint or face recognition.',
    ],
    'save_confirmation' => 'Save Confirmation',
    'update_confirmation' => 'Update Confirmation',
    'create_confirmation' => 'Create Confirmation',
    'delete_confirmation' => 'Delete Confirmation',


    // Save Confirmation Dialog (Detailed)
    'save_confirmation_title' => 'Save Confirmation',
    'save_confirmation_message' => 'Do you want to save the changes?',
    'update_confirmation_title' => 'Update Confirmation',
    'update_confirmation_message' => 'Do you want to update the settings with this content?',

    // Login notification
    'login_notification' => 'Login Notification',
    'login_notification_mode' => [
        'label' => 'Login Notification Mode',
        'options' => [
            0 => 'Disabled',
            1 => 'New devices only',
            2 => 'Always notify',
            3 => 'Follow profile settings',
        ],
    ],

    // Global Setting Control Messages (Account Type Support)
    'global_setting_controlled' => [
        'two_factor' => 'This setting is controlled by the :account_type global settings and cannot be changed.',
        'two_factor_method' => 'This authentication method is controlled by the :account_type global settings and cannot be changed.',
    ],
    'notification_settings' => 'Notification Settings',
    'login_notification_settings' => 'Login Notification Settings',

    // Settings Sections
    'basic_info' => 'Basic Information',
    'name' => 'Name',
    'description' => 'Description',
    'email' => 'Email Address',
    'security_settings' => 'Security Settings',
    'account_settings' => 'Account Settings',
    'management_operations' => 'Management Operations',
    'appearance_settings' => 'Appearance Settings',
    'language_settings' => 'Language Settings',
    // Authentication Method Related
    'available_methods' => 'available methods',
];
