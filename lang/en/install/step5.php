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
    'security_title' => 'Security Settings',
    'security_header' => 'Security Settings (Optional)',
    'security_description' => 'Configure admin panel URL and IP restrictions.<br>IP restrictions can be configured after installation.',
    
    'site_url' => 'Front Page URL',
    'admin_url' => 'Admin Panel URL',
    'admin_url_security_note' => 'In production environments, it is recommended to set the admin panel URL to something other than "admin" that is difficult to guess.',
    'force_ssl' => 'Force SSL (HTTPS)',
    
    // IP Restrictions
    'ip_restrictions' => 'IP Address Restrictions',
    'enable_allowed_admin_ips' => 'Allow only specific IP addresses to access admin panel',
    'enable_blocked_admin_ips' => 'Block specific IP addresses from accessing admin panel',
    'enable_allowed_front_ips' => 'Allow only specific IP addresses to access front',
    'enable_blocked_front_ips' => 'Block specific IP addresses from accessing front',
    'ip_note' => 'Separate multiple IPs with line breaks.',
    'ip_address_format_instruction' => 'Enter one IP address per line. Example:',
    'admin_panel_ip_restrictions' => 'Admin Panel IP Restrictions',
    'front_panel_ip_restrictions' => 'Front Panel IP Restrictions',
];
