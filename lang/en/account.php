<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    // Common My Page Translation Keys
    'title' => 'My Page',
    'dashboard' => 'Dashboard',
    'welcome' => 'Hello, :name',
    'dashboard_description' => 'View your account information and manage settings.',

    // Profile
    'profile' => 'Profile',
    'profile_description' => 'Manage your personal information and account settings.',
    'edit_profile' => 'Edit Profile',

    // Security
    'security' => 'Security',
    'security_description' => 'Manage your password and two-factor authentication settings.',
    'two_fa_enabled' => 'Two-Factor Authentication: Enabled',
    'two_fa_disabled' => 'Two-Factor Authentication: Disabled',

    // Account Information
    'account_info' => 'Account Information',
    'email' => 'Email Address',
    'last_login' => 'Last Login',

    // Email Not Verified
    'email_not_verified_title' => 'Email Address Not Verified',
    'email_not_verified_description' => 'Email verification is required to access all account features.',

    // Profile Edit
    'profile_edit' => 'Edit Profile',
    'basic_info_description' => 'Manage your basic account information.',
    'account_name' => 'Account Name',
    'display_name' => 'Display Name',
    'last_name' => 'Last Name',
    'first_name' => 'First Name',
    'last_name_kana' => 'Last Name (Kana)',
    'first_name_kana' => 'First Name (Kana)',
    'zip_code' => 'Postal Code',
    'pref' => 'Prefecture',
    'city' => 'City',
    'address' => 'Address',
    'building' => 'Building Name',
    'building_help' => 'Enter apartment or building name and room number.',
    'kana' => 'Katakana',
    'phone' => 'Phone Number',
    'tel' => 'Phone Number',
    'tel_help_japanese' => 'Mobile: 000-0000-0000, Landline: 00-0000-0000 or 000-000-0000',
    'gender' => 'Gender',
    'gender_male' => 'Male',
    'gender_female' => 'Female',
    'gender_other' => 'Other',
    'birth_date' => 'Date of Birth',
    'save' => 'Save',
    'profile_updated' => 'Profile has been updated.',

    // Password Change
    'change_password' => 'Change Password',
    'password_settings' => 'Password Settings',
    'password_settings_description' => 'Change your account password.',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm New Password',
    'password_updated' => 'Password has been changed.',
    
    // Password Reset
    'reset_password_title' => 'Password Reset',
    'reset_password_description' => 'If you forgot your password, we will send a password reset link to your registered email address.',
    
    // Appearance Settings
    'appearance_settings' => 'Appearance Settings',
    'appearance_settings_description' => 'Manage display theme and interface settings.',
    'theme_mode' => 'Theme Mode',
    'theme_light' => 'Light Mode',
    'theme_dark' => 'Dark Mode',
    'theme_auto' => 'Auto (Follow System Settings)',
    'appearance_updated' => 'Appearance settings have been updated.',
    
    // Email Notification Settings
    'notification_settings' => 'Email Notification Settings',
    'notification_settings_description' => 'Manage the types of email notifications you receive.',
    'login_notification' => 'Login Notification',
    'login_notification_description' => 'Receive notifications when logging in from a new device.',
    'notification_updated' => 'Notification settings have been updated.',

    // Two-Factor Authentication Settings
    'two_fa_settings' => 'Two-Factor Authentication Settings',
    'two_fa_mode' => 'Two-Factor Authentication Mode',
    'two_factor_mode_disabled' => 'Disabled',
    'two_factor_mode_always' => 'Always Enabled',
    'two_factor_mode_new_device' => 'New Devices Only',
    'two_fa_method' => 'Authentication Method',
    'two_factor_method_email' => 'Email Authentication',
    'two_factor_method_passkey' => 'Passkey',
    'two_factor_method_recovery' => 'Recovery Code',

    // Trusted Devices
    'trusted_devices' => 'Trusted Devices',
    'trusted_devices_description' => 'Manage devices that skip two-factor authentication.',
    'no_trusted_devices' => 'No trusted devices.',
    'remove_device' => 'Remove',
    'device_removed' => 'Device has been removed.',

    // Delete Account
    'delete_account' => 'Delete Account',
    'delete_account_description' => 'Deleting your account will permanently remove all your data. This action cannot be undone.',
    'delete_account_confirm' => 'Are you sure you want to delete your account?',
    'account_deleted' => 'Account has been deleted.',

    // Forgot Password
    'forgot_password' => [
        'title' => 'Forgot Your Password?',
        'heading' => 'Reset Password',
        'description' => 'Enter your registered email address and we will send you a password reset link.',
        'email' => 'Email Address',
        'submit' => 'Send Reset Link',
        'back_to_login' => 'Back to Login',
    ],

    // Reset Password (New Password)
    'reset_password' => [
        'title' => 'Reset Password',
        'heading' => 'Set New Password',
        'email' => 'Email Address',
        'password' => 'New Password',
        'password_confirmation' => 'Confirm New Password',
        'submit' => 'Change Password',
    ],

    // Email Verification
    'verify_email' => [
        'title' => 'Email Verification',
        'heading' => 'Please Verify Your Email Address',
        'description' => 'A verification link has been sent to your registered email address. Please check your email.',
        'link_sent' => 'A new verification link has been sent.',
        'resend' => 'Resend Verification Email',
        'logout' => 'Logout',
        'login_required' => 'Please log in to complete your email address verification.',
    ],
    
    'verification_required' => 'Email Verification Required',
    'verification_notice_message' => 'Before using your account, you need to verify your email address. Please check the verification email sent during registration.',
    'verification_link_sent' => 'A new verification link has been sent to your email address.',
    'verify_email_login_required' => 'Please log in to complete your email address verification.',
    'verify_email_change_login_required' => 'Please log in to complete your email address change.',
    'account_verification_success' => 'Your account verification is complete. Please log in.',
    'email_verification_success' => 'Email address change has been completed.',

    // Confirm Password
    'confirm_password' => [
        'title' => 'Confirm Password',
        'heading' => 'Confirm Your Password',
        'description' => 'For security purposes, please enter your password to continue.',
        'password' => 'Password',
        'submit' => 'Confirm',
    ],

    // Two-Factor Authentication Details (My Page specific)
    'two_fa_authentication' => 'Two-Factor Authentication',
    'two_fa_description' => 'Enhance your security by setting up additional authentication at login.',
];
