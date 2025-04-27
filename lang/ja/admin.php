<?php

/**
 * This file is part of MySoftware.
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

    'common' => [
        'search' => '検索',
        'submit' => '更新',
        'install' => 'インストール',
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
        'theme' => [
            'auto' => '自動',
            'dark' => 'ダーク',
            'light' => 'ライト',
        ],
    ],

    'locales' => [
        'ja_JA' => '日本語',
        'en_EN' => 'English',
    ],
    'role' => '権限',
    'roles' => [
        'SUPER_ADMIN' => '特権管理者',
        'ADMIN' => '管理者',
        'EDITOR' => '編集者',
        'AUTHOR' => '投稿者',
        'CONTRIBUTOR' => '寄稿者',
    ],
    'status' => [
        'Inactive' => '無効',
        'Active' => '有効',
    ],
    'nav' => [
        'dashboard' => 'ダッシュボード',
        'front' => [
            'text' => 'フロントページ管理',
            'index' => 'フロントページマスター',
            'design' => 'フロントページデザイン',
            'settings' => 'フロントページ設定',
        ],
        'media' => [
            'text' => 'メディア管理',
            'index' => 'メディアマスター',
            'upload' => 'メディアアップロード',
            'settings' => 'メディア設定'
        ],
        'settings' => [
            'text' => '全体設定',
            'base' => '基本設定',
            'security' => 'セキュリティ設定',
            'members' => [
                'text' => 'メンバー管理',
                'index' => 'メンバーマスター',
                'create' => '新規メンバー作成',
                'edit' => '編集',
                'profile' => 'プロフィール設定',
                'roles' => '権限設定',
                'settings' => 'メンバー全体設定',
            ],
            'themes' => [
                'text' => 'テーマ設定',
                'index' => '一覧',
                'install' => 'インストール',
            ],
            'plugins' => [
                'text' => 'プラグイン設定',
                'index' => 'プラグインマスター',
                'install'  => 'インストール',
            ],
            'systems' => [
                'text' => 'システム',
                'logs' => 'ログ',
                'info'  => 'システム情報',
            ],
        ],
    ],


    // ダッシュボード
    'dashboard' => [
        'heading' => 'ダッシュボード',
        'description' => 'サイトの概要を確認できます。',
    ],

    // フロントページ
    'front' => [
        'index' => [
            'heading' => 'フロントページマスター',
            'description' => 'フロントページのプレビューを確認できます。',
        ],
        'design' => [
            'heading' => 'フロントページデザイン',
            'description' => 'フロントページのデザインを行います。',
        ],
        'settings' => [
            'heading' => 'フロントページ設定',
            'description' => 'フロントページの設定を行います。',
        ],

    ],

    // メディア
    'media' => [
        'index' => [
            'heading' => 'メディアマスター',
        ],
        'upload' => [
            'heading' => 'メディアアップロード',
        ],
        'settings' => [
            'heading' => 'メディア設定',
        ],

    ],




    // 設定
    'settings' => [
        // 基本
        'base' => [
            'heading' => '基本設定',
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
        // セキュリティ
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
        // メンバー
        'members' => [
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
                'always' => 'メンバー全体設定で「常に有効」にされているため、個別設定は変更できません。',
                'submit' => 'プロフィールを更新',
                'updated' => 'プロフィールが更新されました。',
            ],
            'settings' => [
                'heading' => 'メンバー全体設定',
                'password_conditions' => 'パスワードの条件',
                'password' => 'パスワード',
                'password_confirmation' => 'パスワード確認',
                'role' => 'ロール',
                'submit' => '更新',
                'confirm_title' => '設定の更新',
                'confirm_message' => 'この内容で設定を更新しますか？',
                'confirm_label' => '更新',
                'cancel_label' => '戻る',
                'updated' => 'メンバー全体設定が更新されました。',
                'password_min_length' => 'パスワードの最小文字数',
                'password_min_length_options' => [
                    8 => '8文字以上',
                    12 => '12文字以上',
                    16 => '16文字以上',
                ],
                'password_require_uppercase' => '大文字を含める',
                'password_require_uppercase_options' => [
                    1 => '含める',
                    0 => '含めない',
                ],
                'password_require_symbol' => '記号を含める',
                'password_require_symbol_options' => [
                    1 => '含める',
                    0 => '含めない',
                ],

            ],
            'login_notification_mode' => [
                'label' => 'ログイン通知の設定',
                'options' => [
                    0 => 'メンバーのプロフィール設定を反映',
                    1 => '無効',
                    2 => '常に有効',
                    3 => '異なる端末/IP時のみ有効',
                ]

            ],
            'two_factor_mode' => [
                'label' => '2段階認証の設定',
                'options' => [
                    0 => 'メンバーのプロフィール設定を反映',
                    1 => '無効',
                    2 => '常に有効',
                    3 => '異なる端末/IP時のみ有効',
                ]
            ],
            'force_setting_1' => 'メンバー全体設定で',
            'force_setting_2' => 'が選択されているため、個別設定は変更できません。',
            'status' => 'ステータス',
            'status_options' => [
                1 => '有効',
                0 => '無効',
            ],
            'role' => 'ロール',
            'role_options' => [
                'super_admin' => '特権管理者',
                'admin' => '管理者',
                'editor' => '編集者',
                'author' => '投稿者',
                'contributor' => '寄稿者',
            ],
        ],
        // テーマ
        'themes' => [
            'index' => [
                'heading' => 'テーマ設定',
                'title' => 'テーマ設定',
                'color' => 'カラー',
                'font' => 'フォント',
                'submit' => '更新',
            ],
            'install' => [
                'heading' => 'テーマインストール',
                'name' => 'テーマ名',
                'submit' => 'インストール',
            ],
        ],
        // プラグイン
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

        //システム情報
        'systems' => [
            'logs' => [
                'heading' => 'ログ情報',
                'activity' => '管理アクティビティ',
                'error' => 'エラー',
                'login' => 'ログイン',
                'laravel' => 'Laravel',

            ],
            'info' => [
                'heading' => 'システム情報',
            ],
        ],
    ],

];
