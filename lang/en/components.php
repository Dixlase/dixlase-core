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
    // Pagination related
    'pagination' => [
        'navigation' => 'Page navigation',
        'page' => 'Page :current of :total',
        'previous' => 'Previous',
        'next' => 'Next',
        'first' => 'First',
        'last' => 'Last',
        'showing' => 'Showing :first to :last of :total results',
        'per_page' => 'Per page',
        'per_page_label' => 'Items',
        'total_count' => 'Total: :total items',
        'total_items' => 'Total :count items',
        'total_pages' => 'Total :count pages',
        'no_results' => 'No matching data found',
        'items_suffix' => ' items',
        'sort_by' => 'Sort by',
        'asc' => 'Asc',
        'desc' => 'Desc',
        'ascending' => 'Ascending (A-Z, Old-New)',
        'descending' => 'Descending (Z-A, New-Old)',
    ],


    // Form related
    'forms' => [
        'placeholder' => [
            'search' => 'Enter search keywords...',
            'email' => 'Enter email address',
            'password' => 'Enter password',
            'name' => 'Enter name',
            'title' => 'Enter title',
            'description' => 'Enter description',
        ],
        'validation' => [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
            'unique' => 'This value already exists',
            'min_length' => 'Please enter at least :min characters',
            'max_length' => 'Please enter no more than :max characters',
            'confirmed' => 'Password confirmation does not match',
        ],
    ],

    // Status related
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

    // Message related
    'messages' => [
        'success' => 'Operation completed successfully',
        'loading' => 'Loading...',
        'no_data' => 'No data available',
        'confirm_delete' => 'Are you sure you want to delete this?',
        'unsaved_changes' => 'You have unsaved changes',
    ],

    // Table related
    'table' => [
        'no_data' => 'No data available',
        'select_all' => 'Select all',
        'selected_count' => ':count selected',
        'sort_asc' => 'Sort ascending',
        'sort_desc' => 'Sort descending',
        'caption' => 'Data list',
        'unknown_role' => 'Unknown role',
    ],
    
    // Filter related
    'filters' => [
        'search_keyword' => 'Keyword',
        'role_filter' => 'Role Filter',
        'status_filter' => 'Status Filter',
        'clear_button' => 'Clear',
    ],

    // Modal related
    'modal' => [
        'delete_title' => 'Confirm Delete',
        'delete_message' => 'This action cannot be undone. Are you sure you want to delete this?',
    ],

    // Email input related
    'email_input' => [
        'confirmation_label' => 'Email Address (Confirmation)',
        'confirmation_help' => 'Copy & paste is disabled. Please type manually to confirm.',
        'match_status' => 'Email address match status',
        'match_success' => 'Matched',
        'match_error' => 'Not matched',
    ],

    // Password tools related
    'password_messages' => [
        'toolbar_label' => 'Password Tools',
        'strength' => [
            'error' => 'Password does not meet requirements',
            'normal' => 'Normal strength',
            'strong' => 'Strong password',
        ],
        'tooltip' => [
            'generate' => 'Generate',
            'copy' => 'Copy',
            'toggle' => 'Toggle visibility',
        ],
        'copied' => 'Password copied!',
        'requirements' => [
            // Static display items
            'length' => '8 or more characters',
            'lowercase' => 'Include at least 1 lowercase letter',
            'number' => 'Include at least 1 number',
            'uppercase' => 'Include at least 1 uppercase letter',
            'symbol' => 'Include at least 1 symbol (!@#$%^&* etc.)',

            // Dynamic messages (with parameters)
            'length_full' => ':min or more characters (recommended :recommended or more)',
            'length_simple' => ':min or more characters',

            // Special messages for optional cases
            'lowercase_optional_note' => 'Include lowercase letters',
            'number_optional_note' => 'Include numbers',
            'uppercase_optional_note' => 'Include uppercase letters',
            'symbol_optional_note' => 'Including symbols（!@#$%^&*-_=+ etc.)',

            // Strength labels
            'weak' => 'Weak',
            'normal' => 'Normal',
            'strong' => 'Strong',
            'very_strong' => 'Very Strong',
        ],
        'error' => 'Password does not meet requirements.',
    ],

    // Appearance mode selector
    'appearance_mode' => [
        'auto' => 'Auto',
        'light' => 'Light',
        'dark' => 'Dark',
        'auto_description' => 'Follow system settings',
        'light_description' => 'Display in light mode',
        'dark_description' => 'Display in dark mode',
    ],

    // Login notification settings
    'login_notification' => [
        'label' => 'Login Notification Mode',
        'help' => 'Configure when to send email notifications for logins.',
        'global_setting_help' => 'This setting is controlled by global configuration and cannot be changed.',
        'options' => [
            'disabled' => 'Disabled',
            'different_device' => 'Notify only on different device/IP login',
            'always' => 'Always notify',
        ],
    ],

    // Two-factor authentication settings
    'two_factor' => [
        'mode_label' => 'Two-Factor Authentication Mode',
        'help' => 'When enabled, additional authentication is required at login.',
        'method_label' => 'Two-Factor Authentication Method',
        'email_always_enabled' => 'Email authentication is always enabled',
        'passkey' => 'Passkey',
        'passkey_disabled_globally' => 'Disabled in global settings',
        'method_note' => 'Two-factor authentication methods are managed in global settings.',
        'change_in_global_settings' => 'Change in global settings',
        'default_method' => 'Default Authentication Method',
        'passkey_disabled_default_email_only' => 'When passkey is disabled, only email authentication is available.',
        'default_method_help' => 'Select the authentication method to use first at login.',
        'options' => [
            'disabled' => 'Disabled',
            'different_device' => 'Different device/IP login',
            'always' => 'Always enabled',
        ],
    ],

    // Two-factor authentication management
    'two_factor_management' => [
        'title' => 'Two-Factor Authentication Management',
        'passkey_devices' => 'Passkey Devices',
        'no_passkey_devices' => 'No passkey devices registered',
        'add_passkey' => 'Add Passkey',
        'delete' => 'Delete',
        'delete_all' => 'Delete All',
        'registered_at' => 'Registered',
        'last_used' => 'Last Used',
        'passkey_info_title' => 'About Passkey',
        'passkey_info_1' => 'Passkey is a secure authentication method using biometrics or PIN',
        'passkey_info_2' => 'Multiple devices can be registered',
        'passkey_info_3' => 'Each device can be named and managed individually',
        'recovery_codes_title' => 'Recovery Codes',
        'recovery_codes_remaining' => 'You have :count recovery codes remaining',
        'recovery_codes_not_generated' => 'Recovery codes have not been generated',
        'recovery_codes_regenerate' => 'Regenerate Recovery Codes',
        'recovery_codes_generate' => 'Generate Recovery Codes',
        'recovery_codes_info_title' => 'About Recovery Codes',
        'recovery_codes_info_1' => 'Recovery codes are used when you cannot access your two-factor authentication device',
        'recovery_codes_info_2' => 'Each code can only be used once',
        'recovery_codes_info_3' => 'Store them in a safe place',
        'recovery_codes_info_4' => 'You can regenerate codes if lost',
        'recovery_codes_info_5' => 'Regenerating will invalidate old codes',
        'recovery_codes_info_6' => 'We recommend generating new codes periodically',
        // JavaScript messages
        'passkey_not_supported' => 'Your browser does not support Passkey',
        'passkey_register_success' => 'Passkey registered successfully',
        'passkey_register_error' => 'Failed to register Passkey',
        'passkey_cancelled' => 'Passkey registration was cancelled',
        'passkey_already_registered' => 'This Passkey is already registered',
        'passkey_delete_success' => 'Passkey deleted successfully',
        'passkey_delete_error' => 'Failed to delete Passkey',
        'passkey_delete_all_error' => 'Failed to delete all Passkeys',
        'confirm_delete_passkey' => 'Are you sure you want to delete this Passkey?',
        'recovery_codes_error' => 'Failed to generate recovery codes',
        // Trusted devices management
        'trusted_devices_title' => 'Trusted Devices',
        'no_trusted_devices' => 'No trusted devices',
        'unknown_device' => 'Unknown Device',
        'ip_address' => 'IP Address',
        'trusted_devices_info_title' => 'About Trusted Devices',
        'trusted_devices_info_1' => 'Two-factor authentication is skipped on trusted devices',
        'trusted_devices_info_2' => 'We recommend reviewing them regularly for security',
        'trusted_devices_info_3' => 'Please remove unnecessary devices',
        'confirm_delete_trusted_device' => 'Are you sure you want to delete this trusted device?',
        'trusted_device_delete_success' => 'Trusted device deleted successfully',
        'trusted_device_delete_error' => 'Failed to delete trusted device',
        'trusted_device_delete_all_error' => 'Failed to delete all trusted devices',
        // Modal titles
        'confirm_delete_trusted_device_title' => 'Delete Trusted Device',
        'confirm_delete_trusted_device_message' => 'Are you sure you want to delete this trusted device?',
        'confirm_delete_all_trusted_devices_title' => 'Delete All Trusted Devices',
        'confirm_delete_all_trusted_devices_message' => 'Are you sure you want to delete all trusted devices?',
        'confirm_delete_passkey_title' => 'Delete Passkey',
        'confirm_delete_passkey_message' => 'Are you sure you want to delete this Passkey?',
        'confirm_delete_all_passkeys_title' => 'Delete All Passkeys',
        'confirm_delete_all_passkeys_message' => 'Are you sure you want to delete all Passkeys?',
        'recovery_codes_confirm_title' => 'Generate Recovery Codes',
        'recovery_codes_confirm_message' => 'Do you want to generate recovery codes? Existing codes will be invalidated.',
    ],
];
