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
    'description' => 'Uninstall the plugin and remove from database (files are preserved).',
    'not_found' => 'Plugin \':pluginName\' not found.',
    'still_enabled' => 'Plugin \':pluginName\' is still enabled.',
    'disable_first' => 'Please disable the plugin first using `plugin:disable` command before uninstalling.',
    'force_disabling' => 'Force disabling plugin \':pluginName\' due to --force option.',
    'confirm' => 'Are you sure you want to uninstall plugin \':pluginName\'? This will remove plugin information from the database.',
    'cancelled' => 'Uninstallation cancelled.',
    'rollback_running' => 'Running migrations rollback...',
    'rollback_confirm' => 'Do you want to delete database tables related to plugin \':pluginName\'?',
    'rollback_skipped' => 'Database rollback was skipped.',
    'files_preserved' => 'Plugin files and directories have been preserved.',
    'database_removed' => 'Plugin \':pluginName\' has been removed from the database.',
    'completed' => 'Plugin \':pluginName\' has been uninstalled successfully.',
    'delete_hint' => 'To delete files, run `php artisan plugin:delete <directory>` command.',
    'autoload_pending' => 'Plugin :pluginName is uninstalled, but the autoloader could not be regenerated, so its autoload files still load. Run `php artisan dls:plugin:sync-autoload` to finish.',
];
