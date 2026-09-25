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
    'welcome' => 'Welcome to Dixlase Installation',
    'description' => 'Before proceeding, please check if your server meets the requirements.',
    'server_requirements' => 'Server Requirements',
    'required_section' => 'Required',
    'recommended_section' => 'Recommended / Optional',
    'category' => [
        'extensions' => 'Extensions',
        'permissions' => 'Permissions',
        'other' => 'Other',
    ],
    'permissions' => [
        'storage' => 'Storage directory writable',
        'cache' => 'Bootstrap cache directory writable',
        'writable_required' => 'writable required',
    ],
    'php_settings' => [
        'required' => 'required',
        'recommended_min' => 'Recommended: :value or more',
        'unlimited' => 'unlimited',
    ],
    'theme_check' => [
        'label' => 'Theme',
        'not_found' => 'No theme found in themes/ directory',
    ],
    'theme_download' => [
        'heading' => 'Download a Theme',
        'description' => 'No theme is bundled with this release. Download the official theme below to continue.',
        'button' => 'Download :label',
        'progress_title' => 'Downloading theme...',
        'progress_message' => 'Please do not close this page.<br>This may take a moment.',
        'success' => 'Theme downloaded successfully.',
        'failed' => 'Theme download failed: :error',
        'invalid' => 'Requested theme is not registered as downloadable.',
    ],
    'start_button' => 'Start Installation',
];
