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
        'ja' => '日本語',
        'en' => 'English',
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
            'upload_new_file' => '新しいファイルをアップロード',
            'download' => 'ダウンロード',
            'preview' => 'プレビュー',
            'delete' => '削除',
        ],
        'upload' => [
            'heading' => 'メディアアップロード',
            'select_file' => 'メディアファイルを選択:',
            'drag_drop_text' => 'ここにファイルをドラッグするか、クリックしてアップロード',
            'supported_formats' => '対応形式:',
            'upload_button' => 'アップロード',
        ],
        'preview' => [
            'heading' => 'メディアプレビュー',
            'no_preview' => 'ファイルはプレビューできません。',
            'file_name' => 'ファイル名:',
            'file_type' => 'ファイルタイプ:',
            'upload_date' => 'アップロード日時:',
            'uploaded_by' => 'アップロードしたメンバー:',
            'unknown' => '不明',
            'media_url' => 'メディアURL',
            'copy' => 'コピー',
            'copied' => 'コピー済み',
            'url_description' => 'このURLを使用してメディアファイルに直接アクセスできます。',
            'back' => '戻る',
            'download' => 'ダウンロード',
            'delete' => '削除',
            'delete_confirmation' => '削除の確認',
            'delete_message' => 'このメディアファイルを削除しますか？',
            'cancel' => 'キャンセル',
            'copy_failed' => 'コピーに失敗しました。手動でURLを選択してコピーしてください。',
        ],
        'settings' => [
            'heading' => 'メディア設定',
            'allowed_file_types' => '許可するファイルタイプ',
            'max_file_size' => '最大ファイルサイズ',
            'file_size_range' => '(1MB - 100MB)',
            'save_settings' => '設定を保存',
        ],

    ],




    // 設定
    'settings' => [
        // 基本
        'base' => [
            'heading' => '基本設定',
            'site_settings' => 'サイト設定',
            'app_name' => 'アプリケーション名',
            'locale' => '言語設定',
            'timezone' => 'タイムゾーン',
            'mail_server_settings' => 'メールサーバー設定',
            'mailer' => 'Mailer',
            'mail_host' => 'ホスト名',
            'mail_port' => 'ポート番号',
            'mail_username' => 'ユーザー名',
            'mail_password' => 'パスワード',
            'mail_encryption' => '暗号化方式',
            'mail_from_address' => '送信元メールアドレス',
            'mail_test' => 'メール送信テスト',
            'mail_test_description' => '現在の設定でテストメールを送信します。送信元メールアドレス宛にテストメールが送信されます。',
            'test_connection_button' => '接続テスト',
            'test_mail_button' => 'テストメール送信',
            'testing_connection' => '接続中...',
            'testing_mail' => '送信中...',
            'mail_test_error' => 'メール送信テストでエラーが発生しました。',
            'test_mail_subject' => 'メール送信テスト',
            'test_mail_body' => 'これは :app_name からのメール送信テストです。

メール設定が正常に動作しています。',
            'test_mail_success' => 'テストメールが正常に送信されました。受信トレイをご確認ください。',
            'test_mail_failed' => 'メール送信に失敗しました: :error',
            'maintenance_settings' => 'メンテナンスモード設定',
            'maintenance_mode' => 'メンテナンスモード',
            'maintenance_message' => 'メンテナンス中の表示メッセージ',
            'maintenance_message_help' => '※メンテナンスモード有効時にフロント画面で表示されます。',
            'save_confirmation_title' => '保存の確認',
            'save_confirmation_message' => '変更内容を保存しますか？',
            'save_button' => '保存',
            'cancel_button' => '戻る',
            'yes' => 'はい',
            'no' => 'いいえ',
            'submit' => '更新',
        ],
        // セキュリティ
        'security' => [
            'heading' => 'セキュリティ設定',
            'basic_security_settings' => '基本セキュリティ設定',
            'ip_access_control' => 'IPアクセス制御設定',
            'admin_url' => '管理画面URL',
            'enable_allowed_admin_ips' => '特定のIPアドレスのみアクセスを許可',
            'allowed_admin_ips' => '許可IPアドレス',
            'enable_blocked_admin_ips' => '特定のIPアドレスをブロック',
            'blocked_admin_ips' => 'ブロックIPアドレス',
            'force_ssl' => 'SSL強制',
            'recaptcha_settings' => 'reCAPTCHA設定',
            'captcha_enabled' => 'reCAPTCHAを有効にする',
            'captcha_driver' => 'CAPTCHAプロバイダー',
            'captcha_google_site_key' => 'Google reCAPTCHA サイトキー',
            'captcha_google_secret_key' => 'Google reCAPTCHA シークレットキー',
            'captcha_google_version' => 'reCAPTCHAバージョン',
            'captcha_google_min_score' => '最小スコア (0.0-1.0)',
            'captcha_min_score_description' => '0.0は最も疑わしく、1.0は最も信頼できることを示します。通常は0.5を推奨します。',
            'captcha_form_settings' => 'フォーム別設定',
            'captcha_contact_form' => 'お問い合わせフォーム',
            'captcha_registration_form' => '会員登録フォーム',
            'captcha_login_form' => 'ログインフォーム',
            'captcha_comment_form' => 'コメントフォーム',
            'captcha_version_options' => [
                'v3' => 'v3 (推奨 - 非対話型)',
                'v2_checkbox' => 'v2 チェックボックス',
                'v2_invisible' => 'v2 非表示',
            ],
            'save_confirmation_title' => '保存の確認',
            'save_confirmation_message' => '変更内容を保存しますか？',
            'save_button' => '保存',
            'back_button' => '戻る',
            'submit' => '更新',
        ],
        // メンバー
        'members' => [
            'form' => [
                'name' => '名前',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'role' => 'ロール',
                'appearance' => '外観モード',
                'status' => 'ステータス',
            ],
            'index' => [
                'heading' => 'メンバーマスター',
                'search_title' => '管理者検索',
                'search_placeholder' => 'ユーザー名やメールアドレスで検索',
                'search_button' => '検索',
                'table' => [
                    'id' => 'ID',
                    'name' => '名前',
                    'email' => 'メールアドレス',
                    'role' => '権限',
                    'actions' => '操作',
                    'edit' => '編集',
                    'unknown_role' => '不明',
                ],
            ],
            'create' => [
                'heading' => '新規メンバー作成',
                'name' => '名前',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'password_confirmation' => 'パスワード確認',
                'role' => 'ロール',
                'submit' => '登録',
                'create_button' => '作成',
                'create_confirmation_title' => '作成の確認',
                'create_confirmation_message' => '新規管理者を作成しますか？',
                'back_button' => '戻る',
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
                'description' => '説明',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'password_change_only' => 'パスワード(変更する場合のみ)',
                'password_confirmation' => 'パスワード確認',
                'appearance_mode' => '外観モード',
                'appearance_auto' => '自動',
                'appearance_light' => 'ライト',
                'appearance_dark' => 'ダーク',
                'login_notification_setting' => 'ログイン通知メールの設定',
                'two_factor_setting' => '2段階認証の設定',
                'always' => 'メンバー全体設定で「常に有効」にされているため、個別設定は変更できません。',
                'submit' => 'プロフィールを更新',
                'update_button' => '更新',
                'updated' => 'プロフィールが更新されました。',
                'confirm_title' => 'プロフィール更新の確認',
                'confirm_message' => 'プロフィールを更新しますか？',
                'confirm_label' => '更新',
                'cancel_label' => 'キャンセル',
            ],
            'settings' => [
                'heading' => 'メンバー全体設定',
                'password_conditions' => 'パスワードの条件',
                'login_notification_settings' => 'ログイン通知設定',
                'two_factor_settings' => '二段階認証設定',
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
                'login_notification_global_setting' => 'ログイン通知メールの全体設定',
                'two_factor_methods_label' => '利用可能な二段階認証の方法',
                'two_factor_methods_help' => 'ユーザーが利用できる二段階認証の方法を選択してください。最低1つは有効にする必要があります。',
                'update_button' => '更新',

            ],
            'roles' => [
                'heading' => '権限設定',
                'access_roles' => '編集権限（access_roles）',
                'view_roles' => '閲覧権限（view_roles）',
                'confirm_title' => '権限設定更新の確認',
                'confirm_message' => '権限設定を更新しますか？',
                'confirm_label' => '更新',
                'cancel_label' => 'キャンセル',
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
            'two_factor_method' => [
                'label' => '2段階認証方法',
                'options' => [
                    'email' => 'メール認証',
                    'device' => 'デバイス認証',
                    'biometric' => '生体認証',
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
                'available_themes' => '利用可能なテーマ',
                'currently_active' => '現在使用中',
                'activate_button' => '有効化',
                'delete_button' => '削除',
                'activate_confirm' => 'このテーマを有効化しますか？',
                'delete_confirm' => '本当に削除しますか？',
                'color' => 'カラー',
                'font' => 'フォント',
                'submit' => '更新',
            ],
            'install' => [
                'heading' => 'テーマインストール',
                'upload_title' => 'テーマをアップロード',
                'file_select_label' => 'ファイルを選択',
                'upload_button' => 'アップロード',
                'name' => 'テーマ名',
                'submit' => 'インストール',
            ],
        ],
        // プラグイン
        'plugins' => [
            'index' => [
                'heading' => 'プラグインマスター',
                'table' => [
                    'id' => 'ID',
                    'name' => 'プラグイン名',
                    'status' => '状態',
                    'actions' => '操作',
                ],
                'status' => [
                    'enabled' => '有効',
                    'disabled' => '無効',
                ],
                'buttons' => [
                    'enable' => '有効化',
                    'disable' => '無効化',
                    'uninstall' => 'アンインストール',
                ],
                'uninstall' => [
                    'confirm_title' => 'アンインストールの確認',
                    'confirm_message' => 'プラグイン [{name}] をアンインストールしますか？',
                    'confirm_button' => 'アンインストール',
                    'cancel_button' => 'キャンセル',
                    'remove_data_checkbox' => 'プラグインのインストール時に作成されたデータベースのテーブルを削除する。注意！テーブル削除するとプラグインで作成したデータが失われます！',
                ],
            ],
            'install' => [
                'heading' => 'プラグインインストール',
                'upload_title' => 'プラグインアップロード',
                'file_select_label' => 'ZIPファイルを選択:',
                'drag_drop_text' => 'ここにファイルをドラッグするか、クリックしてアップロード',
                'supported_format' => '対応形式:',
                'upload_limit' => 'アップロード可能ファイルサイズ上限:',
                'upload_button' => 'アップロードしてインストール',
                'enable_plugin_text' => 'プラグインを有効化する場合は',
                'enable_from_here' => 'こちら',
                'enable_instruction' => 'から有効化してください。',
                'name' => 'プラグイン名',
                'submit' => 'インストール',
            ],
        ],

        //システム情報
        'systems' => [
            'logs' => [
                'heading' => 'ログ情報',
                'log_type_label' => 'ログ種別：',
                'no_logs_found' => 'ログが見つかりません。',
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
