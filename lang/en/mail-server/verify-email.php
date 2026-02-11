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
    // Email Verification (Common)
    'subject' => 'Verify Your Email Address',
    'subject_account' => 'Verify Your :type Account',
    'greeting' => 'Hello :name!',
    'message_create' => 'Thank you for registering. Please click the button below to complete your email verification.',
    'message_email_change' => 'Your email address has been changed. Please click the button below to complete the email address change.',
    'message_resend' => 'Email verification is required. Please click the button below to complete the verification.',
    'action_verify_account' => 'Verify Email Address',
    'action_change_email' => 'Change Email Address',
    'manual_verification' => 'If you cannot click the button, please copy and paste the following URL into your browser:',
    'expiration' => 'This verification link will expire in :minutes minutes.',
    'security_notice' => '【IMPORTANT】If you did not request this email, please ignore it. Your account will not be activated unless you click the verification link. A third party may have mistakenly registered using this email address, but your personal information will not be compromised.',
    'regards' => 'Best regards',
    
    // Member Email Verification
    'member' => [
        'subject' => 'Verify Email Address Change',
        'subject_account' => 'Verify Your Member Account',
        'subject_create' => 'Verify Your Email Address',
        'subject_email_change' => 'Verify Email Address Change',
        'subject_resend' => 'Verify Your Email Address (Resent)',
        'greeting' => 'Hello :name!',
        'message_create' => 'Thank you for registering. Please click the button below to complete your email verification.',
        'message_email_change' => 'Your email address has been changed. Please click the button below to complete the email address change.',
        'message_resend' => 'Email verification is required. Please click the button below to complete the verification.',
        'action_verify_account' => 'Verify Email Address',
        'action_change_email' => 'Change Email Address',
        'action_resend' => 'Verify Email Address',
        'manual_verification' => 'If you cannot click the button, please copy and paste the following URL into your browser:',
        'expiration' => 'This verification link will expire in :minutes minutes.',
        'security_notice' => '【IMPORTANT】If you did not request this email, please ignore it. Your account will not be activated unless you click the verification link. A third party may have mistakenly registered using this email address, but your personal information will not be compromised.',
        'regards' => 'Best regards',
    ],
    
    // Member Verification Completed Notification
    'member_verification_completed' => [
        'subject' => 'Account Verification Completed',
        'greeting' => 'Hello :name!',
        'message' => 'Your member account verification has been completed.',
        'member_info' => '【Member Information】',
        'name' => 'Name',
        'email' => 'Email Address',
        'login_info' => 'You can log in to the admin panel from the following URL.',
        'url_info' => '【URL Information】',
        'front_url' => 'Front Page URL',
        'admin_url' => 'Admin Panel URL',
        'thanks' => 'Thank you for using our service.',
        'regards' => 'Best regards',
    ],
];
