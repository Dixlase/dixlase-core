<?php
return [


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
            'text' =>  'admin.nav.dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'fas fa-fw fa-tachometer-alt',
            'can' => 'viewer',
        ],
        'contents' => [
            'text' => 'admin.nav.contents.text',
            'icon' => 'fas fa-fw fa-file',
            'can' => 'editor',
            'children' => [
                'pages' => [
                    'text' => 'admin.nav.contents.pages.text',
                    'icon' => 'fas fa-fw fa-file',
                    'can' => 'editor',
                    'children' => [
                        'index' => [
                            'text' => 'admin.nav.contents.pages.index',
                            'route' => 'admin.contents.pages.index',
                            'can' => 'editor',
                            'icon' => 'fas fa-fw fa-file',
                        ],
                        'create' => [
                            'text' => 'admin.nav.contents.pages.create',
                            'route' => 'admin.contents.pages.create',
                            'can' => 'editor',
                            'icon' => 'fas fa-fw fa-file',
                        ],
                    ]
                ],
                'themes' => [
                    'text' => 'admin.nav.contents.themes.text',
                    'icon' => 'fas fa-fw fa-palette',
                    'can' => 'manager',
                    'children' => [
                        'index' => [
                            'text' => 'admin.nav.contents.themes.index',
                            'route' => 'admin.contents.themes.index',
                            'icon' => 'fas fa-fw fa-file',
                            'can' => 'manager',
                        ],
                        'install' => [
                            'text' => 'admin.nav.contents.themes.install',
                            'route' => 'admin.contents.themes.install',
                            'can' => 'super_manager',
                            'icon' => 'fas fa-fw fa-file',
                        ],
                    ]
                ],
            ]
        ],

        'users' => [
            'text' => 'admin.nav.users.text',
            'icon' => 'fas fa-fw fa-users',
            'can' => 'viewer',
            'children' => [
                'index' => [
                    'text' => 'admin.nav.users.index',
                    'route' => 'admin.users.index',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'viewer',
                ],
                'create' => [
                    'text' => 'admin.nav.users.create',
                    'route' => 'admin.users.create',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'receptionist',
                ],
            ]
        ],

        'settings' => [
            'text' => 'admin.nav.settings.text',
            'icon' => 'fas fa-fw fa-cogs',
            'can' => 'viewer',
            'children' => [
                'members' => [
                    'text' => 'admin.nav.settings.members.text',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'viewer',
                    'children' => [
                        'index' => [
                            'text' => 'admin.nav.settings.members.index',
                            'route' => 'admin.settings.members.index',
                            'icon' => 'fas fa-fw fa-users',
                            'can' => 'manager',
                        ],
                        'create' => [
                            'text' => 'admin.nav.settings.members.create',
                            'route' => 'admin.settings.members.create',
                            'icon' => 'fas fa-fw fa-users',
                            'can' => 'manager',
                        ],
                        'profile' => [
                            'text' => 'admin.nav.settings.members.profile',
                            'route' => 'admin.settings.members.profile',
                            'icon' => 'fas fa-fw fa-users',
                            'can' => 'viewer',
                        ]
                    ]
                ],
                'plugins' => [
                    'text' => 'admin.nav.settings.plugins.text',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'manager',
                    'children' => [
                        'index' => [
                            'text' => 'admin.nav.contents.themes.index',
                            'route' => 'admin.settings.plugins.index',
                            'icon' => 'fas fa-fw fa-file',
                            'can' => 'manager',
                        ],
                        'install' => [
                            'text' => 'admin.nav.contents.themes.install',
                            'route' => 'admin.settings.plugins.install',
                            'icon' => 'fas fa-fw fa-file',
                            'can' => 'super_manager',
                        ],
                    ]
                ],
                'security' => [
                    'text' => 'admin.nav.settings.security',
                    'route' => 'admin.settings.security.index',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'super_manager',
                ],
                'systems' => [
                    'text' => 'admin.nav.settings.systems',
                    'route' => 'admin.settings.systems',
                    'icon' => 'fas fa-fw fa-users',
                    'can' => 'super_manager',
                ],
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
        1 => 'admin.theme.light',
        2 => 'admin.theme.dark',
    ],

    //外観モードのクラス
    'appearance_class' => [
        'layout' => [
            'body' => 'bg-white text-gray-900 dark:bg-gray-950 dark:text-white',
            'header' => 'bg-gray-200 dark:bg-gray-800 border-gray-300 dark:border-gray-700',
            'logo' => 'text-gray-900 dark:text-white',
            'aside' => 'bg-gray-100 dark:bg-gray-800 text-gray-900 border-r border-gray-300  dark:text-white dark:border-r dark:border-gray-700',
            'main' => 'bg-white text-gray-900 dark:bg-black dark:text-white',
            'title' => 'bg-gray-100 text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
            'heading' => 'text-gray-800 dark:text-white',
            'nav_link' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'button_admin_user' => 'text-gray-500 bg-white hover:text-gray-700 dark:text-gray-300 dark:bg-gray-800 dark:hover:text-white',
            'button_hamburger' => 'text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700',
            'responsive_navigation_menu' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'option_1' => 'border-gray-200 dark:border-gray-700',
            'option_2' => 'text-gray-800 dark:text-white',
            'option_3' => 'text-gray-500 dark:text-gray-400',
            'save_button' => 'bg-gray-200 dark:bg-gray-800 border-gray-300 dark:border-gray-700 ',
        ],
        'sidebar' => [
            'normal' => 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 hover:text-black dark:hover:bg-gray-700 dark:hover:text-white',
            'active' => 'bg-gray-200 text-gray-900 font-bold border-blue-500 pl-3 rounded-md hover:bg-gray-300 hover:text-black dark:bg-gray-100 dark:text-black dark:hover:bg-gray-600'
        ],
        'table' => [
            'table' => 'w-full text-sm text-left rtl:text-right mb-4',
            'thead' => 'text-xs uppercase bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white',
            'tr' => 'odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700',
            'td' => 'px-4 py-4 border-b dark:border-gray-700',
            'row_hover' => 'hover:bg-gray-100 dark:hover:bg-gray-600',
            'row_selected' => 'bg-gray-200 dark:bg-gray-600',
            'row_selected_hover' => 'hover:bg-gray-200 dark:hover:bg-gray-600',
            'cell' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected_hover' => 'border-b border-gray-200 dark:border-gray-700',
        ],
        'link' => 'text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-500',
        'form' => [
            'label' => 'block text-sm font-medium text-gray-700 dark:text-gray-300',
            'text' => 'bg-white text-gray-100 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500',
            'input' => 'block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            'button' => 'ml-2 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700',
            'checkbox' => 'text-gray-600 dark:text-gray-600',
            'error' => 'text-red-600 dark:text-red-400',
            'select' => 'block w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
            'textarea' => 'bg-white text-gray-700 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500',
            'modal' => 'bg-white dark:bg-gray-900',
            'radio' => 'text-gray-600 dark:text-gray-600'
        ]
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
        'super_manager' => 'admin.roles.super_manager',
        // 管理者
        'manager' => 'admin.roles.manager',
        // 編集者
        'editor' => 'admin.roles.editor',
        // 受付
        'receptionist' => 'admin.roles.receptionist',
        // 閲覧者
        'viewer' => 'admin.roles.viewer',
    ],

    // 権限の階層
    'roles_hierarchy' => [
        'super_manager' => ['super_manager'],
        'manager' => ['super_manager', 'manager'],
        'editor' => ['super_manager', 'manager', 'editor'],
        'receptionist' => ['super_manager', 'manager', 'editor', 'receptionist'],
        'viewer' => ['super_manager', 'manager', 'editor', 'receptionist', 'viewer'],
    ],


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

    'languages' => [
        'default' => 'ja',
        'available' => [
            'ja' => '日本語',
            'en' => 'English',
        ],
    ],

    'status' => [
        'pages' => [
            '1' => '公開',
            '0' => '下書き',
        ],
        'users' => [
            '1' => '有効',
            '0' => '無効',
        ],
        'admins' => [
            '1' => '有効',
            '0' => '無効',
        ],
    ]
];
