<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Cache Management',
    'title' => 'Cache Clear',
    'description' => 'Clear various application caches',
    'config_cache' => [
        'name' => 'Configuration Cache',
        'description' => 'Clear cached application configuration files',
    ],
    'route_cache' => [
        'name' => 'Route Cache',
        'description' => 'Clear cached routing information',
    ],
    'view_cache' => [
        'name' => 'View Cache',
        'description' => 'Clear compiled view files cache',
    ],
    'application_cache' => [
        'name' => 'Application Cache',
        'description' => 'Clear application cache data',
    ],
    'clear_button' => 'Clear',
    'clear_confirm' => 'Clear :name?',
    'clear_all_title' => 'Clear All Caches',
    'clear_all_description' => 'Clear all caches (configuration, route, view, application) at once.',
    'clear_all_warning' => 'This operation may temporarily slow down the application.',
    'clear_all_button' => 'Clear All Caches',
    'clear_all_confirm' => 'Clear all caches? This operation may temporarily reduce performance.',
    'info_title' => 'About Caches',
    'info_config' => 'Cache application configuration files for faster performance',
    'info_route' => 'Cache routing information for faster performance',
    'info_view' => 'Cache compiled Blade templates as PHP files',
    'info_application' => 'Cache various data used within the application',
    'success_config' => 'Configuration cache cleared',
    'success_route' => 'Route cache cleared',
    'success_view' => 'View cache cleared',
    'success_application' => 'Application cache cleared',
    'success_all' => 'All caches cleared',
    'error_invalid_type' => 'Invalid cache type',
    'error_general' => 'Error occurred while clearing cache: :error',
    'warning' => 'Warning:',
];
