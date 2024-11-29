<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during admin for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'dashboard' => 'ダッシュボード',
    'users' => [
        'text' => 'ユーザー管理',
        'index' => 'ユーザーマスター',
        'create' => 'ユーザー新規作成',
    ],
    'settings' => [
        'text' => '設定',
        'admins' => [
            'text' => '管理者設定',
            'index' => '管理者マスター',
            'create' => '新規管理者作成',
            'edit' => '編集',
            'profile' => 'プロフィール設定',
        ],
        'systems' => 'システム設定'
    ],
    'roles' => 'ロール',
    'permissions' => '権限',

    'logout' => 'ログアウト',

    'required' => ':attribute は必須です。',
    'email' => ':attribute は正しいメールアドレス形式で入力してください。',
    'unique' => ':attribute は既に存在しています。',
    'min' => ':attribute は最低 :min 文字必要です。',
    'confirmed' => ':attribute 確認が一致しません。',
    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],

    'roles' => [
        'text' => '権限',
        'super_admin' => '特権管理者',
        'admin' => '管理者',
        'editor' => '編集者',
        'receptionist' => '受付',
    ],
];
