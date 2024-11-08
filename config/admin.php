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
        //
        'setting' => [
            'text' => 'admin.settings.index',
            'icon' => 'fas fa-fw fa-cogs',
            'children' => [
                'admins' => [
                    'text' => 'admin.settings.admins',
                    'route' => 'admin.settings.admins',
                    'icon' => 'fas fa-fw fa-users',
                ],
                'systems' => [
                    'text' => 'admin.settings.systems',
                    'route' => 'admin.settings.systems',
                    'icon' => 'fas fa-fw fa-users',
                    'children' => [
                        'create' => [
                            'text' => 'admin.settings.systems.create',
                            'route' => 'admin.settings.systems.create',
                            'icon' => 'fas fa-fw fa-users',
                        ]
                    ]
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


    ]
];
