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
        'expire_label' => 'コードの有効期限',
        'expired' => '期限切れ',
        'submit' => '認証してログイン',
        'verify' => '認証する',
        'resend' => '認証コードを再送信する',
        'invalid' => '認証コードが間違っているか、有効期限が切れています。',
        'invalid_code' => '認証コードが間違っているか、有効期限が切れています。',
        'resend_success' => 'メールを再送信しました。',
        'resend_failed' => 'コードの再送信に失敗しました',
        'network_error' => 'ネットワークエラーが発生しました',
        'seconds_suffix' => '秒',
    ],
    
    // デバイス認証
    'device' => [
        'title' => 'デバイス認証',
        'prompt' => 'メンバーアカウントのメールアドレスにデバイス認証用のメールが送信されました。<br>メールから認証リクエストを承認してください。',
        'waiting_title' => 'デバイス認証待機中',
        'waiting_message' => 'メールから認証リクエストを承認してください。',
        'start_button' => '認証を開始',
        'help_text' => 'メールに記載されたリンクから認証を承認してください。',
        'remaining_time' => '残り時間',
        'seconds_suffix' => '秒',
        'success_title' => '認証成功',
        'success_message' => 'デバイス認証が完了しました。リダイレクトしています...',
        'error_title' => '認証失敗',
        'error_message' => 'デバイス認証に失敗しました。再試行してください。',
        'timeout_title' => '認証タイムアウト',
        'timeout_message' => '認証の時間制限を超過しました。再試行してください。',
        'retry_button' => '再試行',
        'challenge_start_failed' => 'チャレンジの開始に失敗しました',
        'auth_denied' => '認証が拒否されました',
        'network_error' => 'ネットワークエラーが発生しました',
        'expire_label' => '有効期限',
        'resend_button' => '認証メールを再送信',
        'sending' => '送信中...',
        'approved_title' => 'ログイン承認完了',
        'approved_description' => 'ログインが承認されました。',
        'approved_message' => 'ログインリクエストが承認されました。<br>ログイン画面に戻って、自動的にログインが完了します。',
        'approved_close' => 'このウィンドウは閉じても問題ありません。',
        'denied_title' => 'ログイン拒否',
        'denied_description' => 'ログインリクエストが拒否されました。',
        'denied_message' => 'ログインリクエストが拒否されました。<br>ログイン試行は無効になりました。',
        'denied_close' => 'このウィンドウは閉じても問題ありません。',
        'error_page_title' => 'エラー',
        'error_page_description' => 'リクエストの処理中にエラーが発生しました。',
        'error_page_message' => 'リクエストの処理中にエラーが発生しました。',
        'error_page_close' => 'このウィンドウは閉じても問題ありません。',
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
    'alternative_methods_prompt' => '別の認証方法を使用しますか？',
    'awaiting_approval' => '承認待機中...',
];
