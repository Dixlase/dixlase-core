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
    'heading' => 'Environment Settings',
    'title' => 'Environment Settings',
    'description' => 'Configure the application environment and debug mode. These settings are directly reflected in the .env file.',

    'app_env' => 'Environment',
    'app_env_help' => 'Select the operating environment. In production, strict error handling and security settings are applied.',

    'env_options' => [
        'local' => 'Local (Development)',
        'staging' => 'Staging (Testing)',
        'production' => 'Production',
    ],

    'env_descriptions' => [
        'local' => 'Development environment for developers. Detailed error information is displayed and optimized for debugging. Do not use on production servers.',
        'staging' => 'Testing environment similar to production. Used for testing before production release. Some debugging features are available.',
        'production' => 'Production environment used by actual users. Security is prioritized and error details are hidden.',
    ],

    'app_debug' => 'Debug Mode',
    'app_debug_help' => 'When debug mode is enabled, detailed error information and stack traces are displayed. <strong class="text-red-600 dark:text-red-400">Always disable this in production.</strong>',

    'debug_enabled_warning' => 'Debug mode is enabled. Detailed error information will be displayed, which poses a security risk. It is strongly recommended to disable this in production.',
    'production_debug_warning' => 'Debug mode cannot be enabled in production environment. Enabling debug mode may expose sensitive information.',

    'current_status' => 'Current Status',
    'current_env' => 'Current Environment',
    'current_debug' => 'Debug Mode',

    'settings_updated' => 'Environment settings have been updated. Please reload the page to fully apply the changes.',
    'update_failed' => 'Failed to update environment settings. Please check the write permissions for the .env file.',

    'save_confirmation_title' => 'Change Environment Settings',
    'save_confirmation_message' => 'You are about to change the environment settings. This change will affect the entire application. Do you want to continue?',
];
