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
        'next_steps_title' => '次の手順',
        'next_steps' => [
            'close_window' => 'このウィンドウを閉じてください',
            'save_settings' => '基本設定画面で「更新」ボタンを押して設定を保存してください',
            'data_saved' => 'テスト結果が保存され、メール機能が有効になります',
        ],
        'important_notice_title' => '重要な注意事項',
        'important_notice' => 'テスト結果は一時的に保存されています。必ず設定を保存してください。',
        'close_button' => 'ウィンドウを閉じる',
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
];
