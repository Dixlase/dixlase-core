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
    'database_title' => 'Database Settings',
    'database_header' => 'Database Configuration',
    'database_description' => 'Configure the database to be used by the system.',

    // Database Settings Related
    'database_connection_settings' => 'Database Connection Settings',
    'database_connection_details' => 'Database Connection Details',
    'data_preservation_settings' => 'Data Preservation Settings',
    'database_preservation_options' => 'Database Preservation Options',
    'database_connection_test' => 'Database Connection Test',

    'db_connection' => 'Database Type',
    'db_host' => 'Database Host',
    'db_port' => 'Database Port',
    'db_database' => 'Database Name',
    'db_username' => 'Database Username',
    'db_password' => 'Database Password',
    'db_password_required' => 'Database password is required.',

    'preserve_database' => 'Do not reset database',
    'preserve_database_help' => 'If checked, existing data will be preserved and only necessary updates will be applied. If unchecked, all existing data will be deleted during installation.',

    'test_db_connection' => 'Test Connection',
    'db_connection_success' => 'Database connection successful!',
    'db_connection_error' => 'Failed to connect to database: :error',
    'db_test_required' => '⚠️ Please run database connection test before proceeding to the next screen.',
    'db_test_success' => '✅ Database connection successful! You may proceed to the next screen!',
];
