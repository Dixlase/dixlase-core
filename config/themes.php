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
    'theme_directory' => 'themes', // Theme directory
    'active_theme' => env('APP_THEME', 'DixlaseOnePage'), // Active theme
    'default_theme' => env('APP_THEME', 'DixlaseOnePage'), // Default theme
    'default_theme_slug' => env('DEFAULT_THEME_SLUG', 'dixlase-onepage'), // Default theme slug name
    'admin_theme' => 'admin', // Admin panel theme

    /*
     * Themes that the install wizard can fetch from GitHub when the user's
     * release ZIP does not bundle them. Add new entries here as more first-party
     * themes ship. Each entry:
     *   - directory : target folder under themes/
     *   - repository: GitHub owner/repo (used to query the latest release)
     *   - label     : display name for the install wizard UI
     */
    'downloadable' => [
        [
            'directory' => 'DixlaseOnePage',
            'repository' => 'Dixlase/theme-dixlase-onepage',
            'label' => 'Dixlase OnePage',
        ],
    ],
];
