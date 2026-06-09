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
    'heading' => 'Plugin Details',
    'description' => 'View detailed information and scan results for the plugin.',
    'back_to_list' => 'Back to Plugin List',
    'back_to_add' => 'Back to Add Plugin',
    'update_available' => 'available',
    'online_badge' => 'Available Online',
    'repository' => 'Repository',
    'last_updated' => 'Last Updated',

    // Metadata labels
    'author' => 'Author',
    'license' => 'License',
    'slug' => 'Slug',
    'package_name' => 'Package Name',
    'namespace' => 'Namespace',
    'directory' => 'Directory',
    'email' => 'Email',
    'url' => 'URL',

    // Sections
    'sections' => [
        'description' => 'Description',
        'details' => 'Detailed Information',
        'scan_result' => 'Scan Result',
        'scan_details' => 'Scan Details',
        'csp_compatibility' => 'CSP Mode Compatibility',
        'preset_compatibility' => 'Extension Compatibility',
    ],

    // Scan
    'scan' => [
        'signature' => 'Signature',
        'permission' => 'Permission',
        'csp' => 'CSP',
        'operation' => 'Operation',
        'issues' => 'Issues Found',
        'scan' => 'Scan',
        'rescan' => 'Rescan',
        'scanning' => 'Scanning...',
        'not_scanned_message' => 'This plugin has not been scanned yet. Run a scan to check security and permissions.',
    ],

    'last_scanned_at' => 'Last scanned: :date',

    // Actions
    'actions' => [
        'install' => 'Install',
        'uninstall' => 'Uninstall',
        'enable' => 'Enable',
        'disable' => 'Disable',
        'delete' => 'Delete',
        'settings' => 'Settings',
    ],
];
