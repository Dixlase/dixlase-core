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

use App\Enums\MenuVisibility;

/*
|--------------------------------------------------------------------------
| Admin Panel Mode Settings
|--------------------------------------------------------------------------
|
| Define display and operation level for each menu in
| Simple mode and Advanced mode.
|
| MenuVisibility:
|   Full (0)      = Display all, enable all features
|   Partial (1)   = Display partial features only, hidden parts are auto-configured
|   Hidden (2)    = Hide entire menu, auto-configured or disabled
|   ReadOnly (3)  = Display but status display only (read-only)
|   GuideOnly (4) = Display but navigation only (guide to separate page or mode switch for settings)
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default menu display settings for Simple mode
    |--------------------------------------------------------------------------
    |
    | Keys correspond to navigation configuration keys.
    | Nested child items are specified using dot notation: 'parent_key.child_key'
    |
    | In Advanced mode, all menus are displayed as Full (0)
    |
    */

    'simple_defaults' => [

        // Dashboard - Always display
        'dashboard' => MenuVisibility::Full,

        // Front page management
        'front' => MenuVisibility::Full,

        // Media management
        'media' => MenuVisibility::Partial,
        'media.index' => MenuVisibility::Full,
        'media.upload' => MenuVisibility::Full,
        'media.settings' => MenuVisibility::Hidden,

        // Profile settings
        'profile' => MenuVisibility::Full,

        // Member management
        'members' => MenuVisibility::Partial,
        'members.index' => MenuVisibility::Full,
        'members.create_edit' => MenuVisibility::Full,
        'members.roles' => MenuVisibility::Hidden,

        // General settings
        'settings' => MenuVisibility::Partial,

        // Global settings > Basic settings
        'settings.base' => MenuVisibility::Partial,
        'settings.base.index' => MenuVisibility::Full,
        'settings.base.site' => MenuVisibility::Full,
        'settings.base.admin' => MenuVisibility::Hidden,
        'settings.base.mail' => MenuVisibility::Full,
        'settings.base.maintenance' => MenuVisibility::Full,
        'settings.base.mode' => MenuVisibility::Full,
        // Content settings show advanced settings only (hidden in simple mode, runs with default values)
        'settings.base.content' => MenuVisibility::Hidden,

        // Global settings > Security settings
        'settings.security' => MenuVisibility::Partial,
        'settings.security.index' => MenuVisibility::Full,
        'settings.security.password' => MenuVisibility::Hidden,
        'settings.security.login' => MenuVisibility::Partial,
        'settings.security.two-fa' => MenuVisibility::Partial,
        'settings.security.notifications' => MenuVisibility::Hidden,
        'settings.security.captcha' => MenuVisibility::Full,
        'settings.security.session' => MenuVisibility::Hidden,
        'settings.security.csp' => MenuVisibility::Hidden,
        'settings.security.extensions' => MenuVisibility::Hidden,
        'settings.security.ip' => MenuVisibility::Hidden,
        'settings.security.integrity' => MenuVisibility::Hidden,
        'settings.security.environment' => MenuVisibility::Hidden,

        // Global settings > Theme management
        'settings.themes' => MenuVisibility::Full,

        // Global settings > Plugin management
        'settings.plugins' => MenuVisibility::Full,

        // Global settings > System
        'settings.systems' => MenuVisibility::Partial,
        'settings.systems.updates' => MenuVisibility::Full,
        'settings.systems.cache' => MenuVisibility::Full,
        'settings.systems.database' => MenuVisibility::Hidden,
        'settings.systems.api' => MenuVisibility::Hidden,
        'settings.systems.logs' => MenuVisibility::Full,
        'settings.systems.logs.files' => MenuVisibility::Hidden,
        'settings.systems.info' => MenuVisibility::Hidden,
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu item metadata
    |--------------------------------------------------------------------------
    |
    | Defines display name (translation key), icon,
    | and available display level options for each menu item
    |
    */

    'menu_items' => [
        'dashboard' => [
            'text_key' => 'admin/navigation.dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'allowed_visibilities' => [MenuVisibility::Full],
            'locked' => true,
        ],
        'front' => [
            'text_key' => 'admin/navigation.front.text',
            'icon' => 'fas fa-desktop',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
        ],
        'media' => [
            'text_key' => 'admin/navigation.media.text',
            'icon' => 'fas fa-photo-video',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
        ],
        'profile' => [
            'text_key' => 'admin/navigation.profile.text',
            'icon' => 'fas fa-id-badge',
            'allowed_visibilities' => [MenuVisibility::Full],
            'locked' => true,
        ],
        'members' => [
            'text_key' => 'admin/navigation.settings.members.text',
            'icon' => 'fas fa-users-cog',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden, MenuVisibility::ReadOnly],
        ],
        'settings' => [
            'text_key' => 'admin/navigation.settings.text',
            'icon' => 'fas fa-cogs',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial],
            'children' => [
                'base' => [
                    'text_key' => 'admin/navigation.settings.base.text',
                    'icon' => 'fas fa-gear',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial],
                ],
                'security' => [
                    'text_key' => 'admin/navigation.settings.security.text',
                    'icon' => 'fas fa-shield-alt',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden, MenuVisibility::ReadOnly, MenuVisibility::GuideOnly],
                ],
                'themes' => [
                    'text_key' => 'admin/navigation.settings.themes.text',
                    'icon' => 'fas fa-palette',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
                ],
                'plugins' => [
                    'text_key' => 'admin/navigation.settings.plugins.text',
                    'icon' => 'fas fa-puzzle-piece',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
                ],
                'systems' => [
                    'text_key' => 'admin/navigation.settings.systems.text',
                    'icon' => 'fas fa-server',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Hidden, MenuVisibility::ReadOnly, MenuVisibility::GuideOnly],
                ],
            ],
        ],
    ],

];
