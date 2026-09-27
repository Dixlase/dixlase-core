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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    // Device management
    'devices' => [
        'passkey_devices' => 'Passkey Devices',
        'no_devices' => 'No devices registered',
        'add_device' => 'Add Device',
        'delete_device' => 'Delete Device',
        'delete_all' => 'Delete All',
        'registered_at' => 'Registered',
        'last_used' => 'Last Used',
    ],

    // Recovery code management
    'recovery_codes' => [
        'title' => 'Recovery Codes',
        'generate' => 'Generate Recovery Codes',
        'regenerate' => 'Regenerate Recovery Codes',
        'not_generated' => 'Recovery codes have not been generated yet.',
        'remaining' => ':count recovery codes remaining.',
        'warning' => 'These codes will only be displayed once.<br>Please save them in a safe place by downloading, copying, taking a screenshot, photo, or printing them.<br>Do not share these codes with anyone.',
        'download' => 'Download',
        'copy' => 'Copy',
        'confirm_saved' => 'I have saved the recovery codes in a safe place',
        'auto_generated_title' => 'Recovery Codes Auto-Generated',
        'auto_generated_message' => 'After your first successful two-factor authentication, recovery codes have been automatically generated for emergencies. These codes will not be shown again, so please save them.',
        'already_exists' => 'Recovery codes have already been generated. Regenerate them if you need new codes.',
    ],
];
