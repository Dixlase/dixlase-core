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
    'confirm_title' => 'Confirm Installation Settings',
    'confirm_header' => 'Installation Confirmation',
    'confirm_message' => 'Please review your settings before finalizing the installation.',
    'confirm_description' => 'The installation will proceed with the above settings.<br>Are you sure?',
    'confirm_button' => 'Install',
    'installing' => 'Installing...',
    'installing_description' => 'Please do not close this page. This may take a moment.',
    'installing_description_line1' => 'Please do not close this page.',
    'installing_description_line2' => 'This may take a moment.',

    // Confirmation Page Related
    'settings_review' => 'Settings Review',
    'basic_settings' => 'Basic Settings',
    'app_settings' => 'Application Settings',
    'datasite_settings' => 'Database Settings',
    'mail_settings' => 'Mail Settings',
    'security_settings' => 'Security Settings',

    // Site Information
    'site_name' => 'Site Name',
    'admin_email' => 'Admin Email',
    'admin_password' => 'Admin Password',

    // IP Restrictions
    'ip_restrictions' => 'IP Restrictions',
    'allowed_admin_ips' => 'Allowed Admin Panel IPs',
    'blocked_admin_ips' => 'Blocked Admin Panel IPs',
    'allowed_front_ips' => 'Allowed Front IPs',
    'blocked_front_ips' => 'Blocked Front IPs',

    // Database Information
    'db_connection' => 'Database Connection',
    'db_host' => 'Database Host',
    'db_port' => 'Database Port',
    'db_name' => 'Database Name',
    'db_user' => 'Database Username',
    'db_password' => 'Database Password',

    // Core integrity pre-flight
    'integrity' => [
        'title' => 'Core integrity',
        'status' => [
            'genuine' => 'Genuine',
            'modified' => 'Modified',
            'unsigned' => 'Unsigned (development build)',
            'pending_verification' => 'Verification pending',
            'invalid' => 'Invalid signature',
            'error' => 'Verification error',
        ],
        'desc' => [
            'genuine' => 'This is a genuine, unmodified Dixlase release.',
            'modified' => 'This is an official release with local modifications. You can continue, but note the changes below.',
            'unsigned' => 'No signed manifest is present (development build). You can continue.',
            'pending_verification' => 'The trusted key is not available yet. You can continue; verify later from the admin panel.',
            'invalid' => 'The core signature did not verify — the files may have been tampered with. Installation is blocked.',
            'error' => 'Integrity could not be verified. You may continue at your own risk.',
        ],
        'changed' => ':count file(s) differ from the signed manifest.',
        'blocked' => 'Installation was blocked because the core signature is invalid (possible tampering). Re-download a genuine release and try again.',
    ],
];
