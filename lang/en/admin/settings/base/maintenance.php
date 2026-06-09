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
    'heading' => 'Maintenance Settings',
    'description' => 'Enable maintenance mode and customize the display message for your site.',
    'maintenance_settings' => 'Maintenance Mode Settings',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_mode_help' => 'When maintenance mode is enabled, a maintenance message will be displayed on the front page. Administrators can always access the site.',
    'maintenance_message' => 'Maintenance Message',
    'maintenance_message_help' => '*Displayed on the front page when maintenance mode is enabled.',
    'default_message' => 'Currently under maintenance. Please wait for a while.',

    // Release method
    'release_method' => 'Release Method',
    'manual_release' => 'Manual Release',
    'manual_release_help' => 'Maintenance mode will continue until an administrator manually turns it OFF.',
    'auto_release' => 'Auto Release',
    'auto_release_help' => 'Maintenance mode will be automatically released at the specified date and time.',

    // Schedule settings
    'schedule_settings' => 'Schedule Settings',
    'start_at' => 'Start Date/Time (Optional)',
    'start_at_help' => 'If you specify a future date/time, maintenance mode will start at that time. Leave blank for immediate start.',
    'release_at' => 'End Date/Time (Required for auto release)',
    'release_at_help' => 'If auto release is selected, maintenance mode will be automatically released at this date/time.',

    // Preview
    'preview_button' => 'Preview Display',
    'preview_help' => 'You can preview the maintenance page before saving.',

    'settings_updated' => 'Maintenance settings have been updated.',
];
