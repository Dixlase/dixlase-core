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
    'type_required' => '--type option is required',
    'confirm_delete_all' => 'Are you sure you want to delete all records for :type?',
    'operation_cancelled' => 'Operation cancelled',
    'force_required' => '--force flag is required when running from web interface',
    'invalid_days' => 'Days must be an integer greater than or equal to 0',
    'type_not_found' => 'Cleanup type :type not found',
    'deleting_all' => 'Deleting all records for :type...',
    'cleaning_up' => 'Cleaning up :type records older than :days days...',
    'deleted_success' => 'Successfully deleted :count records',
    'cleanup_failed' => 'Cleanup failed: :error',
    'cleaning_up_all' => 'Cleaning up all tables for records older than :days days...',
    'deleted_all_success' => 'Successfully deleted :count records in total',
    'available_types' => 'Available cleanup types:',
    'core_tables' => '[Core Tables]',
    'plugin_tables' => '[Plugin Tables]',
    'no_plugin_tables' => 'No plugin cleanup tables available',
    'usage_examples' => 'Usage examples:',
    'table_type' => 'Table Type',
    'table_count' => 'Deleted Count',
];
