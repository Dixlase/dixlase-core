<?php

return [
    // Email Authentication
    'email' => [
        'title' => 'Two-Factor Authentication',
        'prompt' => <<<TEXT
We have sent you an email with an authentication code.
Please enter the 6-digit authentication code from the email.
TEXT,
        'code_title' => 'Enter Authentication Code',
        'code_prompt' => 'Please enter the 6-digit authentication code from the email.',
        'code_label' => 'Authentication Code',
        'expire_notice' => 'The authentication code is valid for :minutes minutes.',
        'expire_label' => 'Code expires in',
        'expired' => 'Expired',
        'submit' => 'Authenticate and Login',
        'verify' => 'Verify',
        'resend' => 'Resend Authentication Code',
        'invalid' => 'The authentication code is incorrect or has expired.',
        'invalid_code' => 'The authentication code is incorrect or has expired.',
        'invalid_with_attempts' => 'The authentication code is incorrect. Remaining attempts: :attempts',
        'resend_success' => 'Email has been resent.',
        'resend_failed' => 'Failed to resend code',
        'network_error' => 'A network error occurred',
        'seconds_suffix' => 's',
    ],
    
    // Device Authentication
    'device' => [
        'title' => 'Device Authentication',
        'prompt' => 'A device authentication email has been sent to your member account email address.<br>Please approve the authentication request from the email.',
        'waiting_title' => 'Waiting for Device Authentication',
        'waiting_message' => 'Please approve the authentication request from the email.',
        'start_button' => 'Start Authentication',
        'help_text' => 'Please approve the authentication from the link in the email.',
        'remaining_time' => 'Time remaining',
        'seconds_suffix' => 's',
        'success_title' => 'Authentication Successful',
        'success_message' => 'Device authentication completed. Redirecting...',
        'error_title' => 'Authentication Failed',
        'error_message' => 'Device authentication failed. Please try again.',
        'timeout_title' => 'Authentication Timeout',
        'timeout_message' => 'Authentication time limit exceeded. Please try again.',
        'retry_button' => 'Retry',
        'challenge_start_failed' => 'Failed to start challenge',
        'auth_denied' => 'Authentication was denied',
        'network_error' => 'A network error occurred',
        'expire_label' => 'Expires in',
        'resend_button' => 'Resend Authentication Email',
        'sending' => 'Sending...',
        'approved_title' => 'Login Approved',
        'approved_description' => 'Login has been approved.',
        'approved_message' => 'Login request has been approved.<br>Return to the login screen and login will be completed automatically.',
        'approved_close' => 'You can close this window.',
        'denied_title' => 'Login Denied',
        'denied_description' => 'Login request has been denied.',
        'denied_message' => 'Login request has been denied.<br>Login attempt has been invalidated.',
        'denied_close' => 'You can close this window.',
        'error_page_title' => 'Error',
        'error_page_description' => 'An error occurred while processing the request.',
        'error_page_message' => 'An error occurred while processing the request.',
        'error_page_close' => 'You can close this window.',
    ],
    
    // Biometric Authentication
    'biometric' => [
        'title' => 'Biometric Authentication',
        'prompt' => 'Please use biometric authentication to log in.',
        'waiting_title' => 'Waiting for Biometric Authentication',
        'waiting_message' => 'Please use Touch ID, Face ID, or fingerprint authentication.',
    ],

    // Passkey Authentication
    'passkey' => [
        'title' => 'Passkey Authentication',
        'prompt' => 'Please log in using your Passkey.',
        'waiting_title' => 'Waiting for Passkey Authentication',
        'waiting_message' => 'Please use Touch ID, Face ID, or your registered Passkey.',
    ],

    // Recovery Code
    'recovery_code' => [
        'title' => 'Recovery Code',
        'prompt' => 'Please enter your recovery code. If you cannot access your device, you can use a recovery code to log in.',
        'code_label' => 'Recovery Code',
        'format_hint' => 'Enter 20 digits (with or without hyphens)',
        'submit' => 'Authenticate and Login',
        'invalid' => 'Invalid recovery code.',
        'invalid_with_attempts' => 'Invalid recovery code. Remaining attempts: :attempts',
        'use_recovery_code' => 'Use Recovery Code',
        'back_to_2fa' => 'Back to Two-Factor Authentication',
    ],

    // Common
    'back_to_login' => 'Back to Login',
    'alternative_methods_prompt' => 'Use a different authentication method?',
    'awaiting_approval' => 'Awaiting approval...',
    
    // 2FA Settings (Common)
    'settings' => [
        'title' => 'Two-Factor Authentication Settings',
        'mode_label' => 'Two-Factor Authentication',
        'method_label' => 'Authentication Method',
        'help' => 'Configure when to use two-factor authentication.',
    ],
    
    // 2FA Mode
    'mode' => [
        'disabled' => 'Disabled',
        'enabled' => 'Enabled',
        'use_profile' => 'Follow Profile Settings',
        'always' => 'Always Enabled',
    ],
    
    // 2FA Method
    'method' => [
        'email' => 'Email Authentication',
        'passkey' => 'Passkey (Biometric)',
    ],
    
    // Device Management
    'devices' => [
        'passkey_devices' => 'Passkey Devices',
        'no_devices' => 'No devices registered',
        'add_device' => 'Add Device',
        'delete_device' => 'Delete Device',
        'delete_all' => 'Delete All',
        'registered_at' => 'Registered',
        'last_used' => 'Last Used',
    ],
    
    // Recovery Code Management
    'recovery_codes' => [
        'title' => 'Recovery Codes',
        'remaining' => 'You have :count recovery codes remaining',
        'none' => 'No recovery codes have been generated',
        'generate' => 'Generate Recovery Codes',
        'regenerate' => 'Regenerate Recovery Codes',
        'download' => 'Download Recovery Codes',
        'warning' => 'Please store your recovery codes in a safe place.',
    ],
    
    // Lockout
    'lockout' => [
        'message' => 'Maximum two-factor authentication attempts reached. Please try again in :minutes minutes.',
        'locked' => 'Maximum two-factor authentication attempts reached. You will be locked out for :minutes minutes.',
    ],
];
