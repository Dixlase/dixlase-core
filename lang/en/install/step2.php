<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
    'environment_title' => 'Environment Settings',
    'environment_header' => 'Application Environment Settings',
    'environment_description' => 'Select the environment in which the application will run and configure the URL.',

    // Environment Settings Related
    'environment_settings' => 'Environment Settings',
    'application_environment' => 'Application Environment',
    'url_settings' => 'URL Settings',
    'application_url_configuration' => 'Application URL Configuration',
    'admin_url_configuration' => 'Admin URL Configuration',
    'admin_panel_url' => 'Admin Panel URL',
    'timezone_configuration' => 'Timezone Configuration',
    'application_timezone' => 'Application Timezone',

    'app_env' => 'Application Environment',
    'app_env_options' => [
        'local' => 'Local',
        'staging' => 'Staging',
        'production' => 'Production',
    ],

    'app_debug' => 'Debug Mode',
    'enable_debug' => 'Enable debug mode',
    'app_debug_note' => 'Debug mode cannot be enabled in production environment.',

    'app_url' => 'Application URL',
    'app_url_note' => 'Automatically set based on current host. Change if necessary.',

    // Admin URL
    'admin_url' => 'Admin Panel URL',
    'admin_url_prefix' => 'URL Prefix',
    'admin_url_suffix' => 'URL Suffix',
    'admin_url_security_note' => 'Choose a prefix and enter a suffix. The suffix must be at least 4 characters (lowercase letters and numbers only).',
    'admin_url_auto_generated' => 'The admin URL is automatically generated with a random prefix and suffix for security.',

    // SSL Settings
    'force_ssl' => 'Force SSL (HTTPS)',

    // Timezone
    'timezone' => [
        'label' => 'Timezone',
    ],
    'timezone_note' => 'Select the default timezone for the application.',
];
