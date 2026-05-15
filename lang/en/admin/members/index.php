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
    'heading' => 'Member Management',
    'force_logout_all' => 'Force Logout All Members',
    'force_logout_all_confirmation_title' => 'Confirm Force Logout All',
    'force_logout_all_confirmation_message' => 'Are you sure you want to force logout all members except yourself? This action cannot be undone.',
    'search_title' => 'Member Search',
    'search_placeholder' => 'Search by member name or email address',
    'table' => [
        'unknown_role' => 'Unknown Role',
        'caption' => 'Member List',
    ],
    'messages' => [
        'initial_member_role_protected' => 'The role of the initial member account cannot be changed.',
        'initial_member_status_protected' => 'The initial member account cannot be deactivated.',
        'initial_member_cannot_delete' => 'The initial member account cannot be deleted.',
        'permissions_saved' => 'Permission settings have been saved.',
        'insufficient_permissions' => 'You do not have permission to perform this operation.',
        'deleted' => 'Member has been deleted.',
    ],
    'validation' => [
        'mail_server_not_tested' => 'To enable lockout notification function, password reset function, login notification function, and two-factor authentication function, you must pass the mail server connection test in the basic settings.',
        'mail_server_warning' => 'Mail Server Not Configured',
        'mail_server_warning_message' => 'Lockout notification function, password reset function, login notification function, and two-factor authentication function will not work because mail server configuration and testing have not been completed.',
        'mail_server_test_passed' => 'Mail server connection test passed. Lockout notification, password reset function, login notification function, and two-factor authentication function can be used.',
        'please_configure_in' => 'Please configure mail server settings in the basic settings',
        'name_required' => 'Name is required.',
        'email_required' => 'Email address is required.',
        'email_invalid' => 'Email address format is invalid.',
        'email_unique' => 'This email address is already registered.',
        'password_required' => 'Password is required.',
        'password_min' => 'Password must be at least 8 characters.',
        'password_confirmed' => 'Password confirmation does not match.',
        'role_required' => 'Please select a role.',
        'role_invalid' => 'Invalid role selected.',
        'appearance_required' => 'Please select appearance setting.',
        'appearance_invalid' => 'Invalid appearance setting selected.',
        'status_required' => 'Please select status.',
        'status_invalid' => 'Invalid status selected.',
    ],
    'force_setting_1' => 'Individual settings cannot be changed because',
    'force_setting_2' => 'is selected in member global settings.',
    'delete_confirm_title' => 'Delete Member',
    'delete_confirm_message' => 'Are you sure you want to delete member ":name"? This action cannot be undone.',
    'admin_operations' => 'Admin Operations',
    'initial_admin_account' => 'Initial Admin Account',
    'initial_admin_restriction' => 'This account is the initial administrator, so deletion and forced logout are not allowed. These operations are restricted to maintain system security.',
    'force_logout_button' => 'Force Logout',
    'delete_member_button' => 'Delete Member',
    'role_options' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'contributor' => 'Contributor',
    ],
];
