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
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'failed_with_attempts' => 'These credentials do not match our records. :attempts attempts remaining.',
    'lockout' => 'Too many login attempts. Please try again in :minutes minutes.',
    'ip_lockout' => 'Login attempts from this IP address are temporarily restricted.',
    '2fa_locked_out' => 'Too many two-factor authentication attempts. Please try again in :minutes minutes.',
    '2fa_invalid_code' => 'The authentication code is incorrect.',
    '2fa_code_expired' => 'The authentication code has expired.',
    'recovery_code_used' => 'Recovery code used. :count codes remaining.',
    'recovery_code_warning' => 'You are running low on recovery codes. Please generate new recovery codes from your profile settings.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'email_not_verified' => 'This account has not been verified. Please check the verification email sent to your registered email address and complete the account verification.',
    'verify_email_login_required' => 'To complete account verification, please log in. Verification will be completed automatically after login.',
    'verify_email_change_login_required' => 'To complete email address change, please log in. The change will be completed automatically after login.',
    'verification_required' => 'Email verification required',
    
    // Passkey Authentication
    'passkey_https_required' => 'Passkey authentication requires HTTPS connection.',
    'passkey_not_registered' => 'No Passkey registered.',
    'passkey_challenge_error' => 'Failed to generate Passkey authentication challenge.',
    'passkey_verification_failed' => 'Passkey authentication failed.',
    'passkey_verification_error' => 'An error occurred during Passkey verification.',
    'verification_notice_message' => 'This account has not completed email verification. To use the admin panel, please click the link in the verification email sent to your registered email address and log in.',
    'verification_link_sent' => 'A new verification link has been sent to your email address.',
    'resend_verification_email' => 'Resend Verification Email',
    'verification_token_expired' => 'The verification token has expired. Please request a new verification email.',
    'verification_member_mismatch' => 'The logged-in account does not match the account pending verification.',
    'verification_invalid' => 'The verification token is invalid.',
    'verification_failed' => 'Email verification failed. Please try again.',

    // Common Fields
    'login_title' => ':type Login',
    'login_description' => 'Please log in to your :type account.',
    'email' => 'Email Address',
    'password' => 'Password',
    'remember_me' => 'Remember Me',
    'login' => 'Login',
    'forgot_password' => 'Forgot your password?',
    'no_account' => "Don't have an account?",
    'register' => 'Register',

    // Account Status
    'account_inactive' => 'This account has been deactivated.',
    'account_suspended' => 'This account has been suspended.',
    'email_not_verified' => 'Email address has not been verified.',

    // Two-Factor Authentication (Common)
    'two_factor_title' => 'Two-Factor Authentication',
    'two_factor_description' => 'A verification code has been sent to your registered email address.',
    'two_factor_code' => 'Verification Code',
    'two_factor_verify' => 'Verify',
    'two_factor_resend' => 'Resend Code',
    'two_factor_invalid' => 'The verification code is invalid.',
    'two_factor_expired' => 'The verification code has expired.',
    'two_factor_sent' => 'Verification code has been sent.',
    'two_factor_send_failed' => 'Failed to send verification code.',
    'two_factor_resend_success' => 'Verification code has been resent.',
    'recovery_code_invalid' => 'The recovery code is invalid.',
    'session_expired' => 'Your session has expired. Please log in again.',

    // Password Reset (Common)
    'reset_password_title' => 'Reset Password',
    'reset_password_description' => 'Enter your email address and we will send you a password reset link.',
    'reset_password_button' => 'Send Reset Link',
    'reset_password_sent' => 'We have emailed your password reset link.',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm Password',
    'reset_password' => 'Reset Password',
    'reset_password_success' => 'Your password has been reset.',
    'delete_account' => 'Delete Account',

    // Email Verification (Common)
    'verify_email_title' => 'Verify Email Address',
    'verify_email_description' => 'A verification link has been sent to your registered email address.',
    'verify_email_message' => 'Thank you for registering. Please click the verification link sent to your email address to complete account verification. If you did not receive the email, click the resend button.',
    'verify_email_resend' => 'Resend Verification Email',
    'verify_email_sent' => 'A new verification link has been sent to your email address.',
    'verify_email_success' => 'Email address has been verified.',
    'back_to_login' => 'Back to Login',

    // Logout
    'logout' => 'Logout',
    'logout_success' => 'You have been logged out.',

    // Registration (Common)
    'register_title' => 'Register',
    'register_description' => 'Please enter your account information.',
    'account_name' => 'Account Name',
    'account_name_placeholder' => 'Alphanumeric and underscore (3-20 characters)',
    'account_name_help' => 'Account name used for login. Only alphanumeric characters and underscores (_) are allowed.',
    'display_name' => 'Display Name',
    'password_confirmation' => 'Confirm Password',
    'register_button' => 'Register',
    'already_registered' => 'Already have an account?',
    'register_success' => 'Registration completed.',
    'send_password_reset_link' => 'Send Password Reset Link',

    // Other
    'password_incorrect' => 'The password is incorrect.',
];

