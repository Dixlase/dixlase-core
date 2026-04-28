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
    'mode_label' => 'Two-Factor Authentication Mode',
    'help' => 'When enabled, additional authentication is required at login.',
    'method_label' => 'Two-Factor Authentication Method',
    'email_always_enabled' => 'Email authentication is always enabled',
    'passkey' => 'Passkey',
    'passkey_disabled_globally' => 'Disabled in global settings',
    'method_note' => 'Two-factor authentication methods are managed in global settings.',
    'change_in_global_settings' => 'Change in global settings',
    'default_method' => 'Default Authentication Method',
    'passkey_disabled_default_email_only' => 'When passkey is disabled, only email authentication is available.',
    'default_method_help' => 'Select the authentication method to use first at login.',
    'global_setting_fixed' => 'Fixed by global settings',
    'authentication_mode' => [
        'disabled' => 'Disabled',
        'different_device' => 'Different device/IP login',
        'always' => 'Always enabled',
        'use_profile_setting' => 'Use Profile Setting',
    ],
    'options' => [
        'disabled' => 'Disabled',
        'different_device' => 'Different device/IP login',
        'always' => 'Always enabled',
        'use_profile_setting' => 'Use Profile Setting',
    ],
    'passkey_mode' => [
        'label' => 'Passkey Settings',
        'help' => [
            'profile_editable' => 'You can enable or disable passkey authentication',
            'profile_forced_disabled' => 'Passkey authentication is disabled by global settings',
            'profile_forced_enabled' => 'Passkey authentication is enabled by global settings',
        ],
        'options' => [
            'enabled' => 'Enabled',
        ],
    ],
];
