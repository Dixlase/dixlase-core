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


    'nav' => [
        'dashboard' => 'ダッシュボード',
        'contents' => [
            'text' => 'コンテンツ管理',
            'pages' => [
                'text' => 'ページ管理',
                'index' => 'ページマスター',
                'create' => 'ページ新規作成',
            ],
            'themes' => [
                'text' => 'テーマ設定',
                'index' => '一覧',
                'install' => 'インストール',
            ]
        ],
        'users' => [
            'text' => 'ユーザー管理',
            'index' => 'ユーザーマスター',
            'create' => 'ユーザー新規作成',
        ],
        'settings' => [
            'text' => '設定',
            'members' => [
                'text' => 'メンバー設定',
                'index' => 'メンバーマスター',
                'create' => '新規メンバー作成',
                'edit' => '編集',
                'profile' => 'プロフィール設定',
            ],
            'plugins' => [
                'text' => 'プラグイン設定',
                'index' => 'プラグインマスター',
                'install'  => 'インストール',
            ],
            'security' => 'セキュリティ設定',
            'systems' => 'システム設定',

        ],
    ],

    'features' => [
        'dashboard' => [
            'heading' => 'ダッシュボード',
        ],
        'contents' => [
            'pages' => [
                'title' => 'ページタイトル',
                'slug' => 'スラッグ',
                'content' => 'コンテンツ',
                'status' => 'ステータス',
                'index' => [
                    'heading' => 'ページマスター',
                ],
                'create' => [
                    'heading' => '新規ページ作成',
                ],
                'edit' => [
                    'heading' => 'ページ編集',
                ],
            ],
            'themes' => [
                'title' => 'テーマ設定',
                'color' => 'カラー',
                'font' => 'フォント',
                'submit' => '更新',
                'index' => [
                    'heading' => 'テーマ一覧',
                ],
                'install' => [
                    'heading' => 'テーマインストール',
                    'name' => 'テーマ名',
                    'submit' => 'インストール',
                ],
            ],
        ],
        'users' => [
            'index' => [
                'heading' => 'ユーザーマスター',
                'name' => '名前',
                'email' => 'メールアドレス',
                'role' => 'ロール',

            ],
            'create' => [
                'heading' => '新規ユーザー作成',
                'name' => '名前',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'password_confirmation' => 'パスワード確認',
                'role' => 'ロール',
                'submit' => '登録',
            ],
            'edit' => [
                'heading' => 'ユーザー編集',
                'name' => '名前',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'password_confirmation' => 'パスワード確認',
                'role' => 'ロール',
                'submit' => '更新',
            ],
        ],
        'settings' => [
            'admins' => [
                'index' => [
                    'heading' => 'メンバーマスター',
                ],
                'create' => [
                    'heading' => '新規メンバー作成',
                    'name' => '名前',
                    'email' => 'メールアドレス',
                    'password' => 'パスワード',
                    'password_confirmation' => 'パスワード確認',
                    'role' => 'ロール',
                    'submit' => '登録',
                ],
                'edit' => [
                    'heading' => 'メンバー編集',
                    'name' => '名前',
                    'email' => 'メールアドレス',
                    'password' => 'パスワード',
                    'password_confirmation' => 'パスワード確認',
                    'role' => 'ロール',
                    'submit' => '更新',
                ],
                'profile' => [
                    'heading' => 'プロフィール設定',
                    'name' => '名前',
                    'email' => 'メールアドレス',
                    'password' => 'パスワード',
                    'password_confirmation' => 'パスワード確認',
                    'submit' => '更新',
                ],
            ],
            'plugins' => [
                'index' => [
                    'heading' => 'プラグインマスター',
                ],
                'install' => [
                    'heading' => 'プラグインインストール',
                    'name' => 'プラグイン名',
                    'submit' => 'インストール',
                ],
            ],
            'security' => [
                'heading' => 'セキュリティ設定',
                'admin_url' => '管理画面URL',
                'enable_allowed_admin_ips' => '特定のIPアドレスのみアクセスを許可',
                'allowed_admin_ips' => '許可IPアドレス',
                'enable_blocked_admin_ips' => '特定のIPアドレスをブロック',
                'blocked_admin_ips' => 'ブロックIPアドレス',
                'force_ssl' => 'SSL強制',
                'submit' => '更新',
            ],
            'systems' => [
                'heading' => 'システム設定',
                'site_name' => 'サイト名',
                'is_member_site' => '会員サイト',
                'allow_external_registration' => '外部登録',
                'allow_guest_registration' => 'ゲスト登録',
                'required_fields' => '必須項目',
                'language' => '言語',
                'maintenance_mode' => 'メンテナンスモード',
                'maintenance_message' => 'メンテナンスメッセージ',
                'submit' => '更新',
            ],
        ],
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
        'super_manager' => '特権管理者',
        'manager' => '管理者',
        'editor' => '編集者',
        'receptionist' => '受付',
        'viewer' => '閲覧者',
    ],

    'theme' => [
        'auto' => '自動',
        'dark' => 'ダーク',
        'light' => 'ライト',
    ],


];
