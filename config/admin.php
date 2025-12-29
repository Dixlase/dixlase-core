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


    // 管理画面のURL
    'admin_url' => env('ADMIN_URL', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | 管理画面ナビゲーション
    |--------------------------------------------------------------------------
    |
    | ここでは、管理画面のナビゲーションに関する設定を行います。
    | これにより、管理画面のナビゲーションを簡単に変更できます。
    |
    */

    'nav' => [
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
            'text' => 'admin/nav.profile',
            'route' => 'admin.profile',
            'icon' => 'fas fa-fw fa-id-badge',
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
                'create' => [
                    'text' => 'admin/nav.settings.members.create',
                    'route' => 'admin.members.create',
                    'icon' => 'fas fa-fw fa-user-plus',
                ],
                'settings' => [
                    'text' => 'admin/nav.settings.members.settings',
                    'icon' => 'fas fa-fw fa-user-cog',
                    'children' => [
                        'overview' => [
                            'text' => 'admin/nav.settings.members.overview',
                            'route' => 'admin.members.settings.index',
                            'icon' => 'fas fa-fw fa-list-alt',
                        ],
                        'password' => [
                            'text' => 'admin/nav.settings.members.settings_nav.password',
                            'route' => 'admin.members.settings.password',
                            'icon' => 'fas fa-fw fa-key',
                        ],
                        'session' => [
                            'text' => 'admin/nav.settings.members.settings_nav.session',
                            'route' => 'admin.members.settings.session',
                            'icon' => 'fas fa-fw fa-clock',
                        ],
                        'auth' => [
                            'text' => 'admin/nav.settings.members.settings_nav.auth',
                            'route' => 'admin.members.settings.auth',
                            'icon' => 'fas fa-fw fa-shield-alt',
                        ],
                        'roles' => [
                            'text' => 'admin/nav.settings.members.roles_short',
                            'route' => 'admin.members.settings.roles',
                            'icon' => 'fas fa-fw fa-user-shield',
                        ],
                    ]
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
                        'auth' => [
                            'text' => 'admin/nav.settings.security.auth',
                            'route' => 'admin.settings.security.auth',
                            'icon' => 'fas fa-fw fa-user-lock',
                        ],
                        'captcha' => [
                            'text' => 'admin/nav.settings.security.captcha',
                            'route' => 'admin.settings.security.captcha',
                            'icon' => 'fas fa-fw fa-robot',
                        ],
                        'ip' => [
                            'text' => 'admin/nav.settings.security.ip',
                            'route' => 'admin.settings.security.ip',
                            'icon' => 'fas fa-fw fa-network-wired',
                        ],
                        'extensions' => [
                            'text' => 'admin/nav.settings.security.extensions',
                            'route' => 'admin.settings.security.extensions',
                            'icon' => 'fas fa-fw fa-puzzle-piece',
                        ],
                        'csp' => [
                            'text' => 'admin/nav.settings.security.csp',
                            'route' => 'admin.settings.security.csp',
                            'icon' => 'fas fa-fw fa-code',
                        ],
                        'notifications' => [
                            'text' => 'admin/nav.settings.security.notifications',
                            'route' => 'admin.settings.security.notifications',
                            'icon' => 'fas fa-fw fa-bell',
                        ],
                        'environment' => [
                            'text' => 'admin/nav.settings.security.environment',
                            'route' => 'admin.settings.security.environment',
                            'icon' => 'fas fa-fw fa-cog',
                        ],
                        'integrity' => [
                            'text' => 'admin/nav.settings.security.integrity',
                            'route' => 'admin.settings.security.integrity',
                            'icon' => 'fas fa-fw fa-file-shield',
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
    ],

    /*
    |--------------------------------------------------------------------------
    | 管理画面外観
    |--------------------------------------------------------------------------
    |
    | ここでは、管理画面の外観に関する設定を行います。
    | これにより、管理画面の外観を簡単に変更できます。
    |
    */

    //外観モードの設定
    'appearance' => [
        0 => 'admin.theme.auto',
        1 => 'admin.theme.dark',
        2 => 'admin.theme.light',
    ],



    /*
    |--------------------------------------------------------------------------
    | 権限設定
    |--------------------------------------------------------------------------
    |
    | ここでは、管理画面の権限設定に関する設定を行います。
    | これにより、管理画面の権限設定を簡単に変更できます。
    |
    */

    'roles' => [
        // 特権管理者
        'super_admin' => 'admin.roles.super_admin',
        // 管理者
        'admin' => 'admin.roles.admin',
        // 編集者
        'editor' => 'admin.roles.editor',
        // 投稿者
        'author' => 'admin.roles.author',
        // 寄稿者
        'contributor' => 'admin.roles.contributor',
        // 受付
        'receptionist' => 'admin.roles.receptionist',
        // ゲスト
        'guest' => 'admin.roles.guest',

    ],

    // 権限の階層
    'roles_hierarchy' => [
        '1' => ['1'],                   // super_admin
        '2' => ['1', '2'],               // admin
        '3' => ['1', '2', '3'],          // editor
        '4' => ['1', '2', '3', '4'],     // author
        '5' => ['1', '2', '3', '4', '5'], // contributor
        '6' => ['1', '2', '3', '4', '5', '6'], // receptionist
        '7' => ['1', '2', '3', '4', '5', '6', '7'], // guest
    ],

    //ログイン時のメール通知設定
    'global_login_notification_mail_mode' => [0, 1, 2, 3], // 0: 無効, 1: 異なる端末/IP時のみ有効, 2: 常に有効, 3: メンバーのプロフィール設定を反映
    'members_login_notification_mail_mode' => [0, 1, 2], // 0: 無効, 1: 異なる端末/IP時のみ有効, 2: 常に有効

    //二段階認証の設定
    'global_two_factor_mode' => [0, 1, 2, 3], // 0: 無効, 1: 異なるデバイス・IP時のみ, 2: 常に有効, 3: メンバーのプロフィール設定に従う
    'members_two_factor_mode' => [0, 1, 2], // 0: 無効, 1: 異なるデバイス・IP時のみ, 2: 常に有効

    /*
    |--------------------------------------------------------------------------
    | 管理画面フォーム
    |--------------------------------------------------------------------------
    |
    | ここでは、管理画面のフォームに関する設定を行います。
    | これにより、管理画面のフォームを簡単に変更できます。
    |
    */

    'form_class' => [
        'text' => 'block w-full rounded-md shadow-sm sm:text-sm',
        'button' => 'ml-2 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700',
        'input' => 'block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
    ],


    /*
    |--------------------------------------------------------------------------
    | フロントページテンプレート
    |--------------------------------------------------------------------------
    |
    | ここでは、フロントページのテンプレートに関する設定を行います。
    | これにより、フロントページのテンプレートを簡単に変更できます。
    |
    */

    'default_theme' => 'default',

    /*
    |--------------------------------------------------------------------------
    | 言語設定
    |--------------------------------------------------------------------------
    |
    | ここでは、言語設定に関する設定を行います。
    | これにより、言語設定を簡単に変更できます。
    |
    */

    'locale' => [
        'default' => 'en',
        'available' => [
            'ja' => [
                'name' => '日本語',
                'faker_locale' => 'ja_JA',
            ],
            'en' => [
                'name' => 'English',
                'faker_locale' => 'en_EN',
            ],
        ],
    ],




    'fileExtensions' => [
        'jpg',
        'png',
        'gif',
        'webp',
        'svg',
        'mp4',
        'pdf',
        'docx',
        'zip',
        'txt'
    ],
    
    // ファイル拡張子の表示名
    'fileExtensionNames' => [
        'jpg' => 'JPEG',
        'png' => 'PNG',
        'gif' => 'GIF',
        'webp' => 'WebP',
        'svg' => 'SVG',
        'mp4' => 'MP4 Video',
        'pdf' => 'PDF',
        'docx' => 'Word Document',
        'zip' => 'ZIP Archive',
        'txt' => 'Text',
    ],
    'allowedFileTypes' => [
        'jpg',
        'png',
        'gif',
        'mp4',
        'pdf',
    ],
    'maxFileSize' => 2048,
    'storageDisk' => 'public',
    'generateThumbnails' => true,
    'perPage' => 10,
    'mediaPath' => 'media',
];
