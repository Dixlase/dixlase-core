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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'login' => [
        'title' => 'Admin Login',
        'header' => 'Admin Login',
        'description' => 'Please log in to access the admin panel.',
        'login_field' => 'Email or Account Name',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'captcha' => 'Security Verification',
        'back_to_welcome' => 'Back to Site',
        'continue' => 'Continue',
        'change_account' => 'Change Account',
        'login_with_passkey' => 'Sign in with Passkey',
        'no_passkey_registered' => 'No passkey registered',
        'passkey_cancelled' => 'Passkey authentication cancelled',
        'two_fa_disabled' => 'Two-factor authentication is disabled. Please log in with password.',
    ],
    'forgot_password' => [
        'title' => 'Password Reset',
        'header' => 'Password Reset',
        'description' => 'Forgot your password?<br>Please enter your email address.<br>We will send you a password reset link.',
        'email' => 'Email Address',
        'send_reset_link' => 'Send Password Reset Link',
        'back_to_login' => 'Back to Login',
    ],
    'reset_password' => [
        'title' => 'Set New Password',
        'header' => 'Set New Password',
        'description' => 'Please set your new password.',
        'email' => 'Email Address',
        'password' => 'New Password',
        'password_confirmation' => 'Confirm Password',
        'reset_password_button' => 'Reset Password',
    ],
];
