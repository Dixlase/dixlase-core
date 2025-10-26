<?php

return [
    // メール認証
    'email' => [
        'title' => '二段階認証',
        'prompt' => <<<TEXT
認証コードが書かれたメールを送信しました。
メールに書かれている6桁の認証コードを入力してください。
TEXT,
        'code_title' => '認証コード入力',
        'code_prompt' => 'メールに記載された6桁の認証コードを入力してください。',
        'code_label' => '認証コード',
        'expire_notice' => '認証コードは :minutes 分間有効です。',
        'submit' => '認証してログイン',
        'verify' => '認証する',
        'resend' => '認証コードを再送信する',
        'invalid' => '認証コードが間違っているか、有効期限が切れています。',
        'invalid_code' => '認証コードが間違っているか、有効期限が切れています。',
        'resend_success' => 'メールを再送信しました。',
    ],
    
    // デバイス認証
    'device' => [
        'title' => 'デバイス認証',
        'prompt' => 'メンバーアカウントのメールアドレスにデバイス認証用のメールが送信されました。<br>メールから認証リクエストを承認してください。',
        'waiting_title' => 'デバイス認証待機中',
        'waiting_message' => 'メールから認証リクエストを承認してください。',
    ],
    
    // 生体認証
    'biometric' => [
        'title' => '生体認証',
        'prompt' => '生体認証を使用してログインしてください。',
        'waiting_title' => '生体認証待機中',
        'waiting_message' => 'Touch ID、Face ID、または指紋認証を使用してください。',
    ],

    // 共通
    'back_to_login' => 'ログイン画面に戻る',
];
