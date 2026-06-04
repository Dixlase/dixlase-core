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

    'dashboard' => [
        'text' => 'admin/navigation.dashboard',
        'route' => 'admin.dashboard',
        'icon' => 'fas fa-fw fa-tachometer-alt',
    ],
    'front' => [
        'text' => 'admin/navigation.front.text',
        'icon' => 'fas fa-fw fa-desktop',
        'children' => [
            'index' => [
                'text' => 'admin/navigation.front.index',
                'route' => 'admin.front.index',
                'icon' => 'fas fa-fw fa-home',
            ],
            'edit' => [
                'text' => 'admin/navigation.front.edit',
                'route' => 'admin.front.edit',
                'icon' => 'fas fa-fw fa-edit',
            ],
            'settings' => [
                'text' => 'admin/navigation.front.settings',
                'route' => 'admin.front.settings',
                'icon' => 'fas fa-fw fa-sliders-h',
            ],
        ],
    ],
    'media' => [
        'text' => 'admin/navigation.media.text',
        'icon' => 'fas fa-fw fa-photo-video',
        'children' => [
            'index' => [
                'text' => 'admin/navigation.media.index',
                'route' => 'admin.media.index',
                'icon' => 'fas fa-fw fa-images',
            ],
            'upload' => [
                'text' => 'admin/navigation.media.upload',
                'route' => 'admin.media.upload',
                'icon' => 'fas fa-fw fa-upload',
            ],
            'settings' => [
                'text' => 'admin/navigation.media.settings',
                'route' => 'admin.media.settings',
                'icon' => 'fas fa-fw fa-cogs',
            ],
        ],
    ],
    'profile' => [
        'text' => 'admin/navigation.profile.text',
        'icon' => 'fas fa-fw fa-id-badge',
        'children' => [
            'index' => [
                'text' => 'admin/navigation.profile.index',
                'route' => 'admin.profile',
                'icon' => 'fas fa-fw fa-home',
            ],
            'basic' => [
                'text' => 'admin/navigation.profile.basic',
                'route' => 'admin.profile.basic',
                'icon' => 'fas fa-fw fa-user',
            ],
            'password' => [
                'text' => 'admin/navigation.profile.password',
                'route' => 'admin.profile.password',
                'icon' => 'fas fa-fw fa-key',
            ],
            'appearance' => [
                'text' => 'admin/navigation.profile.appearance',
                'route' => 'admin.profile.appearance',
                'icon' => 'fas fa-fw fa-palette',
            ],
            'notifications' => [
                'text' => 'admin/navigation.profile.notifications',
                'route' => 'admin.profile.notifications',
                'icon' => 'fas fa-fw fa-bell',
            ],
            'two_fa' => [
                'text' => 'admin/navigation.profile.two_fa',
                'route' => 'admin.profile.two-fa',
                'icon' => 'fas fa-fw fa-shield-alt',
            ],
            'two_fa_management' => [
                'text' => 'admin/navigation.profile.two_fa_management',
                'route' => 'admin.profile.two-fa-management',
                'icon' => 'fas fa-fw fa-fingerprint',
            ],
        ],
    ],
    'members' => [
        'text' => 'admin/navigation.settings.members.text',
        'icon' => 'fas fa-fw fa-users-cog',
        'children' => [
            'index' => [
                'text' => 'admin/navigation.settings.members.index',
                'route' => 'admin.members.index',
                'icon' => 'fas fa-fw fa-users',
            ],
            'create_edit' => [
                'text' => 'admin/navigation.settings.members.create',
                'route' => 'admin.members.create',
                'icon' => 'fas fa-fw fa-user-plus',
            ],
            'roles' => [
                'text' => 'admin/navigation.settings.members.roles',
                'route' => 'admin.members.roles',
                'icon' => 'fas fa-fw fa-user-shield',
            ],
        ],
    ],
    'settings' => [
        'text' => 'admin/navigation.settings.text',
        'icon' => 'fas fa-fw fa-cogs',
        'children' => [
            'base' => [
                'text' => 'admin/navigation.settings.base.text',
                'icon' => 'fas fa-fw fa-gear',
                'children' => [
                    'index' => [
                        'text' => 'admin/navigation.settings.base.index',
                        'route' => 'admin.settings.base.index',
                        'icon' => 'fas fa-fw fa-tachometer-alt',
                    ],
                    'site' => [
                        'text' => 'admin/navigation.settings.base.site',
                        'route' => 'admin.settings.base.site',
                        'icon' => 'fas fa-fw fa-globe',
                    ],
                    'admin' => [
                        'text' => 'admin/navigation.settings.base.admin',
                        'route' => 'admin.settings.base.admin',
                        'icon' => 'fas fa-fw fa-cog',
                    ],
                    'mail' => [
                        'text' => 'admin/navigation.settings.base.mail',
                        'route' => 'admin.settings.base.mail',
                        'icon' => 'fas fa-fw fa-envelope',
                    ],
                    'maintenance' => [
                        'text' => 'admin/navigation.settings.base.maintenance',
                        'route' => 'admin.settings.base.maintenance',
                        'icon' => 'fas fa-fw fa-tools',
                    ],
                    'editor' => [
                        'text' => 'admin/navigation.settings.base.editor',
                        'route' => 'admin.settings.base.editor',
                        'icon' => 'fas fa-fw fa-pen-nib',
                    ],
                    'content' => [
                        'text' => 'admin/navigation.settings.base.content',
                        'route' => 'admin.settings.base.content',
                        'icon' => 'fas fa-fw fa-file-lines',
                    ],
                    'mode' => [
                        'text' => 'admin/navigation.settings.base.mode',
                        'route' => 'admin.settings.base.mode',
                        'icon' => 'fas fa-fw fa-sliders-h',
                    ],
                ],
            ],
            'security' => [
                'text' => 'admin/navigation.settings.security.text',
                'icon' => 'fas fa-fw fa-shield-alt',
                'children' => [
                    'index' => [
                        'text' => 'admin/navigation.settings.security.index',
                        'route' => 'admin.settings.security.index',
                        'icon' => 'fas fa-fw fa-tachometer-alt',
                    ],
                    'password' => [
                        'text' => 'admin/navigation.settings.security.password',
                        'route' => 'admin.settings.security.password',
                        'icon' => 'fas fa-fw fa-key',
                    ],
                    'login' => [
                        'text' => 'admin/navigation.settings.security.login_attempt',
                        'route' => 'admin.settings.security.login',
                        'icon' => 'fas fa-fw fa-sign-in-alt',
                    ],
                    'two-fa' => [
                        'text' => 'admin/navigation.settings.security.two-fa',
                        'route' => 'admin.settings.security.two-fa',
                        'icon' => 'fas fa-fw fa-user-shield',
                    ],
                    'notifications' => [
                        'text' => 'admin/navigation.settings.security.notifications',
                        'route' => 'admin.settings.security.notifications',
                        'icon' => 'fas fa-fw fa-bell',
                    ],
                    'captcha' => [
                        'text' => 'admin/navigation.settings.security.captcha',
                        'route' => 'admin.settings.security.captcha',
                        'icon' => 'fas fa-fw fa-robot',
                    ],
                    'session' => [
                        'text' => 'admin/navigation.settings.security.session',
                        'route' => 'admin.settings.security.session',
                        'icon' => 'fas fa-fw fa-clock',
                    ],
                    'csp' => [
                        'text' => 'admin/navigation.settings.security.csp',
                        'route' => 'admin.settings.security.csp',
                        'icon' => 'fas fa-fw fa-code',
                    ],
                    'extensions' => [
                        'text' => 'admin/navigation.settings.security.extensions',
                        'route' => 'admin.settings.security.extensions',
                        'icon' => 'fas fa-fw fa-puzzle-piece',
                    ],
                    'ip' => [
                        'text' => 'admin/navigation.settings.security.ip',
                        'route' => 'admin.settings.security.ip',
                        'icon' => 'fas fa-fw fa-network-wired',
                    ],
                    'integrity' => [
                        'text' => 'admin/navigation.settings.security.integrity',
                        'route' => 'admin.settings.security.integrity',
                        'icon' => 'fas fa-fw fa-file-shield',
                    ],
                    'environment' => [
                        'text' => 'admin/navigation.settings.security.environment',
                        'route' => 'admin.settings.security.environment',
                        'icon' => 'fas fa-fw fa-cog',
                    ],
                ],
            ],
            'themes' => [
                'text' => 'admin/navigation.settings.themes.text',
                'icon' => 'fas fa-fw fa-palette',
                'children' => [
                    'index' => [
                        'text' => 'admin/navigation.settings.themes.index',
                        'route' => 'admin.settings.themes.index',
                        'icon' => 'fas fa-fw fa-brush',
                    ],
                    'add' => [
                        'text' => 'admin/navigation.settings.themes.add',
                        'route' => 'admin.settings.themes.add',
                        'icon' => 'fas fa-fw fa-plus',
                    ],
                ],
            ],
            'plugins' => [
                'text' => 'admin/navigation.settings.plugins.text',
                'icon' => 'fas fa-fw fa-puzzle-piece',
                'children' => [
                    'index' => [
                        'text' => 'admin/navigation.settings.plugins.index',
                        'route' => 'admin.settings.plugins.index',
                        'icon' => 'fas fa-fw fa-puzzle-piece',
                    ],
                    'add' => [
                        'text' => 'admin/navigation.settings.plugins.add',
                        'route' => 'admin.settings.plugins.add',
                        'icon' => 'fas fa-fw fa-plus',
                    ],
                ],
            ],
            'systems' => [
                'text' => 'admin/navigation.settings.systems.text',
                'icon' => 'fas fa-fw fa-server',
                'children' => [
                    'updates' => [
                        'text' => 'admin/navigation.settings.systems.updates',
                        'route' => 'admin.settings.systems.updates.index',
                        'icon' => 'fas fa-fw fa-cloud-arrow-down',
                    ],
                    'cache' => [
                        'text' => 'admin/navigation.settings.systems.cache',
                        'route' => 'admin.settings.systems.cache',
                        'icon' => 'fas fa-fw fa-trash-alt',
                    ],
                    'database' => [
                        'text' => 'admin/navigation.settings.systems.database',
                        'route' => 'admin.settings.systems.database',
                        'icon' => 'fas fa-fw fa-database',
                    ],
                    'backup' => [
                        'text' => 'admin/navigation.settings.systems.backup.text',
                        'icon' => 'fas fa-fw fa-archive',
                        'children' => [
                            'index' => [
                                'text' => 'admin/navigation.settings.systems.backup.index',
                                'route' => 'admin.settings.systems.backup.index',
                                'icon' => 'fas fa-fw fa-box-archive',
                            ],
                            'restores' => [
                                'text' => 'admin/navigation.settings.systems.backup.restores',
                                'route' => 'admin.settings.systems.backup.restores',
                                'icon' => 'fas fa-fw fa-clock-rotate-left',
                            ],
                            'settings' => [
                                'text' => 'admin/navigation.settings.systems.backup.settings',
                                'route' => 'admin.settings.systems.backup.settings',
                                'icon' => 'fas fa-fw fa-sliders',
                            ],
                        ],
                    ],
                    'api' => [
                        'text' => 'admin/navigation.settings.systems.api',
                        'route' => 'admin.settings.systems.api',
                        'icon' => 'fas fa-fw fa-key',
                    ],
                    'logs' => [
                        'text' => 'admin/navigation.settings.systems.logs.text',
                        'icon' => 'fas fa-fw fa-file-alt',
                        'children' => [
                            'audit' => [
                                'text' => 'admin/navigation.settings.systems.logs.audit',
                                'route' => 'admin.settings.systems.logs.index',
                                'icon' => 'fas fa-fw fa-clipboard-list',
                            ],
                            'files' => [
                                'text' => 'admin/navigation.settings.systems.logs.files',
                                'route' => 'admin.settings.systems.logs.files',
                                'icon' => 'fas fa-fw fa-scroll',
                            ],
                        ],
                    ],
                    'info' => [
                        'text' => 'admin/navigation.settings.systems.info',
                        'route' => 'admin.settings.systems.info',
                        'icon' => 'fas fa-fw fa-info-circle',
                    ],
                    'integrity' => [
                        'text' => 'admin/navigation.settings.systems.integrity',
                        'route' => 'admin.settings.systems.integrity',
                        'icon' => 'fas fa-fw fa-shield-alt',
                    ],
                ],
            ],
        ],
    ],

];
