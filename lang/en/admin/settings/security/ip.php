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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'admin_ip_help' => 'Enter IPv4 / IPv6 addresses or CIDR notation. One per line. Examples: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32',
    'front_access_control' => 'Front Page IP Access Control Settings',
    'enable_allowed_front_ips' => 'Allow access only from specific IP addresses',
    'allowed_front_ips' => 'Allowed IP Addresses',
    'allowed_front_ips_list' => 'Allowed IP Address List',
    'enable_blocked_front_ips' => 'Block specific IP addresses',
    'blocked_front_ips' => 'Blocked IP Addresses',
    'blocked_front_ips_list' => 'Blocked IP Address List',
    'front_ip_help' => 'Enter IPv4 / IPv6 addresses or CIDR notation. One per line. Examples: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32',
    'ip_list_placeholder' => '192.168.1.1
192.168.1.0/24
10.0.0.0/8',
    'settings_updated' => 'IP access control settings have been updated.',

    'detected_ip_label' => 'Your IP address as seen by the application',
    'detected_ip_hint' => 'To keep your own access, make sure this address is in the allow list before you enable it.',
    'proxy_warning_heading' => 'Reverse proxy detected, but TRUSTED_PROXIES is not configured',
    'proxy_warning_body' => 'The application sees every visitor as :proxy, so the IP allow/block lists cannot match real client addresses. Do not add :proxy itself to either list — adding it to the allow list would allow everyone, and adding it to the block list would block everyone.',
    'proxy_warning_howto' => 'Add the line above as an environment variable. Common locations: the Laravel `.env` file, the docker-compose `environment:` (or `env_file:`) section, the systemd unit `Environment=` directive, or Apache `SetEnv` — whichever your deployment uses. After saving, run `php artisan config:cache` (inside the application container if you use Docker), then reload this page.',
    'lockout_allowlist' => 'Your current IP address (:ip) is not in the allow list. Saving this would lock you out of the admin panel — add :ip to the list first.',
    'lockout_blocklist' => 'Your current IP address (:ip) is in the block list. Saving this would lock you out of the admin panel.',

    'static_ip_recommendation' => 'If you plan to enable an allow list (admin or front), a static (fixed) IP — or a known office or VPN range entered with CIDR notation — is strongly recommended. A dynamic IP can change without notice and lock you out.',

    'invalid_entries' => 'The following entries are not valid IPv4 / IPv6 addresses or CIDR ranges: :entries',
];
