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
            'text' =>  'admin.dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'fas fa-fw fa-tachometer-alt',
        ],
        'users' => [
            'text' => 'admin.users.text',
            'icon' => 'fas fa-fw fa-users',
            'children' => [
                'index' => [
                    'text' => 'admin.users.index',
                    'route' => 'admin.users.index',
                    'icon' => 'fas fa-fw fa-users',
                ],
                'create' => [
                    'text' => 'admin.users.create',
                    'route' => 'admin.users.create',
                    'icon' => 'fas fa-fw fa-users',
                ],
            ]
        ],
        'setting' => [
            'text' => 'admin.settings.text',
            'icon' => 'fas fa-fw fa-cogs',
            'children' => [
                'admins' => [
                    'text' => 'admin.settings.admins.text',
                    'icon' => 'fas fa-fw fa-users',
                    'children' => [
                        'index' => [
                            'text' => 'admin.settings.admins.index',
                            'route' => 'admin.settings.admins.index',
                            'icon' => 'fas fa-fw fa-users',
                        ],
                        'create' => [
                            'text' => 'admin.settings.admins.create',
                            'route' => 'admin.settings.admins.create',
                            'icon' => 'fas fa-fw fa-users',
                        ],
                        'profile' => [
                            'text' => 'admin.settings.admins.profile',
                            'route' => 'admin.settings.admins.profile',
                            'icon' => 'fas fa-fw fa-users',
                        ]
                    ]
                ],
                'systems' => [
                    'text' => 'admin.settings.systems',
                    'route' => 'admin.settings.systems',
                    'icon' => 'fas fa-fw fa-users',
                ],

            ],
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
        //特権管理者
        'super_admin' => 'admin.roles.super_admin',
        //管理者
        'admin' => 'admin.roles.admin',
        //編集者
        'editor' => 'admin.roles.editor',
        //受付
        'receptionist' => 'admin.roles.receptionist',
    ],


    /*
    |--------------------------------------------------------------------------
    | 管理画面テーマ
    |--------------------------------------------------------------------------
    |
    | ここでは、管理画面のテーマに関する設定を行います。
    | これにより、管理画面のテーマを簡単に変更できます。
    |
    */


    'theme' => 'dark', // 'light' または 'dark' に切り替え可能
    'theme_class' => [
        'layout' => [
            'body' => 'bg-white text-gray-900 dark:bg-gray-950 dark:text-white',
            'header' => 'bg-gray-200 dark:bg-gray-900 border-gray-300 dark:border-gray-700 border-b',
            'logo' => 'text-gray-900 dark:text-white',
            'aside' => 'bg-gray-100 dark:bg-gray-900 text-gray-900 border-r border-gray-300  dark:text-white dark:border-r dark:border-gray-700',
            'main' => 'bg-white text-gray-900 dark:bg-gray-900 dark:text-white',
            'title' => 'bg-gray-100 text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
            'heading' => 'text-gray-800 dark:text-white',
            'nav_link' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'button_admin_user' => 'text-gray-500 bg-white hover:text-gray-700 dark:text-gray-300 dark:bg-gray-800 dark:hover:text-white',
            'button_hamburger' => 'text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700',
            'responsive_navigation_menu' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
            'option_1' => 'border-gray-200 dark:border-gray-700',
            'option_2' => 'text-gray-800 dark:text-white',
            'option_3' => 'text-gray-500 dark:text-gray-400',
        ],
        'table' => [
            'header' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white',
            //'row' => 'bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white',
            'row' => 'odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700',
            'row_hover' => 'hover:bg-gray-100 dark:hover:bg-gray-600',
            'row_selected' => 'bg-gray-200 dark:bg-gray-600',
            'row_selected_hover' => 'hover:bg-gray-200 dark:hover:bg-gray-600',
            'cell' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected_hover' => 'border-b border-gray-200 dark:border-gray-700',
        ],
        'link' => 'text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-500',
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




];
