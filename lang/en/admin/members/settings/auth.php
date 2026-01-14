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
    'heading' => 'Authentication Settings',
    'description' => 'Manage authentication settings including login notifications, attempt limits, two-factor authentication, Passkey, and CAPTCHA.',
    'login_notification_global_setting' => 'Login Notification Email Global Settings',
    'login_notification_mail_test_required' => 'Mail server configuration and testing are not complete, so login notification will not work even if enabled.<br>To use login notification, complete mail server configuration and testing in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Base Settings</a>.',
    'login_attempt_limit_settings' => 'Login Attempt Limit Settings',
    'login_attempt_limit_enabled' => 'Login Attempt Limit Function',
    'login_attempt_limit_help' => 'To prevent brute force attacks, login will be restricted for a certain period if consecutive login failures occur within a short time.',
    'login_attempt_max_attempts' => 'Maximum Attempts (Identifier-based)',
    'login_attempt_max_attempts_help' => 'Maximum attempts for the same email address/account name. If login failures exceed this number for a specific account, login to that account will be temporarily restricted.',
    'login_attempt_max_attempts_ip' => 'Maximum Attempts (IP-based)',
    'login_attempt_max_attempts_ip_help' => 'Maximum attempts from the same IP address (total across all identifiers). Attempts to different accounts are also counted. Recommended to set higher than identifier-based limit to prevent brute force attacks.',
    'login_attempt_time_window' => 'Time Window (minutes)',
    'login_attempt_time_window_help' => 'Failure count within this time period will be counted.',
    'login_attempt_lockout_duration' => 'Lockout Duration (minutes)',
    'login_attempt_lockout_duration_help' => 'Set lockout duration in minutes (1-10080 minutes).',
    'lockout_notification_enabled' => 'Lockout Notification',
    'lockout_notification_help' => 'Send email notification to administrators when login attempt limit is reached.',
    'lockout_notification_mail_test_required' => 'Mail server configuration and testing are not complete, so lockout notification will not work even if enabled.<br>To use lockout notification, complete mail server testing in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Base Settings</a>.',
    'two_fa_mode_global_setting' => 'Two-Factor Authentication Global Settings',
    'two_fa_mail_test_required' => 'Mail server configuration and testing are not complete, so two-factor authentication (email authentication) will not work even if enabled.<br>To use two-factor authentication, complete mail server configuration and testing in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Base Settings</a>.',
    'enabled_two_fa_methods_label' => 'Enabled Two-Factor Authentication Methods',
    'enabled_two_fa_methods_help' => 'Email authentication is always enabled. When Passkey is enabled, members can select authentication method in profile settings.',
    'email_always_enabled_note' => 'Email authentication is always enabled as the basic authentication method available to all members.',
    'two_fa_expire_settings' => 'Two-Factor Authentication Expiration Settings',
    'two_fa_expire_minutes' => 'Authentication Expiration',
    'two_factor_expire_minutes_help' => 'Set expiration time for email authentication codes and device authentication (1-60 minutes).',
    'two_fa_resend_interval_seconds' => 'Authentication Email Resend Interval',
    'two_factor_resend_interval_seconds_help' => 'Set waiting time before authentication email can be resent (60-600 seconds, 1-10 minutes).',
    'two_fa_attempt_limit_settings' => '2FA Attempt Limit Settings',
    'two_fa_max_attempts' => 'Maximum Attempts',
    'two_fa_max_attempts_help' => 'Set maximum attempts for 2FA authentication (1-10 times). Total across all authentication methods.',
    'two_fa_attempt_window' => 'Attempt Limit Time Window',
    'two_fa_attempt_window_help' => 'Set time window for counting attempts (5-60 minutes).',
    'two_fa_lockout_duration' => 'Lockout Duration',
    'two_fa_lockout_duration_help' => 'Set lockout duration after exceeding attempt limit (5-1440 minutes).',
    'two_fa_lockout_notification_enabled' => '2FA Lockout Notification',
    'two_fa_lockout_notification_enabled_help' => 'Send email notification when lockout occurs.',
    'passkey_settings' => 'Passkey Settings',
    'passkey_enabled' => 'Passkey Function',
    'passkey_enabled_help' => 'Enable/disable Passkey (biometric authentication) function.',
    'max_passkey_devices' => 'Maximum Passkey Registrations',
    'max_passkey_devices_help' => 'Set maximum number of Passkey devices per member (1-5 devices).',
    'recovery_code_settings' => 'Recovery Code Settings',
    'recovery_codes_count' => 'Recovery Code Generation Count',
    'recovery_codes_count_help' => 'Set number of recovery codes to generate per member (1-5 codes).',
    'recovery_code_regenerate_interval' => 'Recovery Code Regeneration Interval',
    'recovery_code_regenerate_interval_help' => 'Set waiting time before recovery codes can be regenerated (1-168 hours).',
    'captcha_admin_login_settings' => 'CAPTCHA Settings (Admin Login)',
    'captcha_admin_login_settings_description' => 'Configure CAPTCHA authentication for admin login.',
    'captcha_screens' => 'CAPTCHA Display Screens',
    'captcha_admin_login_enabled' => 'Use CAPTCHA for Admin Login',
    'captcha_password_reset_enabled' => 'Use CAPTCHA for Password Reset',
    'captcha_admin_login_help' => 'When enabled, CAPTCHA authentication will be required for admin login.',
    'captcha_not_enabled' => 'CAPTCHA is not enabled. Please enable CAPTCHA in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Security Settings</a>.',
    'captcha_not_authenticated' => 'CAPTCHA authentication test is not complete. Please perform authentication test in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Security Settings</a>.',
];
