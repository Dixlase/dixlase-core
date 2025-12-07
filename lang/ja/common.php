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
    'all' => 'すべて',
    'none' => 'なし',
    
    // ラベル
    'required' => '必須',
    'optional' => '任意',

    // フォーム操作
    'submit' => '送信',
    'send' => '送信',
    'sending' => '送信中',
    'reset' => 'リセット',
    'clear' => 'クリア',
    'cancel' => 'キャンセル',
    'confirm' => '確認',

    // ナビゲーション
    'back' => '戻る',
    'next' => '次へ',
    'close' => '閉じる',
    'index' => '一覧',
    'select' => '選択',
    'search' => '検索',
    'loading' => '読み込み中',
    'new' => '新規作成',
    'view_site' => 'サイトを表示',
    'design' => 'デザイン',

    // システム操作
    'settings' => '設定',
    'profile' => 'プロフィール',
    'language_timezone' => '言語・タイムゾーン設定',

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
    'error_occurred' => 'エラーが発生しました',

    // 状態・属性
    'required' => '必須',
    'optional' => '任意',
    'default_method' => 'デフォルト',
    'enabled' => '有効',
    'disabled' => '無効',
    'active' => '有効',
    'inactive' => '無効',
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
    'details' => '詳細',
    'version' => 'バージョン',
    'author' => '作者',
    'license' => 'ライセンス',
    'unknown' => '不明',
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
    
    // メディア関連
    'select_media' => 'メディアを選択',
    'all_types' => 'すべてのタイプ',
    'images' => '画像',
    'videos' => '動画',
    'documents' => 'ドキュメント',
    'items_selected' => '件選択中',
    'no_media_found' => 'メディアが見つかりません',
    'error_loading_media' => 'メディアの読み込みに失敗しました',
    'caption' => 'キャプション',
    'caption_placeholder' => '画像のキャプションを入力',
    'alt_text' => '代替テキスト',
    'alt_text_placeholder' => '画像の代替テキストを入力（アクセシビリティ用）',
    'description_placeholder' => 'メディアの詳細説明を入力',

    // コンテンツ関連
    'content' => 'コンテンツ',
    'slug' => 'スラッグ',
    'slug_help' => 'URLに使用されます（例: /pages/about-us）。空の場合はタイトルから自動生成されます。',
    'auto_generate' => '自動生成',
    'published_at' => '公開日時',
    'page_url' => 'ページURL',

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
    'preview' => 'プレビュー',

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
    'admin_logs' => '管理画面ログ',
    'front_logs' => 'フロントエンドログ',
    'error_log' => 'エラーログ',
    'login_log' => 'ログインログ',

    // ===========================================
    // 二段階認証関連（サイト全体で汎用的なもののみ）
    // ===========================================
    'two_factor_authentication' => '二段階認証',
    'two_factor_settings' => '二段階認証設定',
    'two_factor_global_setting_fixed' => 'この設定は全体設定により固定されています。',
    'two_factor_method_global_setting_fixed' => 'この認証方法は全体設定により固定されています。',
    
    // 二段階認証モード（数値キー版）
    'two_factor_mode' => [
        'label' => '二段階認証設定',
        'options' => [
            0 => '無効',
            1 => '異なるデバイス・IPでのログイン時',
            2 => '有効',
            3 => 'メンバーのプロフィール設定に従う',
        ]
    ],
    
    // 二段階認証方法（数値キー版）
    'two_factor_method' => [
        'label' => '二段階認証方法',
        'numbered_options' => [
            0 => 'メール認証',
            1 => 'Passkey（生体認証）',
        ],
        'options' => [
            'email' => 'メール認証',
            'passkey' => 'Passkey認証(生体認証)',
        ]
    ],
    
    // 二段階認証ヘルプテキスト
    'two_factor_help' => '二段階認証を有効にすると、ログイン時に追加の認証が必要になります。<br>万が一パスワードが漏れても第三者によるアクセスを防ぎ、アカウントのセキュリティが大幅に向上します。',
    'two_factor_method_help' => [
        'single' => ':account_typeの全体設定により、認証方法が固定されています。',
        'multiple' => ':account_typeの全体設定により、利用可能な認証方法から選択できます。',
    ],
    
    'login_notification_mode' => [
        'label' => 'ログイン通知の設定',
        'options' => [
            0 => '無効',
            1 => '異なる端末/IP時のみ有効',
            2 => '有効',
            3 => ':account_typeのプロフィール設定を反映',
        ]
    ],


    // ステータス説明（詳細版）
    'status_descriptions' => [
        'draft_description' => '下書き状態です。公開されません。',
        'published_description' => '即座に公開されます。',
    ],

    // ログイン通知
    'login_notification' => 'ログイン通知',
    'login_notification_mode' => [
        'label' => 'ログイン通知モード',
        'help' => 'ログイン時にメール通知を送信するタイミングを設定します。',
        'options' => [
            0 => '無効',
            1 => '新しいデバイスのみ',
            2 => '常に通知',
            3 => 'メンバーのプロフィール設定に従う',
        ],
    ],
    'notification_settings' => '通知設定',

    // 全体設定による制御メッセージ（アカウント種別対応）
    'global_setting_fixed' => [
        'two_factor' => 'この設定は:account_type全体設定で制御されており、変更できません。',
        'two_factor_method' => 'この認証方法は:account_type全体設定で制御されており、変更できません。',
    ],

    // プロフィール・設定関連の汎用項目

    // 基本項目
    'basic_info' => '基本情報',
    'name' => '名前',
    'description' => '説明',
    'email' => 'メールアドレス',
    'password_settings' => 'パスワード設定',
    'security_settings' => 'セキュリティ設定',
    'account_settings' => 'アカウント設定',
    'management_operations' => '管理操作',
    'appearance_settings' => '外観設定',

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

    // バリデーション用の属性名（他のページでも使用される汎用的な項目）
    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'new_password_confirmation' => '新しいパスワード確認',
        'appearance' => '外観設定',
        'login_notification_mode' => 'ログイン通知設定',
        'two_factor_mode' => '二段階認証設定',
        'two_factor_method' => '二段階認証方法',
        'title' => 'タイトル',
        'content' => '内容',
        'description' => '説明',
    ],

    // 言語
    'language' => '言語',
    'languages' => [
        'ja' => '日本語',
        'en' => 'English',
    ],

    // コンテンツ保存方法
    'content_storage' => [
        'label' => 'コンテンツの保存方法',
        'database' => 'データベース',
        'database_description' => 'DBに保存します。管理画面から直接編集できます。',
        'file' => 'ファイル',
        'file_description' => 'ファイルとして保存します。ローカルエディタで直接編集できます。',
        'file_info_title' => 'ファイル保存について',
        'file_info_description' => 'コンテンツはファイルとして保存されます。以下のパスで直接編集できます：',
        'gui_db_only' => 'GUIエディタはデータベース保存のみ対応',
    ],

    // コンテンツエディタ
    'content_editor' => [
        'label' => 'エディタータイプ',
        'gui' => 'GUIエディタ',
        'gui_description' => 'ドラッグ&ドロップで直感的に編集（将来実装予定）',
        'gui_coming_soon' => 'GUIエディタは近日実装予定です',
        'markdown' => 'Markdown',
        'markdown_description' => 'Markdown記法で記述。プレビュー機能付き。',
        'markdown_editor' => 'Markdownエディタ',
        'html' => 'HTML',
        'html_description' => 'HTMLタグを直接記述。完全な制御が可能。',
        'blade' => 'Blade',
        'blade_description' => 'Laravel Blade記法で記述。動的コンテンツ対応。',
        'blade_warning' => '⚠️ Bladeテンプレートは強力ですが、セキュリティリスクがあります。信頼できる管理者のみが使用してください。',
        'preview' => 'プレビュー',
    ],

    // メタ情報
    'meta_description' => 'メタディスクリプション',
    'ogp_image' => 'OGP画像',
];
