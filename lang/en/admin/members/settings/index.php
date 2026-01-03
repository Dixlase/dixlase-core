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
    'heading' => 'Member Global Settings',
    'description' => 'Manage global settings for member passwords, sessions, and authentication.',
    
    'nav' => [
        'password' => 'Password Settings',
        'session' => 'Session Settings',
        'auth' => 'Authentication Settings',
    ],
    
    'password_min_length' => 'Minimum Length',
    'characters' => 'characters',
    'requirements' => 'Requirements',
    'uppercase' => 'Uppercase',
    'number' => 'Number',
    'symbol' => 'Symbol',
    'no_requirements' => 'No additional requirements',
    'session_lifetime' => 'Session Lifetime',
    'system_default' => 'System Default',
    'custom_session_enabled' => 'Custom Settings Enabled',
    'custom_session_disabled' => 'Using System Default',
    'two_factor' => 'Two-Factor Authentication',
    'optional' => 'Optional',
    'required' => 'Required',
    'login_attempt_limit' => 'Login Attempt Limit',
    'roles_description' => 'Configure member permissions and access control.',
    'force_logout_heading' => 'Force Logout',
    'force_logout_description' => 'Force logout all admin members. Sessions of all currently logged-in members will be deleted.',
    'force_logout_all_button' => 'Force Logout All Members',
    'force_logout_all_modal' => [
        'title' => 'Force Logout All Members Confirmation',
        'message' => 'Are you sure you want to force logout all members? This will delete sessions of all currently logged-in members.',
        'confirm_label' => 'Execute Force Logout',
    ],
    
    // Messages
    'updated' => 'Member settings have been updated.',
    
    // Common units
    'minutes' => 'minutes',
    'seconds' => 'seconds',
    'hours' => 'hours',
    'times' => 'times',
    'codes' => 'codes',
];
