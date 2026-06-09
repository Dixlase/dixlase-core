<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
    'account_inactive' => 'This account is currently inactive. Please contact a site administrator.',

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

    // Login Form (Common)
    'login_field' => 'Email Address or Account Name',
    'continue' => 'Continue',
    'password_field' => 'Password',
    'login_button' => 'Login',
    'back_to_identifier' => 'Back',
    'remember_me' => 'Remember me',
    'forgot_password' => 'Forgot your password?',

    // Passkey Authentication (Common)
    'passkey_login' => 'Login with Passkey',
    'login_with_passkey' => 'Login with Passkey',
    'passkey_cancelled' => 'Passkey authentication was cancelled',
    'no_passkey_registered' => 'No passkey registered',
    'two_fa_disabled' => 'Two-factor authentication is disabled',
    'change_account' => 'Change Account',

    // IP Restriction
    'ip_lockout' => 'Login attempt limit reached from this IP address. Please try again later.',
];
