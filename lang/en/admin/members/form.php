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
 */

return [
    'account_name_help' => 'Account name used for login. Use 3-20 alphanumeric characters.',
    'display_name_help' => 'Name displayed in the admin bar and profile. If left empty, the account name will be used.',
    'password_change_only' => 'Password (only when changing)',
    'login_notification' => 'Login Notification',
    'two_fa_mode' => 'Two-Factor Authentication Mode',
    'initial_admin_status_fixed' => 'This account is the initial administrator, so the status cannot be changed.',
    'initial_admin_role_fixed' => 'Role is fixed to "Super Administrator" for the initial administrator.',
    'mail_server_not_tested' => 'Two-factor authentication is not available because mail server settings and testing have not been completed.',
    'mail_server_not_tested_login_notification' => 'Login notification is not available because mail server settings and testing have not been completed.',
    'account_verification' => 'Account Verification',
    'account_verification_disabled' => 'Account will be automatically verified because mail server setup and testing are not complete.',
    'account_verified' => 'Verified',
    'account_unverified' => 'Unverified',
    'account_verified_send_email' => 'Send verification email',
    'account_verification_help_create' => 'If you select "Send verification email", a verification email will be sent to the member when created.',
    'account_verification_help_edit' => 'If you select "Unverified", the verification status will be reset. Use the button below to send a verification email.',
    'send_verification_email_button' => 'Send Verification Email',
    'send_verification_email_title' => 'Send Verification Email Confirmation',
    'send_verification_email_confirm' => 'Are you sure you want to send the verification email? The account will be changed to unverified status.',
    'email_confirmation' => 'Email Address (Confirmation)',
    'email_confirmation_help' => 'Please re-enter the email address. To prevent input errors, you must enter the same email address as above.',
    'login_notification_global_fixed' => 'Login notification setting is fixed to ":setting" by member global settings. <br>To change this, please set the global setting to "Use Profile Setting".',
    'two_fa_global_fixed' => 'Two-factor authentication setting is fixed to ":setting" by member global settings. <br>To change this, please set the global setting to "Use Profile Setting".',
    'force_logout' => 'Force Logout',
    'force_logout_description' => 'Force this member to logout. Current session will be deleted.',
    'force_logout_button' => 'Execute Force Logout',
    'unlock_lockout' => 'Unlock Lockout',
    'unlock_lockout_description' => 'Unlock login and two-factor authentication lockout for this member. Failed attempt records will be deleted.',
    'unlock_lockout_button' => 'Unlock Lockout',
    'delete_member' => 'Delete Member',
    'delete_member_description' => 'Completely delete this member. This operation cannot be undone.',
    'delete_member_button' => 'Delete Member',
    'recovery_codes_admin_note' => 'Administrators cannot generate or regenerate recovery codes. Only the user can perform this action.',
    'delete_recovery_codes' => 'Delete Recovery Codes',
    'confirm_delete_recovery_codes_title' => 'Confirm Recovery Codes Deletion',
    'confirm_delete_recovery_codes_message' => 'Are you sure you want to delete all recovery codes for this member? After deletion, only the member can regenerate them.',
    'recovery_codes_deleted' => 'Recovery codes (:count) have been deleted.',
    'recovery_codes_delete_success_title' => 'Recovery Codes Deletion Complete',
    'recovery_codes_delete_error' => 'Failed to delete recovery codes.',
    'two_fa_method_note' => 'Global authentication method settings can be changed in',
    'change_in_global_settings' => 'Member Global Settings',
    'passkey_disabled_globally' => 'Disabled in global settings',
    'default_two_fa_method' => 'Default Authentication Method',
    'default_two_factor_method_help' => 'Select the authentication method to be displayed first during two-factor authentication.',
    'passkey_disabled_default_email_only' => 'Passkey authentication is disabled, so the default authentication method is automatically set to email authentication.',
    'two_fa_management_admin_note' => 'Administrators cannot add Passkey devices or generate recovery codes. Only deletion is allowed. Addition and generation can only be performed by the member themselves.',
    'two_fa_cannot_enable_warning' => 'Cannot enable two-factor authentication. A mail server configuration, passkey registration, or recovery code generation is required.',
    'passkey_all_deleted' => 'Passkey devices (:count) have been deleted.',

    // Role permission descriptions
    'role_permissions_info' => 'Permission Scope by Role',
    'role_super_admin_description' => 'Full access to all administrative functions, including managing other administrators. Has permission to change system settings.',
    'role_admin_description' => 'Access to most admin panel features, but cannot manage other administrators or change system settings.',
    'role_editor_description' => 'Can create, edit, and publish content. Can also edit content created by other members.',
    'role_contributor_description' => 'Can create and edit content, but cannot publish. Requires approval from editors or higher.',
    'role_guest_description' => 'Minimal viewing permissions only. Cannot access most administrative functions.',

    // Passkey Registration Prompt Modal Settings
    'passkey_prompt_settings' => 'Passkey Registration Prompt Modal Settings',
    'passkey_prompt_settings_description' => 'Configure whether to display the passkey registration prompt modal for this member.',
    'passkey_prompt_dismissed' => 'Don\'t show passkey registration prompt modal',
    'passkey_prompt_dismissed_help' => 'When enabled, the passkey registration prompt modal will not be displayed when this member logs in.',
];
