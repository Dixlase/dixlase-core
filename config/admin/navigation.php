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
    'text' => 'admin/nav.dashboard',
    'route' => 'admin.dashboard',
    'icon' => 'fas fa-fw fa-tachometer-alt',
  ],
  'front' => [
    'text' => 'admin/nav.front.text',
    'icon' => 'fas fa-fw fa-desktop',
    'children' => [
        'index' => [
          'text' => 'admin/nav.front.index',
          'route' => 'admin.front.index',
          'icon' => 'fas fa-fw fa-home',
        ],
        'edit' => [
          'text' => 'admin/nav.front.edit',
          'route' => 'admin.front.edit',
          'icon' => 'fas fa-fw fa-edit',
        ],
        'settings' => [
          'text' => 'admin/nav.front.settings',
          'route' => 'admin.front.settings',
          'icon' => 'fas fa-fw fa-sliders-h',
        ],
      ]
    ],
    'media' => [
      'text' => 'admin/nav.media.text',
      'icon' => 'fas fa-fw fa-photo-video',
      'children' => [
        'index' => [
          'text' => 'admin/nav.media.index',
          'route' => 'admin.media.index',
          'icon' => 'fas fa-fw fa-images',
        ],
        'upload' => [
          'text' => 'admin/nav.media.upload',
          'route' => 'admin.media.upload',
          'icon' => 'fas fa-fw fa-upload',
        ],
        'settings' => [
          'text' => 'admin/nav.media.settings',
          'route' => 'admin.media.settings',
          'icon' => 'fas fa-fw fa-cogs',
        ],
      ]
    ],
    'profile' => [
      'text' => 'admin/nav.profile.text',
      'icon' => 'fas fa-fw fa-id-badge',
      'children' => [
        'index' => [
          'text' => 'admin/nav.profile.index',
          'route' => 'admin.profile',
          'icon' => 'fas fa-fw fa-home',
        ],
        'basic' => [
          'text' => 'admin/nav.profile.basic',
          'route' => 'admin.profile.basic',
          'icon' => 'fas fa-fw fa-user',
        ],
        'password' => [
          'text' => 'admin/nav.profile.password',
          'route' => 'admin.profile.password',
          'icon' => 'fas fa-fw fa-key',
        ],
        'appearance' => [
          'text' => 'admin/nav.profile.appearance',
          'route' => 'admin.profile.appearance',
          'icon' => 'fas fa-fw fa-palette',
        ],
        'notifications' => [
          'text' => 'admin/nav.profile.notifications',
          'route' => 'admin.profile.notifications',
          'icon' => 'fas fa-fw fa-bell',
        ],
        'two_fa' => [
          'text' => 'admin/nav.profile.two_fa',
          'route' => 'admin.profile.two-fa',
          'icon' => 'fas fa-fw fa-shield-alt',
        ],
        'two_fa_management' => [
          'text' => 'admin/nav.profile.two_fa_management',
          'route' => 'admin.profile.two-fa-management',
          'icon' => 'fas fa-fw fa-fingerprint',
        ],
      ]
    ],
    'members' => [
      'text' => 'admin/nav.settings.members.text',
      'icon' => 'fas fa-fw fa-users-cog',
      'children' => [
        'index' => [
          'text' => 'admin/nav.settings.members.index',
          'route' => 'admin.members.index',
          'icon' => 'fas fa-fw fa-users',
        ],
        'create_edit' => [
          'text' => 'admin/nav.settings.members.create',
          'route' => 'admin.members.create',
          'icon' => 'fas fa-fw fa-user-plus',
        ],
        'roles' => [
          'text' => 'admin/nav.settings.members.roles',
          'route' => 'admin.members.roles',
          'icon' => 'fas fa-fw fa-user-shield',
        ],
      ]
    ],
    'settings' => [
      'text' => 'admin/nav.settings.text',
      'icon' => 'fas fa-fw fa-cogs',
      'children' => [
        'base' => [
          'text' => 'admin/nav.settings.base.text',
          'icon' => 'fas fa-fw fa-gear',
          'children' => [
            'index' => [
              'text' => 'admin/nav.settings.base.index',
              'route' => 'admin.settings.base.index',
              'icon' => 'fas fa-fw fa-tachometer-alt',
            ],
            'site' => [
              'text' => 'admin/nav.settings.base.site',
              'route' => 'admin.settings.base.site',
              'icon' => 'fas fa-fw fa-globe',
            ],
            'admin' => [
              'text' => 'admin/nav.settings.base.admin',
              'route' => 'admin.settings.base.admin',
              'icon' => 'fas fa-fw fa-cog',
            ],
            'mail' => [
              'text' => 'admin/nav.settings.base.mail',
              'route' => 'admin.settings.base.mail',
              'icon' => 'fas fa-fw fa-envelope',
            ],
            'maintenance' => [
              'text' => 'admin/nav.settings.base.maintenance',
              'route' => 'admin.settings.base.maintenance',
              'icon' => 'fas fa-fw fa-tools',
            ],
            'mode' => [
              'text' => 'admin/nav.settings.base.mode',
              'route' => 'admin.settings.base.mode',
              'icon' => 'fas fa-fw fa-sliders-h',
            ],
          ]
        ],
        'security' => [
          'text' => 'admin/nav.settings.security.text',
          'icon' => 'fas fa-fw fa-shield-alt',
          'children' => [
            'index' => [
              'text' => 'admin/nav.settings.security.index',
              'route' => 'admin.settings.security.index',
              'icon' => 'fas fa-fw fa-tachometer-alt',
            ],
            'password' => [
              'text' => 'admin/nav.settings.security.password',
              'route' => 'admin.settings.security.password',
              'icon' => 'fas fa-fw fa-key',
            ],
            'login' => [
              'text' => 'admin/nav.settings.security.login_attempt',
              'route' => 'admin.settings.security.login',
              'icon' => 'fas fa-fw fa-sign-in-alt',
            ],
            'two-fa' => [
              'text' => 'admin/nav.settings.security.two-fa',
              'route' => 'admin.settings.security.two-fa',
              'icon' => 'fas fa-fw fa-user-shield',
            ],
            'notifications' => [
              'text' => 'admin/nav.settings.security.notifications',
              'route' => 'admin.settings.security.notifications',
              'icon' => 'fas fa-fw fa-bell',
            ],
            'captcha' => [
              'text' => 'admin/nav.settings.security.captcha',
              'route' => 'admin.settings.security.captcha',
              'icon' => 'fas fa-fw fa-robot',
            ],
            'session' => [
              'text' => 'admin/nav.settings.security.session',
              'route' => 'admin.settings.security.session',
              'icon' => 'fas fa-fw fa-clock',
            ],
            'csp' => [
              'text' => 'admin/nav.settings.security.csp',
              'route' => 'admin.settings.security.csp',
              'icon' => 'fas fa-fw fa-code',
            ],
            'extensions' => [
              'text' => 'admin/nav.settings.security.extensions',
              'route' => 'admin.settings.security.extensions',
              'icon' => 'fas fa-fw fa-puzzle-piece',
            ],
            'ip' => [
              'text' => 'admin/nav.settings.security.ip',
              'route' => 'admin.settings.security.ip',
              'icon' => 'fas fa-fw fa-network-wired',
            ],
            'integrity' => [
              'text' => 'admin/nav.settings.security.integrity',
              'route' => 'admin.settings.security.integrity',
              'icon' => 'fas fa-fw fa-file-shield',
            ],
            'environment' => [
              'text' => 'admin/nav.settings.security.environment',
              'route' => 'admin.settings.security.environment',
              'icon' => 'fas fa-fw fa-cog',
            ],
          ]
        ],
        'themes' => [
          'text' => 'admin/nav.settings.themes.text',
          'icon' => 'fas fa-fw fa-palette',
          'children' => [
            'index' => [
              'text' => 'admin/nav.settings.themes.index',
              'route' => 'admin.settings.themes.index',
              'icon' => 'fas fa-fw fa-brush',
            ],
            'add' => [
              'text' => 'admin/nav.settings.themes.add',
              'route' => 'admin.settings.themes.add',
              'icon' => 'fas fa-fw fa-plus',
            ],
          ]
        ],
        'plugins' => [
          'text' => 'admin/nav.settings.plugins.text',
          'icon' => 'fas fa-fw fa-puzzle-piece',
          'children' => [
            'index' => [
              'text' => 'admin/nav.settings.plugins.index',
              'route' => 'admin.settings.plugins.index',
              'icon' => 'fas fa-fw fa-puzzle-piece',
            ],
            'add' => [
              'text' => 'admin/nav.settings.plugins.add',
              'route' => 'admin.settings.plugins.add',
              'icon' => 'fas fa-fw fa-plus',
            ],
          ]
        ],
        'systems' => [
          'text' => 'admin/nav.settings.systems.text',
          'icon' => 'fas fa-fw fa-server',
          'children' => [
            'cache' => [
              'text' => 'admin/nav.settings.systems.cache',
              'route' => 'admin.settings.systems.cache',
              'icon' => 'fas fa-fw fa-trash-alt',
            ],
            'database' => [
              'text' => 'admin/nav.settings.systems.database',
              'route' => 'admin.settings.systems.database',
              'icon' => 'fas fa-fw fa-database',
            ],
            'api' => [
              'text' => 'admin/nav.settings.systems.api',
              'route' => 'admin.settings.systems.api',
              'icon' => 'fas fa-fw fa-key',
            ],
            'logs' => [
              'text' => 'admin/nav.settings.systems.logs.text',
              'icon' => 'fas fa-fw fa-file-alt',
              'children' => [
                'audit' => [
                  'text' => 'admin/nav.settings.systems.logs.audit',
                  'route' => 'admin.settings.systems.logs.index',
                  'icon' => 'fas fa-fw fa-clipboard-list',
                ],
                'files' => [
                  'text' => 'admin/nav.settings.systems.logs.files',
                  'route' => 'admin.settings.systems.logs.files',
                  'icon' => 'fas fa-fw fa-scroll',
                ],
              ]
            ],
            'info' => [
              'text' => 'admin/nav.settings.systems.info',
              'route' => 'admin.settings.systems.info',
              'icon' => 'fas fa-fw fa-info-circle',
            ],
          ]
        ]
      ],
    ]

];
