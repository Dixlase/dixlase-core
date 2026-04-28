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
    'heading' => 'IP Access Control',
    'title' => 'IP Access Control Settings',
    'description' => 'Configure IP address-based access control for the system.',
    'admin_access_control' => 'Admin Panel IP Access Control Settings',
    'admin_url' => 'Admin URL',
    'enable_allowed_admin_ips' => 'Allow access only from specific IP addresses',
    'allowed_admin_ips' => 'Allowed IP Addresses',
    'allowed_admin_ips_list' => 'Allowed IP Address List',
    'enable_blocked_admin_ips' => 'Block specific IP addresses',
    'blocked_admin_ips' => 'Blocked IP Addresses',
    'blocked_admin_ips_list' => 'Blocked IP Address List',
    'admin_ip_help' => 'Enter IP addresses or CIDR notation. One per line. Example: 192.168.1.1 or 192.168.1.0/24',
    'front_access_control' => 'Front Page IP Access Control Settings',
    'enable_allowed_front_ips' => 'Allow access only from specific IP addresses',
    'allowed_front_ips' => 'Allowed IP Addresses',
    'allowed_front_ips_list' => 'Allowed IP Address List',
    'enable_blocked_front_ips' => 'Block specific IP addresses',
    'blocked_front_ips' => 'Blocked IP Addresses',
    'blocked_front_ips_list' => 'Blocked IP Address List',
    'front_ip_help' => 'Enter IP addresses or CIDR notation. One per line. Example: 192.168.1.1 or 192.168.1.0/24',
    'ip_list_placeholder' => '192.168.1.1
192.168.1.0/24
10.0.0.0/8',
    'settings_updated' => 'IP access control settings have been updated.',
];
