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
        'submit' => 'Authenticate and Login',
        'verify' => 'Verify',
        'resend' => 'Resend Authentication Code',
        'invalid' => 'The authentication code is incorrect or has expired.',
        'invalid_code' => 'The authentication code is incorrect or has expired.',
        'resend_success' => 'Email has been resent.',
    ],
    
    // Device Authentication
    'device' => [
        'title' => 'Device Authentication',
        'prompt' => 'A device authentication email has been sent to your member account email address.<br>Please approve the authentication request from the email.',
        'waiting_title' => 'Waiting for Device Authentication',
        'waiting_message' => 'Please approve the authentication request from the email.',
    ],
    
    // Biometric Authentication
    'biometric' => [
        'title' => 'Biometric Authentication',
        'prompt' => 'Please use biometric authentication to log in.',
        'waiting_title' => 'Waiting for Biometric Authentication',
        'waiting_message' => 'Please use Touch ID, Face ID, or fingerprint authentication.',
    ],

    // Common
    'back_to_login' => 'Back to Login',
];
