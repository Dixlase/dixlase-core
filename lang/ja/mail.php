<?php

return [
    'mailers' => [
        'smtp' => 'SMTP（標準）',
        'sendmail' => 'Sendmail',
        'log' => 'Log（ログ出力）',
        'array' => 'Array（配列保存）',
        'failover' => 'Failover（冗長化）',
        'mailgun' => 'Mailgun（外部）',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ],
    'encryptions' => [
        '' => 'なし',
        'tls' => 'TLS（推奨）',
        'ssl' => 'SSL',
    ],
    
    // パスワードリセットメール
    'reset_password' => [
        'subject' => 'パスワードリセット通知',
        'greeting' => 'こんにちは！',
        'line1' => 'アカウントのパスワードリセット要求を受け取ったため、このメールをお送りしています。',
        'action' => 'パスワードをリセット',
        'line2' => 'このパスワードリセットリンクは :count 分後に期限切れになります。',
        'line3' => 'パスワードリセットを要求していない場合は、何もする必要はありません。',
        'regards' => 'よろしくお願いいたします',
    ],

    // ログイン通知メール
    'login_notification' => [
        'subject_user' => '【ログイン通知】:nameさん、ログインがありました',
        'subject_system' => '【システム通知】管理画面へのログインがありました',
        'title' => 'ログイン通知',
        'user_message' => ':nameさん、ログインがありました。',
        'system_message' => 'システム通知',
        'details_title' => 'ログイン詳細:',
        'datetime' => '日時:',
        'ip_address' => 'IPアドレス:',
        'user_agent' => 'User-Agent:',
        'security_notice' => 'もしこのログインに心当たりがない場合は、すぐにパスワードを変更してください。',
        'regards' => 'よろしくお願いいたします。',
    ],

    // 二段階認証メール
    'two_factor' => [
        'default' => [
            'subject' => '【:app_name】二段階認証コード',
            'greeting' => 'こんにちは！',
            'message' => 'ログインのための二段階認証コードをお送りします。',
            'instructions' => 'このコードをログイン画面で入力してください。コードの有効期限は10分間です。',
            'security_notice' => 'このログインに心当たりがない場合は、すぐにパスワードを変更してください。',
            'regards' => 'よろしくお願いいたします',
        ],
        'admin' => [
            'subject' => '【:app_name】管理画面二段階認証コード',
            'greeting' => 'こんにちは！',
            'message' => '管理画面ログインのための二段階認証コードをお送りします。',
            'instructions' => 'このコードを管理画面のログイン画面で入力してください。コードの有効期限は10分間です。',
            'security_notice' => 'このログインに心当たりがない場合は、すぐにパスワードを変更し、システム管理者に連絡してください。',
            'regards' => 'よろしくお願いいたします',
        ],
        'user' => [
            'subject' => '【:app_name】ユーザー二段階認証コード',
            'greeting' => 'こんにちは！',
            'message' => 'ユーザーログインのための二段階認証コードをお送りします。',
            'instructions' => 'このコードをログイン画面で入力してください。コードの有効期限は10分間です。',
            'security_notice' => 'このログインに心当たりがない場合は、すぐにパスワードを変更してください。',
            'regards' => 'よろしくお願いいたします',
        ],
    ],

    // メール設定・テスト共通
    'settings' => [
        'mailer' => 'Mailer',
        'mail_host' => 'ホスト名',
        'mail_port' => 'ポート番号',
        'mail_username' => 'ユーザー名',
        'mail_password' => 'パスワード',
        'mail_encryption' => '暗号化方式',
        'mail_from_address' => '送信元メールアドレス',
        'mail_test' => 'メール送信テスト',
        'mail_test_description' => '現在の設定でテストメールを送信します。送信元メールアドレス宛にテストメールが送信されます。',
        'mail_test_description_2' => 'メール送信機能を有効するには、必ず接続テストとメール送信テストを実行してください。',
        'test_connection_button' => '接続テスト',
        'test_mail_button' => 'テストメール送信',
        'testing_connection' => '接続中...',
        'testing_mail' => '送信中...',
        'mail_test_error' => 'メール送信テストでエラーが発生しました。',
        'last_test_date' => '最終テスト日時',
        'mail_server_warning' => 'メールサーバー未設定',
        'mail_server_warning_message' => 'メールサーバーの設定とテストが未実行のため、メール送信機能が利用できません。',
        'mail_server_test_passed' => 'メールサーバー接続テスト合格済み。メール送信機能が利用できます。',
        'save_settings_reminder' => '設定を保存してください',
        'save_settings_reminder_message' => '変更を有効にするため、必ず設定を保存してください。',
    ],

    // メールテスト機能
    'test' => [
        'title' => 'メールテスト',
        'description' => 'メールサーバーの設定をテストします。',
        'description_admin_email' => '管理者メールアドレス宛にテストメールが送信されます。',
        'three_stage_test_incomplete' => 'メールテストが未完了です',
        'three_stage_test_complete' => 'メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
        'test_passed' => 'テスト合格',
        'test_not_completed' => '未実行',
        'mail_test_complete' => 'メール機能テスト完了',
        'mail_test_incomplete' => 'メール機能テスト未完了',
        'mail_test_warning_features' => 'メンバー全体設定のロックアウト通知、パスワードリセット、ログイン通知、二段階認証機能を使用するには、すべてのメールテストを完了してください。',
        'mail_test_warning_temporary' => 'テスト結果は一時的に保存されます。更新ボタンを押すまで、設定やテスト結果は保存されません。',
        'mail_receive_test_completed' => 'メール受信テストが完了しました。設定を保存してください。',
        'connection_test_required' => '接続テストを先に実行してください。',
    ],

    // テストメール内容
    'test_mail' => [
        'subject' => 'メール送信テスト',
        'greeting' => 'こんにちは！',
        'body' => ':app_name からのテストメールです。

メール送信テストが正常に完了しました。
メール受信確認を完了するには、以下のリンクをクリックしてください：

:verification_url

このリンクをクリックすることで、メール機能の完全なテストが完了します。',
        'body_with_verification' => ':app_name からのテストメールです。

メール送信テストが正常に完了しました。
メール受信確認を完了するには、以下のリンクをクリックしてください：

:verification_url

このリンクをクリックすることで、メール機能の完全なテストが完了します。',
        'test_details_title' => 'メール送信テスト',
        'app_name' => 'アプリケーション名:',
        'test_datetime' => 'テスト実行日時:',
        'verification_required' => 'このメールが正常に受信できているかを確認するため、以下のボタンをクリックしてください。',
        'verify_button' => 'メール受信を確認',
        'manual_verification' => 'ボタンが機能しない場合は、以下のURLを直接ブラウザにコピーしてアクセスしてください:',
        'regards' => 'よろしくお願いいたします。',
        'success' => 'テストメールが正常に送信されました。受信トレイをご確認ください。',
        'failed' => 'メール送信に失敗しました: :error',
    ],

    // メール受信確認成功ページ
    'verification_success' => [
        'title' => 'メール受信確認完了',
        'heading' => 'メール受信確認が完了しました',
        'description' => 'メール機能のテストが正常に完了しました。',
        'actions' => 'アクション',
        'already_verified_heading' => 'メール受信確認済み',
        'already_verified_description' => 'このメールの受信確認は既に完了しています。',
        'next_steps_title' => '次の手順',
        'next_steps' => [
            'close_window' => 'このウィンドウを閉じてください',
            'save_settings' => '設定を保存してテスト結果を確定してください',
            'data_saved' => 'データが保存されました',
        ],
        'next_steps_install' => [
            'close_window' => 'このウィンドウを閉じてください',
            'continue_install' => 'インストールを続行してください',
        ],
        'important_notice_title' => '重要なお知らせ',
        'important_notice' => 'テスト結果は一時的なものです。設定を保存するまで確定されません。',
        'close_button' => 'ウィンドウを閉じる',
        'completed_message' => 'メール受信確認が完了しました。',
    ],

    // メール受信確認エラーページ
    'verification_error' => [
        'title' => 'メール受信確認エラー',
        'heading' => 'メール受信確認でエラーが発生しました',
        'invalid_token_description' => 'このメール認証リンクは無効または期限切れです。',
        'verification_error_description' => 'メール受信確認の処理中にエラーが発生しました。',
        'general_error_description' => '予期しないエラーが発生しました。',
        'solution_title' => '対処方法',
        'actions' => 'アクション',
        'solution_steps' => [
            'このウィンドウを閉じてください',
            '基本設定画面で新しいテストメールを送信してください',
            '新しいメール内のリンクから受信確認を行ってください',
        ],
        'close_button' => 'ウィンドウを閉じる',
        'error_occurred' => 'メール受信確認でエラーが発生しました。',
    ],

    // コントローラーメッセージ
    'controller_messages' => [
        'settings_updated' => '設定が更新されました。',
        'test_session_cleared' => 'テストセッションがクリアされました。',
        'mailer_not_supported' => 'メーラー ":mailer" は接続テストをサポートしていません。',
        'connection_success' => 'メールサーバーへの接続が正常に確認されました。',
        'connection_failed' => 'メールサーバーへの接続に失敗しました: :error',
        'verification_token_invalid' => 'メール確認トークンが無効です。',
        'verification_error' => 'メール確認中にエラーが発生しました: :error',
    ],

    // メールサーバー設定フィールド (共通)
    'server_settings' => [
        'mailer' => 'メーラー',
        'mail_host' => 'ホスト',
        'mail_port' => 'ポート',
        'mail_username' => 'ユーザー名',
        'mail_password' => 'パスワード',
        'mail_encryption' => '暗号化',
        'mail_from_address' => '送信元メールアドレス',
        'mail_from_name' => '送信元名',
    ],

    // メールテスト機能 (共通)
    'test_functions' => [
        'test_connection_button' => '接続テスト',
        'test_mail_button' => 'メール送信テスト',
        'testing' => 'テスト中',
        'testing_connection' => '接続中...',
        'testing_mail' => '送信中...',
        'mail_test_description' => 'メールサーバーの接続とメール送信をテストできます。',
        'mail_test_description_2' => 'メール送信機能を有効するには、必ず接続テストとメール送信テストを実行してください。',
        'connection_test_error' => '接続テストでエラーが発生しました。メールサーバーの設定が正しいかご確認ください。',
        'mail_send_test_error' => 'テストメールの送信に失敗しました: :error',
        'mail_send_test_success' => 'テストメールを :email に送信しました。',
        'mail_send_test_failed' => 'テストメールの送信に失敗しました: :error',
        'connection_test_not_supported' => ':mailer メーラーは接続テストに対応していません。',
        'connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'connection_test_failed' => 'メールサーバーへの接続に失敗しました',
        'mail_connection_test_not_supported' => ':mailer メーラーは接続テストに対応していません。',
        'mail_connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'mail_connection_test_failed' => 'メールサーバーへの接続に失敗しました: :error',
        'send_test_success' => 'テストメールを :email に送信しました。',
        'send_test_failed' => 'テストメールの送信に失敗しました',
        'test_mail_success' => 'テストメールが正常に送信されました。受信トレイをご確認し、メール内のリンクから受信確認を完了させてください。',
        'test_mail_failed' => 'メール送信に失敗しました: :error',
        'three_stage_test_incomplete' => 'メールテストが未完了です',
        'three_stage_test_complete' => 'メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
    ],

    // メール受信確認機能 (共通)
    'verification' => [
        'verification_token_invalid' => 'メール認証トークンが無効です。',
        'verification_error' => 'メール認証処理中にエラーが発生しました: :error',
        'verification_success' => [
            'title' => 'メール受信確認完了',
            'heading' => 'メール受信確認が完了しました',
            'description' => 'メールサーバーの設定が正しく動作していることが確認されました。',
            'next_steps_title' => '次の手順',
            'next_steps' => [
                'close_window' => 'このウィンドウを閉じてください',
                'continue_install' => 'インストール画面に戻って設定を続行してください',
                'save_settings' => '設定を保存してください',
                'data_saved' => 'データは一時的に保存されています',
            ],
            'close_button' => 'ウィンドウを閉じる',
            'completed_message' => 'メール受信確認が完了しました'
        ],
    ],

    // バリデーションメッセージ (共通)
    'validation' => [
        'mail_mailer_required' => 'メーラーを選択してください。',
        'mail_host_required' => 'メールホストを入力してください。',
        'mail_port_required' => 'メールポートを入力してください。',
        'mail_port_numeric' => 'メールポートは数値で入力してください。',
        'mail_from_address_email' => '送信元アドレスは有効なメールアドレスである必要があります。',
    ],

    // JavaScript用メッセージ (共通)
    'js_messages' => [
        'test_route_not_set' => 'テストルートが設定されていません',
        'mail_test_route_not_set' => 'メールテストルートが設定されていません',
        'connection_test_first' => '先に接続テストを実行してください',
        'testing' => 'テスト中...',
        'mail_test_failed_side_note' => 'メールサーバーの設定が正しいか、メールサーバーの動作状況をご確認ください。',
        'connection_test_success_default' => '接続テストが成功しました',
        'connection_test_failed_default' => '接続テストが失敗しました',
        'mail_test_success_default' => 'メール送信テストが成功しました',
        'mail_test_failed_default' => 'メール送信テストが失敗しました。',
        'connection_test_error' => '接続テストでエラーが発生しました。',
        'mail_test_error' => 'メール送信テストでエラーが発生しました。',
        'mail_receive_test_completed' => 'メール受信テスト完了を検出',
        'mail_receive_verified' => 'メール受信確認が完了しました',
    ],

    // 3段階メールテスト機能
    'test_advanced' => [
        'test_email_subject' => 'メールサーバー設定テスト',
        'test_email_body' => 'これはメールサーバー設定のテストメールです。このメールが正常に受信できた場合、メールサーバーの設定が正しく動作しています。',
        'test_email_body_with_verification' => "これはメールサーバー設定のテストメールです。このメールが正常に受信できた場合、メールサーバーの設定が正しく動作しています。\n\nメール受信確認を完了するには、以下のリンクをクリックしてください：\n:verification_url\n\nこのリンクをクリックすることで、メール受信テストが完了します。",
        'connection_test_not_supported' => ':mailer メーラーは接続テストをサポートしていません。',
        'connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'connection_test_failed' => 'メールサーバーへの接続に失敗しました',
        'smtp_connection_error' => '接続エラー: :error (エラーコード: :errno)',
        'smtp_response_invalid' => 'SMTPサーバーからの応答が不正です: :response',
        'smtp_starttls_failed' => 'STARTTLS の開始に失敗しました: :response',
        'smtp_tls_crypto_failed' => 'TLS暗号化の有効化に失敗しました',
        'smtp_auth_login_failed' => 'AUTH LOGIN コマンドが失敗しました: :response',
        'smtp_username_auth_failed' => 'ユーザー名認証が失敗しました: :response',
        'smtp_password_auth_failed' => 'パスワード認証が失敗しました: :response',
        'send_test_success' => 'テストメールを :email に送信しました。',
        'send_test_failed' => 'テストメールの送信に失敗しました',
        'verification_token_invalid' => 'メール確認トークンが無効です。',
        'verification_error' => 'メール確認中にエラーが発生しました: :error',
        'verification_success' => [
            'title' => 'メール受信確認完了',
            'heading' => 'メール受信確認が完了しました',
            'description' => 'メールサーバーの設定が正しく動作していることが確認されました。',
            'next_steps_title' => '次のステップ',
            'next_steps' => [
                'close_window' => 'このウィンドウを閉じる',
                'continue_install' => 'インストール画面に戻って設定を続行する'
            ],
            'close_button' => 'ウィンドウを閉じる',
            'completed_message' => 'メール受信確認が完了しました'
        ],
        'three_stage_test_incomplete' => '3段階メールテストが未完了です',
        'three_stage_test_complete' => '3段階メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
    ],

    // ロックアウト通知メール
    'lockout_notification' => [
        'subject' => '【セキュリティ警告】管理画面ログインロックアウト発生',
        'title' => '管理画面ログインロックアウト通知',
        'message' => '管理画面でログインロックアウトが発生しました。不正なログイン試行の可能性があります。',
        'details' => 'ロックアウト詳細',
        'identifier' => 'メールアドレス',
        'ip_address' => 'IPアドレス',
        'user_agent' => 'ユーザーエージェント',
        'timestamp' => '発生日時',
        'settings' => 'ロックアウト設定',
        'max_attempts' => '最大試行回数',
        'time_window' => '時間枠',
        'lockout_duration' => 'ロックアウト期間',
        'times' => '回',
        'minutes' => '分',
        'action_required' => 'セキュリティ上の理由により、このログインロックアウトを確認し、必要に応じて適切な対応を行ってください。',
        'thanks' => 'よろしくお願いいたします',
    ],

    // メール認証
    'member_verify_email' => [
        'subject' => 'メールアドレスの確認',
        'greeting' => ':nameさん、こんにちは！',
        'message' => 'アカウントの登録が完了しました。以下のボタンをクリックして、メールアドレスを確認してください。',
        'action' => 'メールアドレスを確認',
        'manual_verification' => 'ボタンをクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください:',
        'expiration' => 'この確認リンクは:minutes分後に期限切れになります。',
        'regards' => 'よろしくお願いいたします',
    ],
];
