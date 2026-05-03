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
    'settings_title' => 'Installation - Step 1',
    'settings_header' => 'Basic Settings',
    'settings_description' => 'Please enter the basic information to set up your site.',

    // Semantic Headings
    'site_information' => 'Site Information',
    'admin_account_information' => 'Administrator Account Information',
    'admin_account_details' => 'Administrator Account Details',
    'password_settings' => 'Password Settings',
    'password_setup' => 'Password Setup',

    // Site Information
    'site_name' => 'Site Name',
    'site_name_help' => 'Enter within 60 characters for SEO and to prevent layout issues.',
    'admin_email' => 'Admin Email',

    // Administrator Account
    'admin_account_name' => 'Account Name',
    'admin_account_name_placeholder' => 'Enter alphanumeric characters (e.g., siteadmin2025)',
    'admin_account_name_requirements' => 'Use 3-20 alphanumeric characters.<br>In production, avoid easily guessable names such as admin, administrator, root, user, test, demo, dixlase, manager, or webmaster.',
    'admin_display_name' => 'Display Name',
    'admin_display_name_placeholder' => 'Name displayed in admin bar (e.g., John Doe)',
    'admin_display_name_requirements' => 'This name will be displayed in the admin bar and profile. If left empty, the account name will be used.',
    'admin_password' => 'Admin Password',
    'admin_password_confirmation' => 'Confirm Password',
    'admin_password_confirmation_note' => 'Please re-enter the same password for confirmation.',

    // Validation
    'validation' => [
        'admin_account_name_required' => 'Please enter an account name.',
        'admin_account_name_alpha_num' => 'Account name must contain only alphanumeric characters.',
        'admin_account_name_length' => 'Account name must be between 3 and 20 characters.',
    ],

    // Password Requirements
    'password_requirements' => [
        'length' => '8 or more characters',
        'uppercase' => 'At least one uppercase letter',
        'lowercase' => 'At least one lowercase letter',
        'number' => 'At least one number',
        'symbol' => 'Including symbols (!@#$%^&* etc.) increases strength',
    ],
    'password_strength_messages' => [
        'weak' => '❌ Requirements not met',
        'medium' => '⚠️ Medium',
        'strong' => '✅ Strong',
    ],
    'password_strength_error' => 'Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, and one number.',
    'password_strength_weak' => 'Password is too weak.',
    'password_strength_medium' => 'Password strength is medium.',
    'password_strength_strong' => 'Password is secure.',
];
