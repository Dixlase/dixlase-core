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
        //
        'setting' => [
            'text' => 'admin.setting',
            'route' => 'admin.setting.index',
            'icon' => 'fas fa-fw fa-cogs',
        ],
        /*
        'users' => [
            'text' => 'admin.users',
            'route' => 'admin.users.index',
            'icon' => 'fas fa-fw fa-users',
        ],
        */


    ]
];
