<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Enums\MemberRole;

/**
 * Default permission settings for Core features
 *
 * Defines default permissions for each menu/feature.
 * Only when changed in the admin panel, differences are saved to the role_permission_overrides table.
 *
 * access_roles: Edit permission (write) - users with this permission level or higher can edit
 * view_roles: View permission (read) - users with this permission level or higher can view
 *
 * Permission values (MemberRole enum):
 * - SUPER_ADMIN = 10 (super administrator only)
 * - ADMIN = 9 (administrator or higher)
 * - EDITOR = 8 (editor or higher)
 * - CONTRIBUTOR = 6 (contributor or higher)
 * - GUEST = 1 (everyone)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Permissions for Core Features
    |--------------------------------------------------------------------------
    |
    | Defined with the same hierarchical structure as the nav structure in config/admin.php
    | To display in accordion format on the permission settings screen
    |
    */
    'permissions' => [
        // Dashboard (dashboard) is excluded from permission settings
        // View-only page, viewable even by guests (fixed in AdminHelper)

        // Front page management
        'front' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'edit' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'settings' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
            ],
        ],

        // Media management
        'media' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::CONTRIBUTOR->value,
                    'view_roles' => MemberRole::CONTRIBUTOR->value,
                ],
                'upload' => [
                    'access_roles' => MemberRole::CONTRIBUTOR->value,
                    'view_roles' => MemberRole::CONTRIBUTOR->value,
                ],
                'settings' => [
                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                ],
            ],
        ],

        // Profile is excluded from permission settings
        // Everyone can read and write their own settings (fixed in AdminHelper)

        // Member management
        'members' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
                'create_edit' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
                'roles' => [
                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                ],
            ],
        ],

        // Global settings
        'settings' => [
            'children' => [
                // Basic settings
                'base' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'site' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'admin' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'mail' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'maintenance' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
                ],

                // Security settings
                'security' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'password' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'login' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'authentication' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'captcha' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'session' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'notifications' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'csp' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'extensions' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'ip' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'integrity' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'environment' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // Theme management
                'themes' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'add' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        // The active theme's own settings page (e.g. DixlaseOnePage
                        // colours / hero) is contributed as a theme nav item under
                        // this key. Without a default here, PermissionRegistry hard-
                        // denies every non-super_admin (it returns before consulting
                        // role_permission_overrides), so the menu is hidden and the
                        // route 403s for ADMIN — contradicting Permission::THEMES_SETTINGS
                        // (ADMIN) and the links in the admin bar / getting-started.
                        'settings' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
                ],

                // Plugin management
                'plugins' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'add' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // System management
                'systems' => [
                    'children' => [
                        'cache' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                        'database' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'backup' => [
                            'children' => [
                                'index' => [
                                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                                ],
                                'restores' => [
                                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                                ],
                                'settings' => [
                                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                                ],
                            ],
                        ],
                        'api' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'logs' => [
                            'children' => [
                                'audit' => [
                                    'access_roles' => MemberRole::ADMIN->value,
                                    'view_roles' => MemberRole::ADMIN->value,
                                ],
                                'files' => [
                                    'access_roles' => MemberRole::ADMIN->value,
                                    'view_roles' => MemberRole::ADMIN->value,
                                ],
                            ],
                        ],
                        'info' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
