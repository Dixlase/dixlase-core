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
    'title' => 'Two-Factor Authentication Management',
    'passkey_devices' => 'Passkey Devices',
    'no_passkey_devices' => 'No passkey devices registered',
    'add_passkey' => 'Add Passkey',
    'delete' => 'Delete',
    'delete_all' => 'Delete All',
    'registered_at' => 'Registered',
    'last_used' => 'Last Used',
    'passkey_info_title' => 'About Passkey',
    'passkey_info_1' => 'Once registered, you won\'t need to enter authentication codes for two-factor authentication.',
    'passkey_info_2' => 'You can log in using biometric authentication (fingerprint, face recognition, etc.) or device PIN.',
    'passkey_info_3' => 'Supports Touch ID, Face ID, Windows Hello, and more.',
    'passkey_info_4' => 'Administrators cannot add Passkey devices. Only members themselves can do this.',
    'passkey_warning_title' => 'No Passkey Device Registered',
    'passkey_warning_message' => 'You need to register a device to use Passkey. Passkey authentication cannot be used without a registered device.',
    'passkey_warning_action' => 'Please register a device using the "Add Passkey" button below.',
    'device_count' => 'Registered: :current / :max devices',
    'max_devices_reached' => 'You have reached the maximum number of devices (:max). To add a new device, please delete an existing one.',
    'recovery_codes_title' => 'Recovery Codes',
    'recovery_codes_remaining' => 'You have :count recovery codes remaining',
    'recovery_codes_not_generated' => 'Recovery codes have not been generated',
    'recovery_codes_regenerate' => 'Regenerate Recovery Codes',
    'recovery_codes_generate' => 'Generate Recovery Codes',
    'recovery_codes_info_title' => 'About Recovery Codes',
    'recovery_codes_info_1' => 'Recovery codes are used when you cannot access your two-factor authentication device',
    'recovery_codes_info_2' => 'Each code can only be used once',
    'recovery_codes_info_3' => 'Store them in a safe place',
    'recovery_codes_info_4' => 'You can regenerate codes if lost',
    'recovery_codes_info_5' => 'Regenerating will invalidate old codes',
    'recovery_codes_info_6' => 'We recommend generating new codes periodically',
    'passkey_not_supported' => 'Your browser does not support Passkey',
    'passkey_register_success' => 'Passkey registered successfully',
    'passkey_register_error' => 'Failed to register Passkey',
    'passkey_cancelled' => 'Passkey registration was cancelled',
    'passkey_already_registered' => 'This Passkey is already registered',
    'passkey_delete_success' => 'Passkey deleted successfully',
    'passkey_delete_error' => 'Failed to delete Passkey',
    'passkey_delete_all_error' => 'Failed to delete all Passkeys',
    'confirm_delete_passkey' => 'Are you sure you want to delete this Passkey?',
    'recovery_codes_error' => 'Failed to generate recovery codes',
    'trusted_devices_title' => 'Trusted Devices',
    'no_trusted_devices' => 'No trusted devices',
    'unknown_device' => 'Unknown Device',
    'ip_address' => 'IP Address',
    'trusted_devices_info_title' => 'About Trusted Devices',
    'trusted_devices_info_1' => 'Two-factor authentication is skipped on trusted devices',
    'trusted_devices_info_2' => 'We recommend reviewing them regularly for security',
    'trusted_devices_info_3' => 'Please remove unnecessary devices',
    'confirm_delete_trusted_device' => 'Are you sure you want to delete this trusted device?',
    'trusted_device_delete_success' => 'Trusted device deleted successfully',
    'trusted_device_delete_error' => 'Failed to delete trusted device',
    'trusted_device_delete_all_error' => 'Failed to delete all trusted devices',
    'confirm_delete_trusted_device_title' => 'Delete Trusted Device',
    'confirm_delete_trusted_device_message' => 'Are you sure you want to delete this trusted device?',
    'confirm_delete_all_trusted_devices_title' => 'Delete All Trusted Devices',
    'confirm_delete_all_trusted_devices_message' => 'Are you sure you want to delete all trusted devices?',
    'confirm_delete_passkey_title' => 'Delete Passkey',
    'confirm_delete_passkey_message' => 'Are you sure you want to delete this Passkey?',
    'confirm_delete_all_passkeys_title' => 'Delete All Passkeys',
    'confirm_delete_all_passkeys_message' => 'Are you sure you want to delete all Passkeys?',
    'recovery_codes_confirm_title' => 'Generate Recovery Codes',
    'recovery_codes_confirm_message' => 'Do you want to generate recovery codes? Existing codes will be invalidated.',
    'confirm_delete_recovery_codes_title' => 'Delete Recovery Codes',
    'confirm_delete_recovery_codes_message' => 'Are you sure you want to delete all recovery codes?',
    'recovery_codes_delete_success' => 'Recovery codes deleted successfully',
    'recovery_codes_delete_error' => 'Failed to delete recovery codes',
];
