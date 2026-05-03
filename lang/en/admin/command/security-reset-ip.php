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

    'table_not_found' => 'security_settings table not found.',
    'usage' => 'Usage:',
    'option_show' => 'Show current IP restriction settings',
    'option_disable_all' => 'Disable all IP restrictions',
    'option_add_ip' => 'Add IP to allowed list',
    'option_remove_blocked' => 'Remove IP from blocked list',
    'option_force' => 'Execute without confirmation',
    'current_settings' => '[Admin IP Restriction Settings]',
    'front_settings' => '[Front IP Restriction Settings]',
    'setting' => 'Setting',
    'value' => 'Value',
    'admin_allow_enabled' => 'Allow List Enabled',
    'admin_allowed_ips' => 'Allowed IP List',
    'admin_block_enabled' => 'Block List Enabled',
    'admin_blocked_ips' => 'Blocked IP List',
    'front_allow_enabled' => 'Allow List Enabled',
    'front_allowed_ips' => 'Allowed IP List',
    'front_block_enabled' => 'Block List Enabled',
    'front_blocked_ips' => 'Blocked IP List',
    'none' => '(none)',
    'confirm_disable_all' => '⚠️ Are you sure you want to disable all IP restrictions? This will allow access from any IP.',
    'confirm_add_ip' => 'Add IP :ip to the allowed list?',
    'confirm_remove_ip' => 'Remove IP :ip from the blocked list?',
    'cancelled' => 'Operation cancelled.',
    'disabled_all' => '✅ All IP restrictions have been disabled.',
    'security_warning' => '⚠️ For security reasons, please reconfigure appropriate IP restrictions after recovery.',
    'invalid_ip' => 'Invalid IP address: :ip',
    'ip_already_exists' => 'IP :ip already exists in the allowed list.',
    'ip_added' => '✅ IP :ip has been added to the allowed list.',
    'ip_not_in_blocklist' => 'IP :ip is not in the blocked list.',
    'ip_removed' => '✅ IP :ip has been removed from the blocked list.',
];
