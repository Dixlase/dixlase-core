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
        'user_id' => 'ユーザーID:',
        'security_notice' => 'もしこのログインに心当たりがない場合は、すぐにパスワードを変更してください。',
        'access_site' => 'サイトにアクセス',
        'regards' => 'よろしくお願いいたします。',
    ],

    // 二段階認証メール
    'two_fa' => [
        // 共通メッセージ
        'security_notice' => 'このログインに心当たりがない場合は、第三者によってログインが試行された可能性があります。  
不正アクセスのリスクがありますので、至急、パスワードを変更するか、システム管理者にお問い合わせください。',
        'regards' => 'よろしくお願いいたします。',
        'details_title' => 'ログイン試行の詳細',
        'ip_address' => 'IPアドレス',
        'user_agent' => 'ブラウザ/デバイス',
        'timestamp' => '日時',
        
        // メール認証
        'email' => [
            'subject' => '【:app_name】二段階認証コード',
            'greeting' => 'こんにちは！',
            'message' => 'ログインのための二段階認証コードをお送りします。',
            'instructions' => 'このコードをログイン画面で入力してください。コードの有効期限は10分間です。',
        ],
        
        // デバイス認証承認メール
        'device' => [
            'subject' => 'ログイン承認リクエスト',
            'title' => 'ログイン承認が必要です',
            'greeting' => ':name 様',
            'message' => 'あなたのアカウントへのログインが試行されました。このログインを承認する場合は、下のボタンをクリックしてください。',
            'action_prompt' => 'このログインを承認しますか？',
            'approve_button' => 'ログインを承認する',
            'deny_button' => 'ログインを拒否する',
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
        'connection_test_required' => 'メール送信テストを実行する前に、まず接続テストを完了させてください。',
        'member_not_found' => 'ログイン中のメンバーが見つかりません。再度ログインしてください。',
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

    // メール認証（共通）
    'verify_email' => [
        'subject' => 'メールアドレスの確認',
        'subject_account' => ':typeアカウントの確認',
        'greeting' => ':nameさん、こんにちは！',
        'message_create' => 'ご登録ありがとうございます。以下のボタンをクリックして、メールアドレスの認証を完了してください。',
        'message_email_change' => 'メールアドレスが変更されました。以下のボタンをクリックして、メールアドレスの変更を完了させてください。',
        'message_resend' => 'メールアドレスの認証が必要です。以下のボタンをクリックして、認証を完了してください。',
        'action_verify_account' => 'メールアドレスを認証',
        'action_change_email' => 'メールアドレスを変更',
        'manual_verification' => 'ボタンをクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください:',
        'expiration' => 'この確認リンクは:minutes分後に期限切れになります。',
        'security_notice' => '【重要】このメールに心当たりがない場合は、このメールを無視してください。あなたが認証リンクをクリックしない限り、アカウントは有効化されません。第三者がこのメールアドレスを誤って登録した可能性がありますが、あなたの個人情報が漏洩することはありません。',
        'regards' => 'よろしくお願いいたします',
    ],

    // メンバー本人への認証完了通知
    'member_verification_completed' => [
        'subject' => 'アカウント認証が完了しました',
        'greeting' => ':nameさん、こんにちは！',
        'message' => 'あなたのメンバーアカウントの認証が完了しました。',
        'member_info' => '【メンバー情報】',
        'name' => '名前',
        'email' => 'メールアドレス',
        'login_info' => '以下のURLから管理画面にログインできます。',
        'url_info' => '【URL情報】',
        'front_url' => 'フロントページURL',
        'admin_url' => '管理画面URL',
        'thanks' => 'ご利用ありがとうございます。',
        'regards' => 'よろしくお願いいたします',
    ],

    // 管理者向け通知
    'admin_notification' => [
        'member_verified' => [
            'subject' => 'メンバーアカウントの認証完了通知',
            'greeting' => 'システム管理者様',
            'title' => 'メンバーアカウントの認証完了通知',
            'message' => 'メンバーアカウントの認証が完了しました。',
            'member_info' => '【メンバー情報】',
            'name' => '名前',
            'email' => 'メールアドレス',
            'verified_at' => '認証完了日時',
            'login_available' => 'このメンバーはログイン可能な状態になりました。',
            'urls' => '【URL情報】',
            'front_url' => 'フロントページURL',
            'admin_url' => '管理画面URL',
            'notification_time' => '通知日時',
            'regards' => 'よろしくお願いします。',
        ],
    ],

    // 拡張機能操作通知
    'extension_operation' => [
        // 件名
        'subject_installed' => '【:app_name】:type「:name」がインストールされました',
        'subject_uninstalled' => '【:app_name】:type「:name」がアンインストールされました',
        'subject_enabled' => '【:app_name】:type「:name」が有効化されました',
        'subject_disabled' => '【:app_name】:type「:name」が無効化されました',
        'subject_unhealthy_warning' => '【:app_name 警告】健全性に注意が必要な:typeが操作されました',
        // タイプ
        'type_plugin' => 'プラグイン',
        'type_theme' => 'テーマ',
        // 本文
        'greeting' => 'システム管理者様',
        'message_installed' => ':type「:name」がインストールされました。',
        'message_uninstalled' => ':type「:name」がアンインストールされました。',
        'message_enabled' => ':type「:name」が有効化されました。',
        'message_disabled' => ':type「:name」が無効化されました。',
        'message_unhealthy_warning' => '健全性が「良好」以外の:typeが操作されました。内容をご確認ください。',
        // 詳細
        'details_title' => '操作詳細',
        'extension_name' => '拡張機能名',
        'extension_type' => '種類',
        'operation' => '操作',
        'operation_installed' => 'インストール',
        'operation_uninstalled' => 'アンインストール',
        'operation_enabled' => '有効化',
        'operation_disabled' => '無効化',
        'operated_by' => '操作者',
        'operated_at' => '操作日時',
        'health_status' => '健全性',
        'health_healthy' => '良好',
        'health_warning' => '注意',
        'health_needs_attention' => '要確認',
        'health_not_verified' => '未確認',
        'version' => 'バージョン',
        // 警告メッセージ
        'unhealthy_notice' => 'この拡張機能は健全性が「:level」です。使用する機能や権限について確認することをお勧めします。',
        // フッター
        'regards' => 'よろしくお願いいたします。',
        'auto_notification' => 'この通知はセキュリティ設定に基づいて自動送信されています。',
    ],
    
    // ファイル整合性アラートメール
    'file_integrity' => [
        'subject' => '【:site_name】ファイル整合性アラート - :status',
        'title' => 'ファイル整合性アラート',
        'greeting' => ':site_name のファイル整合性チェックで問題が検出されました。',
        'intro' => 'スキャン結果: :status',
        'scan_info' => 'スキャン情報',
        'scan_date' => 'スキャン日時',
        'status' => 'ステータス',
        'status_critical' => '重大',
        'status_warning' => '警告',
        'status_unknown' => '不明',
        'files_scanned' => 'スキャンファイル数',
        'issues_summary' => '検出された問題',
        'changed_files' => '変更されたファイル',
        'added_files' => '追加されたファイル',
        'removed_files' => '削除されたファイル',
        'suspicious_files' => '疑わしいファイル',
        'action_required' => '詳細を確認し、必要に応じて対処してください。',
        'view_details_button' => '詳細を確認',
        'thanks' => 'よろしくお願いいたします。',
    ],

];
