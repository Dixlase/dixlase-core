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
    'title' => 'Two-Factor Authentication',

    // Two-factor authentication methods
    'method' => [
        'email' => 'Email Authentication',
        'passkey' => 'Passkey Authentication',
    ],

    // Security levels
    'security' => [
        'level' => [
            'very_high' => 'Very High',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ],
        'description' => [
            'passkey' => 'The most secure method using biometric authentication or security keys. Uses authentication credentials stored on your device, making it resistant to phishing attacks and enabling secure login.',
            'email' => 'Uses authentication codes sent to your email address. Protected by expiration time and usage limits, but depends on your email account security. Passkey is recommended for higher security needs.',
            'recovery_code' => 'Emergency backup method. Use when Passkey or email authentication is unavailable. Recovery codes can only be used once and become invalid after use. Store them in a safe place.',
        ],
        'recommended' => 'Recommended',
        'backup' => 'Backup',
    ],

    // Authentication method switching
    'switch_method_prompt' => 'Switch to another authentication method',
    'switch_to_passkey' => 'Switch to Passkey Authentication',
    'switch_to_email' => 'Switch to Email Authentication',

    // Common
    'back_to_login' => 'Back to Login',
    'alternative_methods_prompt' => 'Use another authentication method?',
    'awaiting_approval' => 'Awaiting approval...',

    // Lockout
    'lockout' => [
        'message' => 'Two-factor authentication attempt limit reached. Please try again in :minutes minutes.',
        'locked' => 'Two-factor authentication attempt limit reached. Locked for :minutes minutes.',
    ],

    // 2FA Mode
    'mode' => [
        'disabled' => 'Disabled',
        'enabled' => 'Enabled',
        'use_profile' => 'Follow Profile Setting',
        'always' => 'Always Enabled',
    ],
];
