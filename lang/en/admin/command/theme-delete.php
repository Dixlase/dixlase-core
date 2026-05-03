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

    'description' => 'Delete theme files and directory (theme must be uninstalled first)',
    'theme_directory_prompt' => 'The directory name of the theme to delete',
    'force_option' => 'Force delete without confirmation',
    'not_found' => 'Theme directory \':directory\' not found.',
    'still_installed' => 'Theme \':themeName\' is still installed.',
    'still_enabled' => 'Theme \':themeName\' is still enabled.',
    'uninstall_first' => 'Please uninstall the theme first using `dls:theme:uninstall` command before deleting.',
    'disable_first' => 'Please switch to a different theme before deleting.',
    'confirm' => 'Are you sure you want to delete theme directory \':directory\' and all its files? This action cannot be undone.',
    'cancelled' => 'Deletion cancelled.',
    'deleted' => 'Deleted theme directory: :path',
    'failed' => 'Failed to delete theme directory: :error',
    'database_removed' => 'Removed theme \':themeName\' from database.',
    'completed' => 'Theme \':directory\' deletion completed.',
];
