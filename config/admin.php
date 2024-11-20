<?php
return [

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
        'light' => [
            'body' => 'bg-white text-gray-900',
            'header' => 'bg-gray-200 border-b border-gray-900',
            'logo' => 'text-gray-900',
            'aside' => 'bg-gray-100 text-gray-900 border-r border-gray-900',
            'main' => 'bg-white text-gray-900',
            'title' => 'bg-gray-100 text-gray-800 border-b border-gray-500',
            'headding' => 'text-gray-800',
            'nav_link' => 'text-gray-700 hover:text-black',
            'button_admin_user' => 'text-gray-500 bg-white hover:text-gray-700',
            'button_hamburger' => 'text-gray-400 hover:text-gray-500 hover:bg-gray-100',
            'responsive_navigation_menu' => 'text-gray-700 hover:text-black',
            'option_1' => 'border-gray-200',
            'option_2' => 'text-gray-800',
            'option_3' => 'text-gray-500',
            'form_input' => 'text-gray-500',
            'form_input_button' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none',
            'form_input_text' => 'bg-white text-gray-700 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500',
        ],
        'dark' => [
            'body' => 'bg-gray-950 text-white',
            'header' => 'bg-gray-900 border-b border-gray-700',
            'logo' => 'text-white',
            'aside' => 'bg-gray-900 text-white border-r border-gray-700',
            'main' => 'text-white',
            'title' => 'bg-gray-900 text-white',
            'headding' => 'text-white',
            'nav_link' => 'text-gray-100 hover:text-white',
            'button_admin_user' => 'text-gray-300 bg-gray-800 hover:text-white',
            'button_hamburger' => 'text-gray-400 hover:text-white hover:bg-gray-700',
            'responsive_navigation_menu' => 'text-gray-300 hover:text-white',
            'option_1' => 'border-gray-700',
            'option_2' => 'text-white',
            'option_3' => 'text-gray-400',
            'form_input' => 'text-gray-600',
            'form_input_button' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none',
            'form_input_text' => 'bg-gray-900 border-gray-500 focus:border-indigo-500 focus:ring-indigo-500',

        ],
        'form' => [
            'text' => 'admin.forms',
            'route' => 'admin.forms.index',
            'icon' => 'fas fa-fw fa-users',
        ],
    ],


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

        /*
        'users' => [
            'text' => 'admin.users',
            'route' => 'admin.users.index',
            'icon' => 'fas fa-fw fa-users',
        ],
        */


    ],



];
