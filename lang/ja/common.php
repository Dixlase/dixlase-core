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

    // 基本操作
    'create' => '作成',
    'add' => '追加',
    'edit' => '編集',
    'update' => '更新',
    'save' => '保存',
    'delete' => '削除',
    'copy' => 'コピー',
    'copied' => 'コピー済み',

    // フォーム操作
    'submit' => '送信',
    'reset' => 'リセット',
    'clear' => 'クリア',
    'cancel' => 'キャンセル',
    'confirm' => '確認',

    // ナビゲーション
    'back' => '戻る',
    'next' => '次へ',
    'close' => '閉じる',
    'finish' => '完了',
    'index' => '一覧',

    // 検索・表示
    'search' => '検索',
    'preview' => 'プレビュー',

    // ファイル操作
    'upload' => 'アップロード',
    'download' => 'ダウンロード',

    // システム操作
    'install' => 'インストール',
    'uninstall' => 'アンインストール',
    'enable' => '有効化',
    'disable' => '無効化',
    'execute' => '実行',

    // 認証
    'login' => 'ログイン',
    'logout' => 'ログアウト',

    // 確認・応答
    'yes' => 'はい',
    'no' => 'いいえ',
    'ok' => 'OK',

    // 状態・属性
    'required' => '必須',
    'optional' => '任意',
    'default_method' => 'デフォルト',
    'enabled' => '有効',
    'disabled' => '無効',
    'available_methods' => '利用可能な方法',
    
    // テーマ
    'auto' => '自動',
    'light' => 'ライト',
    'dark' => 'ダーク',

    // 言語
    'ja' => '日本語',
    'en' => '英語',
    
    // アカウント種別
    'account_types' => [
        'member' => 'メンバー',
        'user' => 'ユーザー',
    ],

    // ロール・権限
    'roles' => [
        'super_admin' => '特権管理者',
        'admin' => '管理者',
        'editor' => '編集者',
        'author' => '投稿者',
        'contributor' => '寄稿者',
        'receptionist' => '受付',
        'guest' => 'ゲスト',
    ],
    'permissions' => '権限',
    'role' => '権限',


    // 基本的な属性
    // 基本情報
    'id' => 'ID',
    'name' => '名前',
    'title' => 'タイトル',
    'description' => '説明',
    'value' => '値',
    'status' => '状態',

    // 認証情報
    'email' => 'メールアドレス',
    'password' => 'パスワード',
    'password_confirmation' => 'パスワード確認',

    // 連絡先情報
    'address' => '住所',
    'phone' => '電話番号',
    'fax' => 'FAX',
    'url' => 'URL',

    // 個人情報
    'gender' => '性別',
    'birthday' => '誕生日',

    // ファイル関連
    'file_name' => 'ファイル名',
    'file_type' => 'ファイルタイプ',
    'upload_date' => 'アップロード日時',
    'uploaded_by' => 'アップロードしたメンバー',

    // コンテンツ関連
    'content' => 'コンテンツ',
    'slug' => 'スラッグ',
    'published_at' => '公開日時',

    // デザイン・表示
    'color' => 'カラー',
    'font' => 'フォント',
    'admin_theme' => '管理画面テーマ',
    'appearance_mode' => '外観モード',

    // システム設定
    'site_name' => 'サイト名',
    'locale' => '言語',
    'timezone' => 'タイムゾーン',
    'maintenance_mode' => 'メンテナンスモード',
    'maintenance_message' => 'メンテナンスメッセージ',

    // UI要素
    'actions' => '操作',
    'required_fields' => '必須項目',

    // メッセージ・状態
    'warning' => '注意',
    'info' => '情報',
    'error' => 'エラー',
    'unknown' => '不明',
    
    // 時間単位
    'created_at' => '作成日時',
    'updated_at' => '更新日時',
    'deleted_at' => '削除日時',
    'minutes' => '分',
    'hours' => '時間',
    'days' => '日',

    'search_keyword' => 'キーワード',
    'role_filter' => '権限',
    'status_filter' => 'ステータス',
    'clear_button' => 'クリア',
    
    // Filter Options
    'filters' => [
        'all_roles' => 'すべての権限',
        'all_statuses' => 'すべてのステータス',
        'search_keyword' => 'キーワード',
        'role_filter' => '権限フィルター',
        'status_filter' => 'ステータスフィルター',
        'clear_button' => 'クリア',
    ],

    // ログ関連
    'operation' => '操作',
    'method' => 'メソッド',
    'uri' => 'URI',
    'controller' => 'コントローラー',
    'user_agent' => 'ユーザーエージェント',
    'time' => '時間',

    // ===========================================
    // 二段階認証関連（統合セクション）
    // ===========================================
    'two_factor_authentication' => '二段階認証',
    'two_factor_settings' => '二段階認証設定',
    
    // 二段階認証モード
    'two_factor_mode' => [
        'options' => [
            0 => '無効',
            1 => '新しいデバイスのみ',
            2 => '常に有効',
            3 => 'プロフィール設定に従う',
        ],
    ],
    
    // 二段階認証モードオプション（プロフィール用）
    'two_factor_mode_options' => [
        'disabled' => '無効',
        'only_new_device' => '異なる端末/IP時のみ有効',
        'always' => '常に有効',
    ],
    
    // 二段階認証方法
    'two_factor_method' => [
        'label' => '2段階認証方法',
        'options' => [
            'email' => 'メール認証',
            'device' => 'デバイス認証',
            'biometric' => '生体認証',
            'use_profile_setting' => ':account_typeのプロフィール設定を反映',
        ],
        // 数値キー版（管理画面設定用）
        'numbered_options' => [
            0 => 'メール認証',
            1 => 'デバイス認証',
            2 => '生体認証',
            3 => 'プロフィール設定に従う',
        ]
    ],
    
    // 二段階認証ヘルプテキスト
    'two_factor_help' => '二段階認証を使用するタイミングを設定します。',
    'two_factor_method_help' => '二段階認証で使用する認証方法を選択してください。',
    'two_factor_global_setting_fixed' => 'この設定は全体設定により固定されています。',
    'two_factor_method_global_setting_fixed' => 'この認証方法は全体設定により固定されています。',
    
    // 二段階認証方法のヘルプテキスト（詳細）
    'two_factor_method_help_detailed' => [
        'single' => 'この認証方法が:account_type全体設定で有効になっています。',
        'multiple' => '使用する認証方法を選択してください。:account_type全体設定で有効にされている方法から選択できます。',
        'email' => '登録済みのメールアドレスに認証コードを送信します。',
        'device' => '登録済みのデバイスで認証を行います。',
        'biometric' => '指紋や顔認証などの生体認証を使用して認証を行います。',
    ],
    'login_notification_mode' => [
        'label' => 'ログイン通知の設定',
        'options' => [
            0 => '無効',
            1 => '異なる端末/IP時のみ有効',
            2 => '常に有効',
            3 => ':account_typeのプロフィール設定を反映',
        ]
    ],


    // ステータス説明（詳細版）
    'status_descriptions' => [
        'draft_description' => '下書き状態です。公開されません。',
        'published_description' => '即座に公開されます。',
        'scheduled_description' => '指定した日時に公開されます。',
    ],


    
    // ログイン通知の汎用ヘルプテキスト
    'login_notification' => 'ログイン通知',
    'notification_settings' => '通知設定',
    'login_notification_mode' => 'ログイン通知の設定',
    'login_notification_help' => 'ログイン通知を送信するタイミングを設定します。',

    // 全体設定による制御メッセージ（アカウント種別対応）
    'global_setting_controlled' => [
        'two_factor' => 'この設定は:account_type全体設定で制御されており、変更できません。',
        'two_factor_method' => 'この認証方法は:account_type全体設定で制御されており、変更できません。',
    ],

    // プロフィール・設定関連の汎用項目

    // 設定セクション（汎用）
    'basic_info' => '基本情報',
    'password_settings' => 'パスワード設定',
    'security_settings' => 'セキュリティ設定',
    'account_settings' => 'アカウント設定',
    'management_operations' => '管理操作',
    'appearance_settings' => '外観設定',
    'language_settings' => '言語設定',

    // 認証方法関連
    'available_methods' => '利用可能な方法',

    // 確認ダイアログ
    'save_confirmation' => '保存の確認',
    'update_confirmation' => '更新の確認',
    'create_confirmation' => '作成の確認',
    'delete_confirmation' => '削除の確認',
    
    // 保存確認ダイアログ（詳細版）
    'save_confirmation_title' => '保存の確認',
    'save_confirmation_message' => '変更内容を保存しますか？',
    'update_confirmation_title' => '更新の確認',
    'update_confirmation_message' => 'この内容で設定を更新しますか？',
];
