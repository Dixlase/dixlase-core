<?php

return [
    //index
    'title' => 'インストール',
    'welcome' => 'インストールへようこそ',
    'description' => 'インストールを開始する前に、サーバー要件を確認してください。',
    'server_requirements' => 'サーバー要件',
    'ok' => 'OK',
    'failed' => 'NG',
    'permissions' => [
        'storage' => 'storageディレクトリが書き込み可能',
        'cache' => 'bootstrap/cacheディレクトリが書き込み可能',
    ],
    'required' => '必須',
    'optional' => 'オプション',
    'not_required' => '必須ではありません',
    'required_issues' => '必須項目に問題があります。インストールを続行するには上記の問題を解決してください。',
    'start_button' => 'インストールを開始',
    'languages' => [
        'en' => 'English',
        'ja' => '日本語',
    ],

    //step 1
    'settings_title' => '基本設定',
    'settings_header' => 'インストール設定',
    'settings_description' => 'ソフトウェアの基本設定を行います。',
    'admin_name' => '管理者名',
    'admin_name_placeholder' => '半角英数字で入力（例: admin123）',
    'admin_name_requirements' => '3〜20文字の半角英数字のみ使用可能',
    'validation' => [
        'admin_name_required' => '管理者名を入力してください。',
        'admin_name_alpha_num' => '管理者名は半角英数字のみ使用できます。',
        'admin_name_length' => '管理者名は3〜20文字で入力してください。',
    ],
    'admin_email' => '管理者メールアドレス',
    'admin_password' => '管理者パスワード',
    'admin_password_confirmation' => '管理者パスワード確認',
    'admin_password_confirmation_note' => '確認のため、同じパスワードを手入力してください。',
    'password_requirements' => [
        'length' => '8文字以上',
        'uppercase' => '大文字を1文字以上含む',
        'lowercase' => '小文字を1文字以上含む',
        'number' => '数字を1文字以上含む',
        'symbol' => '記号（!@#$%^&* など）を含むと強度UP',
    ],
    'password_strength_messages' => [
        'weak' => '❌ 条件を満たしていません',
        'medium' => '⚠️ 普通',
        'strong' => '✅ 強い',
    ],
    'tooltip_generate' => 'パスワードを自動生成',
    'tooltip_copy' => 'パスワードをコピー',
    'tooltip_toggle' => 'パスワードの表示切り替え',
    'back' => '戻る',
    'next' => '次へ',

    //step 2
    // 環境設定
    'environment_title' => '環境設定',
    'environment_header' => 'アプリケーション環境の設定',
    'environment_description' => 'アプリケーションの動作環境を選択し、URLを設定してください。',

    'app_env' => 'アプリケーション環境',
    'app_env_options' => [
        'local' => 'ローカル',
        'staging' => 'ステージング',
        'production' => '本番',
    ],

    'app_debug' => 'デバッグモード',
    'enable_debug' => 'デバッグモードを有効にする',
    'app_debug_note' => '本番環境ではデバッグモードは選択できません。',

    'app_url' => 'アプリケーションURL',
    'app_url_note' => '現在のホストに基づいて自動的に設定されます。必要に応じて変更してください。',

    //step 3
    'security_title' => 'セキュリティ設定',
    'security_header' => 'セキュリティ設定',
    'security_description' => '管理画面のURLやIP制御を設定します。',
    'site_url' => 'サイトのURL（管理画面）',
    'force_ssl' => 'SSL（HTTPS）を強制する',
    'ip_restrictions' => 'IPアドレス制限',
    'enable_allowed_admin_ips' => '特定のIPアドレスのみ管理画面へのアクセスを許可',
    'enable_blocked_admin_ips' => '特定のIPアドレスを管理画面へのアクセス禁止',
    'enable_allowed_front_ips' => '特定のIPアドレスのみフロントへのアクセスを許可',
    'enable_blocked_front_ips' => '特定のIPアドレスをフロントへのアクセス禁止',
    'ip_note' => '複数のIPを入力する場合は改行で区切ってください。',
    'back' => '戻る',
    'next' => '次へ',

    //step 4
    'database_title' => 'データベース設定',
    'database_header' => 'データベース情報を入力してください',
    'database_description' => 'システムで使用するデータベースの設定を行います。',
    'db_connection' => 'データベースの種類',
    'db_host' => 'データベースホスト',
    'db_port' => 'データベースポート',
    'db_database' => 'データベース名',
    'db_username' => 'データベースユーザー名',
    'db_password' => 'データベースパスワード',
    'preserve_database' => 'データベースをリセットしない',
    'preserve_database_help' => 'チェックを入れると、既存のデータを保持したまま必要な更新のみを適用します。チェックを外すと、インストール時に既存のデータがすべて削除されます。',
    'test_db_connection' => '接続テスト',
    'db_connection_success' => 'データベース接続成功！',
    'db_connection_error' => 'データベース接続に失敗しました: :error',
    'db_test_required' => '⚠️ 次の画面へ進む前にデータベース接続テストを行ってください。',
    'db_test_success' => '✅ データベース接続が成功しました！次の画面へお進みください！',
    'tooltip_test_db' => 'DB接続テストを行ってください。',
    'db_password_required' => 'データベースパスワードは必須です。',
    'back' => '戻る',
    'next' => '次へ',

    //confirm
    'confirm_title' => 'インストール設定の確認',
    'confirm_header' => 'インストールの確認',
    'confirm_message' => 'インストールを確定する前に、設定を確認してください。',
    'confirm_description' => '上記の設定でインストールを行います。よろしいですか？',

    // サイト情報
    'site_name' => 'サイト名',
    'admin_email' => '管理者メールアドレス',
    'admin_password' => '管理者パスワード',

    // 管理画面設定
    'admin_url' => '管理画面URL',
    'force_ssl' => 'SSL（HTTPS）を強制する',
    'enabled' => '有効',
    'disabled' => '無効',

    // IP制限
    'ip_restrictions' => 'IP制限',
    'allowed_admin_ips' => '管理画面の許可IPアドレス',
    'blocked_admin_ips' => '管理画面のブロックIPアドレス',
    'allowed_front_ips' => 'フロントの許可IPアドレス',
    'blocked_front_ips' => 'フロントのブロックIPアドレス',
    'none' => 'なし',

    // データベース情報
    'db_connection' => 'データベース接続',
    'db_host' => 'データベースホスト',
    'db_port' => 'データベースポート',
    'db_name' => 'データベース名',
    'db_user' => 'データベースユーザー名',
    'db_password' => 'データベースパスワード',

    // ボタン
    'back_button' => '戻る',
    'confirm_button' => 'インストール',

    //complete
    'complete_title' => 'インストール完了',
    'complete_message' => 'インストールが正常に完了しました。以下のリンクからサイトまたは管理画面にアクセスしてください。',
    'go_to_site' => 'サイトへ移動',
    'go_to_admin' => '管理画面へログイン',
    'admin_login_url' => '管理画面ログインURL',

    //errors
    'password_strength_error' => 'パスワードは8文字以上で、大文字・小文字・数字を最低1つ含める必要があります。',
    'password_strength_weak' => 'パスワードが弱すぎます。',
    'password_strength_medium' => 'パスワードの強度は普通です。',
    'password_strength_strong' => 'パスワードは安全です。',

    // コピー＆ペースト制限
    'password_paste_error' => 'パスワードのコピー＆ペーストは禁止されています。手入力してください。',

    // タイムゾーン
    'timezone' => [
        'label' => 'タイムゾーン',
        // タイムゾーンの翻訳は timezones.php に移動しました
    ],
    'timezone_note' => 'アプリケーションのデフォルトタイムゾーンを選択してください。',
];
