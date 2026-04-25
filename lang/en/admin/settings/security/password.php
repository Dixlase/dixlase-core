<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Password Settings',
    'session_management' => 'Session Management Settings',
    'session_management_description' => 'Manage password settings.',
    'session_encrypt' => 'Session Encryption',
    'session_encrypt_help' => 'Encrypt session data before storing.',
    'session_lifetime' => 'Default Session Lifetime',
    'session_lifetime_help' => 'Set the session lifetime in minutes (1-43200 minutes).',
    'password_security_settings' => 'Password Security Settings',
    'password_security_description' => 'Manage password-related security settings.',
    'pwned_password_check' => 'Password Breach Check',
    'pwned_password_check_help' => 'Check passwords against breach databases when setting.',
    'pwned_password_api_info' => 'This check uses the Have I Been Pwned API. It is secure as only the first 5 characters of the SHA-1 hash are used, not the password itself.',
    'pwned_settings' => 'Password Dictionary Attack Protection',
    'pwned_password_settings' => 'Password Dictionary Attack Protection',
    'pwned_password_description' => 'Check if passwords are in breach databases to prevent use of unsafe passwords.',
    'pwned_password_check_enabled' => 'Dictionary Attack Protection',
    'pwned_password_help' => 'When enabled, password safety will be checked using the Have I Been Pwned API during member creation, editing, and password changes.',

    // Common Settings
    'common_settings' => 'Common Settings',
    'common_settings_description' => 'Common password security settings applied to all user types.',

    // Default Password Policy
    'default_password_policy' => 'Default Password Policy',
    'default_password_policy_description' => 'Default password requirements applied to members and when plugin custom settings are disabled.',
    'conditions' => 'Password Conditions',
    'password_requirements' => 'Password Requirements',
    'min_length' => 'Minimum Length',
    'require_uppercase' => 'Require Uppercase',
    'require_number' => 'Require Number',
    'require_symbol' => 'Require symbols',
    'security_warning' => 'For enhanced security, we recommend setting stricter requirements.',
    'reset_enabled' => 'Enable password reset feature',
    'plugin_custom_hint' => 'Plugins (such as DixlaseUsers) can set their own password policies. If a plugin has custom settings enabled, those will take precedence.',

    // Password reset feature
    'password_reset_feature' => 'Password Reset Feature',
    'password_reset_feature_description' => 'Enable or disable the password reset feature for members who have forgotten their passwords.',
    'reset_settings' => 'Password Reset Settings',
    'reset_help' => 'When enabled, members who have forgotten their password can reset it via email. A verified email address and a configured mail server are required.',

    'settings_updated' => 'Password security settings have been updated.',
];
