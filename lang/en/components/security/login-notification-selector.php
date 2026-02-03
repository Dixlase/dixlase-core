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
    'label' => 'Login Notification Mode',
    'help' => 'Configure when to send email notifications for logins.',
    'global_setting_help' => 'This setting is controlled by global configuration and cannot be changed.',
    'global_setting_locked' => 'This setting is locked by global configuration and cannot be changed.',
    'options' => [
        'disabled' => 'Disabled',
        'different_device' => 'Notify only on different device/IP login',
        'always' => 'Always notify',
        'use_profile_setting' => 'Use Profile Setting',
    ],
];
