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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    'title' => 'Profile',
    'heading' => 'Profile Settings',
    'use_system_default' => 'Use System Default',
    'language_help' => 'Individual language setting. If not selected, the system default language will be used.',
    'account_name_help' => 'Account name used for login. Use 3-20 alphanumeric characters.',
    'display_name_help' => 'Name displayed in the admin bar and profile. If left empty, the account name will be used.',
    'password_change_only' => 'Password (Enter only if changing)',
    'updated' => 'Profile has been updated.',
    'two_fa_updated' => 'Two-factor authentication settings have been updated.',
    'login_notification_global_setting_help' => 'This setting is controlled by the global member settings.',
    'single_method_available' => 'Available Authentication Method',
    'submit' => 'Update Profile',
    'updated_with_email_verification' => 'Profile has been updated. A verification email has been sent to your new email address. Please check your email and complete the verification.',
    'confirm_title' => 'Profile Update Confirmation',
    'confirm_message' => 'Do you want to update your profile?',
    'email_verification_success' => 'Email address change has been completed.',
    'account_verification_success' => 'Account verification has been completed.',
    'email_verification_invalid' => 'The verification link is invalid.',
    'email_already_verified' => 'This email address has already been verified.',
    'pending_email_notice' => 'Pending change to :email. Please check the verification email sent and complete the verification.',
    'current_email' => 'Current email address: :email',
    'email_change_help' => 'If you change your email address, a verification email will be sent to the new address. The change will not take effect until verification is complete.',
    'email_change_help_no_mail' => 'If you change your email address, it will be updated immediately.',
    'updated_email_immediate' => 'Profile has been updated. Email address has been changed.',
    'two_factor_requires_mail_server' => 'Two-factor authentication is not available because mail server settings and testing have not been completed.',
    'two_fa_management' => '2FA Management',
    'two_fa_disabled_notice' => 'To manage two-factor authentication, please enable two-factor authentication in your profile settings.',
    'passkey_disabled_notice' => 'Passkey device management is not available because passkey authentication is not enabled.',
    'passkey_no_devices_notice' => 'Passkey authentication is enabled, but no devices have been registered yet. Please register a Passkey device in <a href=":url" class="underline font-semibold">Two-Factor Authentication Management</a>.',
    'passkey_registered' => 'Passkey device has been registered successfully.',
    'passkey_deleted' => 'Passkey device has been deleted.',
];
