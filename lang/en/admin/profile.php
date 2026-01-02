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
    'title' => 'Profile',
    'heading' => 'Profile Settings',
    'name' => 'Name',
    'description' => 'Description',
    'email' => 'Email Address',
    'use_system_default' => 'Use System Default',
    'language_help' => 'Individual language setting. If not selected, the system default language will be used.',
    'account_name_help' => 'Account name used for login. Use 3-20 alphanumeric characters.',
    'display_name_help' => 'Name displayed in the admin bar and profile. If left empty, the account name will be used.',
    'password_change_only' => 'Password (Enter only if changing)',
    'updated' => 'Profile has been updated.',
    'login_notification_global_setting_fixed' => 'Fixed by Global Setting',
    'login_notification_global_setting_help' => 'This setting is controlled by the global member settings. Please contact an administrator to request changes.',
    'default_method' => 'Default method to use. You can choose from methods enabled in member global settings.',
    'single_method_available' => 'Available Authentication Method',
    'submit' => 'Update Profile',
    'updated_with_email_verification' => 'Profile has been updated. A verification email has been sent to your new email address. Please check your email and complete the verification.',
    'confirm_title' => 'Profile Update Confirmation',
    'confirm_message' => 'Do you want to update your profile?',
    'email_verification_success' => 'Email address change has been completed.',
    'account_verification_success' => 'Account verification has been completed.',
    'email_verification_invalid' => 'The verification link is invalid.',
    'email_already_verified' => 'This email address has already been verified.',
    'pending_email_notice' => 'Pending change to :email. Please check the verification email sent and complete the verification.',
    'current_email' => 'Current email address: :email',
    'email_change_help' => 'If you change your email address, a verification email will be sent to the new address. The change will not take effect until verification is complete.',
    'email_change_help_no_mail' => 'If you change your email address, it will be updated immediately.',
    'updated_email_immediate' => 'Profile has been updated. Email address has been changed.',
    
    // 2FA Management (Profile-specific)
    '2fa_management' => 'Two-Factor Authentication Management',
    'recovery_codes' => 'Recovery Codes',
    'passkey_devices' => 'Passkey (Biometric) Devices',
    
    // Recovery Codes Management
    'recovery_codes_generate_confirm' => 'Generate recovery codes?<br>Please store the generated codes in a safe place.',
    'recovery_codes_regenerate_confirm' => 'Regenerate recovery codes? All existing recovery codes will be invalidated.',
    'recovery_codes_generated' => 'Recovery codes have been generated.',
    'recovery_codes_regenerated' => 'Recovery codes have been regenerated.',
    'recovery_codes_generation_error' => 'Failed to generate recovery codes.',
    'recovery_codes_regenerate_too_soon' => 'Recovery codes cannot be regenerated until :time.',
    
    // Passkey Information
    'passkey_info_title' => 'About Passkey',
    'passkey_info_1' => 'Once registered, you won\'t need to enter authentication codes for two-factor authentication.',
    'passkey_info_2' => 'You can log in using biometric authentication (fingerprint, face recognition) or device PIN.',
    'passkey_info_3' => 'Compatible with Touch ID, Face ID, Windows Hello, and more.',
    'passkey_info_4' => 'Administrators cannot add Passkey devices. Only the member themselves can do this.',
    
    // Passkey Management
    'passkey_not_supported' => 'Your browser does not support Passkey.',
    'passkey_device_name_prompt' => 'Enter a name for this device (e.g., iPhone, MacBook Pro)',
    'passkey_register_success' => 'Passkey registered successfully.',
    'passkey_registered' => 'Passkey registered successfully.',
    'passkey_register_success_title' => 'Passkey Registration Complete',
    'passkey_register_error' => 'Failed to register Passkey.',
    'passkey_register_options_error' => 'Failed to get Passkey registration options.',
    'passkey_cancelled' => 'Passkey registration was cancelled.',
    'passkey_already_registered' => 'This Passkey is already registered.',
    'passkey_deleted' => 'Passkey has been deleted.',
    'passkey_delete_success_title' => 'Passkey Deletion Complete',
    'passkey_deleted_all' => 'All Passkeys (:count) have been deleted.',
    'passkey_not_found' => 'Passkey not found.',
    'passkey_delete_error' => 'Failed to delete Passkey.',
    'passkey_delete_all_error' => 'Failed to delete all Passkeys.',
    'device_delete_success_title' => 'Device Deletion Complete',
    'no_passkeys_to_delete' => 'No Passkeys to delete.',
    'all_passkeys_deleted' => 'All Passkeys (:count) have been deleted.',
    'confirm_delete_passkey_title' => 'Confirm Passkey Deletion',
    'confirm_delete_passkey_message' => 'Are you sure you want to delete this Passkey?',
    'confirm_delete_all_passkeys_title' => 'Confirm Delete All Passkeys',
    'confirm_delete_all_passkeys_message' => 'Are you sure you want to delete all Passkeys? This action cannot be undone.',
    
    // Passkey Device Name Input
    'passkey_device_name_title' => 'Enter Device Name',
    'passkey_device_name_message' => 'Please give this Passkey device an identifiable name.',
    'passkey_device_name_label' => 'Device Name',
    
    // Recovery Codes Information
    'recovery_codes_info_title' => 'About Recovery Codes',
    'recovery_codes_info_1' => 'Recovery codes are an emergency backup method when you cannot access your two-factor authentication device.',
    'recovery_codes_info_2' => 'Recovery codes are only displayed when generated and will not be shown again.',
    'recovery_codes_admin_note' => 'Administrators cannot generate or regenerate recovery codes. Only the member themselves can do this.',
    'recovery_codes_info_3' => 'Each generated recovery code can only be used once and becomes invalid after use.',
    'recovery_codes_info_4' => 'Once recovery codes are generated, they cannot be regenerated for a certain period of time.',
    'recovery_codes_info_5' => 'Do not share recovery codes with others.',
    'recovery_codes_info_6' => 'Save the generated recovery codes in a safe place by downloading, copying, taking a screenshot, photographing, or printing them.',
];
