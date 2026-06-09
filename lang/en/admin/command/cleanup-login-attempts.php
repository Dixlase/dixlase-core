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
    'days_zero_warning' => 'Days set to 0 - this will delete ALL login attempt records.',
    'confirm_delete_all' => 'Are you sure you want to delete ALL login attempt records? This action cannot be undone.',
    'operation_cancelled' => 'Operation cancelled.',
    'deleting_all' => 'Deleting all login attempt records...',
    'deleted_all_success' => 'Successfully deleted all :count login attempt records.',
    'no_records_found' => 'No login attempt records found to delete.',
    'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
    'cleaning_up' => 'Cleaning up login attempts older than :days days...',
    'deleted_old_success' => 'Successfully deleted :count old login attempt records.',
    'no_old_records_found' => 'No old login attempt records found to delete.',
];
