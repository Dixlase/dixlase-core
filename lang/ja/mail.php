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

    // テストメール
    'test_mail' => [
        'subject' => 'メールテスト',
        'greeting' => 'こんにちは！',
        'test_details_title' => 'メール送信メール',
        'app_name' => 'アプリケーション名:',
        'test_datetime' => 'テスト実行日時:',
        'verification_required' => 'このメールが正常に受信できているかを確認するため、以下のボタンをクリックしてください。',
        'verify_button' => 'メール受信を確認',
        'manual_verification' => 'ボタンが機能しない場合は、以下のURLを直接ブラウザにコピーしてアクセスしてください:',
        'regards' => 'よろしくお願いいたします。',
    ],
];
