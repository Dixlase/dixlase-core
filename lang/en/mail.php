<?php

return [
    'mailers' => [
        'smtp' => 'SMTP (Standard)',
        'sendmail' => 'Sendmail',
        'log' => 'Log (File Output)',
        'array' => 'Array (Memory Storage)',
        'failover' => 'Failover (Redundancy)',
        'mailgun' => 'Mailgun (External)',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ],
    'encryptions' => [
        '' => 'None',
        'tls' => 'TLS (Recommended)',
        'ssl' => 'SSL',
    ],
    
    // Password Reset Email
    'reset_password' => [
        'subject' => 'Reset Password Notification',
        'greeting' => 'Hello!',
        'line1' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'line2' => 'This password reset link will expire in :count minutes.',
        'line3' => 'If you did not request a password reset, no further action is required.',
        'regards' => 'Regards,',
    ],

    // Login Notification Email
    'login_notification' => [
        'subject_user' => '[Login Notification] :name, you have logged in',
        'subject_system' => '[System Notification] Admin login detected',
        'title' => 'Login Notification',
        'user_message' => ':name, you have logged in.',
        'system_message' => 'System Notification',
        'details_title' => 'Login Details:',
        'datetime' => 'Date & Time:',
        'ip_address' => 'IP Address:',
        'user_agent' => 'User-Agent:',
        'security_notice' => 'If you do not recognize this login, please change your password immediately.',
        'regards' => 'Regards',
    ],

    // Test Mail
    'test_mail' => [
        'subject' => 'Mail Test',
        'greeting' => 'Hello!',
        'test_details_title' => 'Send Mail Test',
        'app_name' => 'Application Name:',
        'test_datetime' => 'Test Execution Date & Time:',
        'verification_required' => 'To confirm that this email is being received properly, please click the button below.',
        'verify_button' => 'Confirm Email Receipt',
        'manual_verification' => 'If the button does not work, please copy and access the following URL directly in your browser:',
        'regards' => 'Regards,',
    ],
];
