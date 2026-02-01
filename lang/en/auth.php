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
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines (Common)
    |--------------------------------------------------------------------------
    |
    | Common authentication language lines used in both admin and user areas
    |
    */

    'failed' => 'These credentials do not match our records.',
    'failed_with_attempts' => 'These credentials do not match our records. You have :attempts attempts remaining.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'lockout' => 'Too many login attempts. Please try again in :minutes minutes.',
    'two_fa_locked_out' => 'Too many two-factor authentication attempts. Please try again in :minutes minutes.',

    // Password Reset
    'reset' => [
        'sent' => 'We have emailed your password reset link.',
        'token' => 'This password reset token is invalid.',
        'user' => 'We cannot find a user with that email address.',
        'password' => 'Passwords must be at least eight characters and match the confirmation.',
        'reset' => 'Your password has been reset.',
        'throttled' => 'Please wait before retrying.',
    ],

    // Authentication Mode (for notifications)
    'authentication_mode' => [
        'notification' => [
            'disabled' => 'Disabled',
            'different_device' => 'Different device or IP only',
            'always' => 'Always notify',
            'use_profile_setting' => 'Use profile setting',
        ],
    ],
];
