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
    'clear_all_view_rebuild_note' => '* The view cache is rebuilt immediately after clearing (so the next page navigation is not interrupted).',
    'rebuild' => 'Rebuild',
    'rebuild_confirm' => 'Rebuild :name?',
    'rebuild_all_title' => 'Rebuild All Caches',
    'rebuild_all_description' => 'Rebuild configuration, route, and view caches at once.',
    'rebuild_all_warning' => 'Optimization for production. A few seconds of delay may occur during rebuild.',
    'rebuild_all_button' => 'Rebuild All Caches',
    'rebuild_all_confirm' => 'Rebuild all caches?',
    'rebuild_not_supported' => '* Application cache cannot be rebuilt by Laravel design (it is populated lazily on demand)',
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
    'success_rebuild_config' => 'Configuration cache rebuilt',
    'success_rebuild_route' => 'Route cache rebuilt',
    'success_rebuild_view' => 'View cache rebuilt',
    'success_rebuild_all' => 'All caches rebuilt',
    'error_invalid_type' => 'Invalid cache type',
    'error_general' => 'Error occurred while clearing cache: :error',
    'warning' => 'Warning:',
];
