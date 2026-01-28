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
    'heading' => 'Password Settings',
    'description' => 'Configure password requirements, reset functionality, and dictionary attack protection for members.',
    'conditions' => 'Password Requirements',
    'min_length' => 'Minimum Password Length',
    'min_length_options' => [
        8 => '8 characters or more',
        12 => '12 characters or more',
        16 => '16 characters or more',
    ],
    'require_uppercase' => 'Require uppercase letters',
    'require_number' => 'Require numbers',
    'require_symbol' => 'Require symbols',
    'security_warning' => 'Lowering requirements increases security risks. Please be cautious.',
    'reset_settings' => 'Password Reset Function Settings',
    'reset_enabled' => 'Password Reset on Login Screen',
    'reset_help' => 'Enabling this may increase security risks, so please be cautious.<br>When disabled, the password reset link will be hidden on the admin login screen and the password reset function will be unavailable.<br>When disabled, reset passwords from the member management edit screen.',
    'reset_mail_test_required' => 'Mail server configuration and testing are not complete, so the password reset function will not work even if enabled.<br>To use the password reset function, complete mail server configuration and testing in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">Base Settings</a>.',
    'pwned_settings' => 'Password Dictionary Attack Protection Settings',
    'pwned_check_enabled' => 'Dictionary Attack Protection',
    'pwned_help' => 'When enabled, password safety is checked using the Have I Been Pwned API during member creation, editing, and password changes.<br>Prevents use of passwords found in breach databases.',
    'pwned_api_info' => 'This check uses the Have I Been Pwned API. The password itself is not sent; only hashed information is used, making it safe.',
    
    // Phase 2: Security Settings Integration
    'password_policy' => 'Password Policy',
    'password_policy_description' => 'Member password policy is managed in global settings.',
    'managed_in_security_settings' => 'Password policy is managed in Global Settings > Security Settings.',
    'go_to_security_settings' => 'Open Security Settings',
    'current_policy' => 'Current Policy',
    'characters' => ' characters',
];
