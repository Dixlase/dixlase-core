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

        'description' => 'Switch to a different theme (select theme to enable)',
        'theme_name_prompt' => 'The name of the theme to switch to',
        'no_installed_themes' => 'No installed themes available.',
        'theme_not_found' => 'Theme \':themeName\' not found.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'install_first' => 'Please install the theme first using `dls:theme:install` command before switching.',
        'disabled' => 'Disabled previous theme: :themeName',
        'already_enabled' => 'Theme \':themeName\' is already enabled.',
        'switched' => 'Switched to theme: :themeName',
        'select_prompt' => 'Select a theme to switch to',
        'current_marker' => '(Currently Active)',
        'selection_error' => 'Failed to select theme.',
        'symlink_warning' => 'Failed to update symlink, but theme switch completed.',
];
