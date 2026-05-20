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
    'heading' => 'Theme Details',
    'description' => 'View the theme\'s details and scan results.',
    'back_to_list' => 'Back to theme list',
    'update_available' => 'available',

    // Metadata labels
    'author' => 'Author',
    'license' => 'License',
    'email' => 'Email',
    'url' => 'URL',
    'namespace' => 'Namespace',
    'slug' => 'Slug',
    'package_name' => 'Package name',
    'directory' => 'Directory',

    'last_scanned_at' => 'Last scanned at: :date',

    'sections' => [
        'scan_result' => 'Scan result',
        'csp_compatibility' => 'CSP Mode Compatibility',
        'preset_compatibility' => 'Security Preset Compatibility',
    ],

    'scan' => [
        'scan' => 'Scan',
        'rescan' => 'Rescan',
        'not_scanned_message' => 'Not scanned yet. Press the Scan button to check the theme\'s permissions, signature, and compatibility.',
        'issues' => 'Detected issues',
    ],
];
