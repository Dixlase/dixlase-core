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
    'heading' => 'Authentication & Session Settings',
    'session_management' => 'Session Management Settings',
    'session_management_description' => 'Manage system-wide session settings. Dixlase uses Guard-Aware Database session management.',
    'session_encrypt' => 'Session Encryption',
    'session_encrypt_help' => 'Encrypt session data before storing.',
    'session_lifetime' => 'Default Session Lifetime',
    'session_lifetime_help' => 'Set the session lifetime in minutes (1-43200 minutes).',
    'password_security_settings' => 'Password Security Settings',
    'password_security_description' => 'Manage password-related security settings.',
    'pwned_password_check' => 'Password Breach Check',
    'pwned_password_check_help' => 'Check passwords against breach databases when setting.',
    'pwned_password_api_info' => 'This check uses the Have I Been Pwned API. It is secure as only the first 5 characters of the SHA-1 hash are used, not the password itself.',
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
    'password_requirements' => 'Password Requirements',
    'min_length' => 'Minimum Length',
    'require_uppercase' => 'Require Uppercase',
    'require_number' => 'Require Number',
    'require_symbol' => 'Require Symbol',
    'reset_enabled' => 'Enable Password Reset',
    'plugin_custom_hint' => 'Plugins (such as DixlaseUsers) can set their own password policies. When plugin custom settings are enabled, they take priority.',
    
    'settings_updated' => 'Password security settings have been updated.',
];
