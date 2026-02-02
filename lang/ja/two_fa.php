<?php

return [
    // 二段階認証方法
    'method' => [
        'email' => 'メール認証',
        'passkey' => 'Passkey認証',
    ],
    
    // セキュリティレベル
    'security' => [
        'level' => [
            'very_high' => '非常に高い',
            'high' => '高い',
            'medium' => '中程度',
            'low' => '低い',
        ],
        'description' => [
            'passkey' => '生体認証またはセキュリティキーを使用する最も安全な方法です。デバイスに保存された認証情報を使用するため、フィッシング攻撃に強く、安全にログインできます。',
            'email' => 'メールアドレスに送信される認証コードを使用します。有効期限や使用回数制限により保護されていますが、メールアカウントのセキュリティに依存します。より高いセキュリティが必要な場合はPasskeyの使用を推奨します。',
            'recovery_code' => '緊急時のバックアップ手段です。Passkeyやメール認証が使用できない場合に使用します。回復コードは一度しか使用できず、使用後は無効になります。安全な場所に保管してください。',
        ],
        'recommended' => '推奨',
        'backup' => 'バックアップ',
    ],
    
    // Passkeyデバイス未登録警告
    'passkey_device_not_registered_title' => 'Passkeyデバイスが登録されていません',
    'passkey_device_not_registered_message' => 'Passkey認証を使用するにはデバイスの登録が必要です。<br>プロフィール画面からデバイスを登録してください。<br>それまでは他の認証方法をご利用ください。',
    
    // メール認証
    'title' => '二段階認証',
    'email' => [
        'prompt' => <<<TEXT
認証コードが書かれたメールを送信しました。
メールに書かれている6桁の認証コードを入力してください。
TEXT,
        'code_title' => 'メール認証',
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
        'invalid_with_attempts' => '認証コードが間違っています。残り試行回数: :attempts回',
        'resend_success' => 'メールを再送信しました。',
        'resend_failed' => 'コードの再送信に失敗しました',
        'network_error' => 'ネットワークエラーが発生しました',
        'minutes_suffix' => '分',
        'seconds_suffix' => '秒',
        'use_email_code' => 'メール認証',
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

    // Passkey認証
    'passkey' => [
        'title' => 'Passkey認証(生体認証)',
        'prompt' => 'Passkey(生体認証)を使用してログインしてください。',
        'start_auth' => '認証を開始',
        'waiting_title' => 'Passkey認証(生体認証)待機中',
        'waiting_message' => 'Touch ID、Face ID、または登録済みのPasskeyを使用してください。',
        'success_title' => '認証成功',
        'success_message' => 'Passkey認証(生体認証)が完了しました。リダイレクトしています...',
        'error_title' => '認証失敗',
        'error_message' => 'Passkey認証に失敗しました。再試行してください。',
        'retry' => '再試行',
        'unsupported_title' => 'Passkey未対応',
        'unsupported_message' => 'お使いのデバイスまたはブラウザはPasskeyに対応していません。',
        'challenge_failed' => 'チャレンジの開始に失敗しました',
        'network_error' => 'ネットワークエラーが発生しました',
        'no_challenge_data' => 'チャレンジデータがありません',
        'verification_failed' => '認証の検証に失敗しました',
        'auth_cancelled' => '認証がキャンセルされました',
        'invalid_state' => '認証の状態が無効です',
        'auth_failed' => '生体認証に失敗しました',
    ],
    
    // 認証方法切り替え
    'switch_method_prompt' => '別の認証方法に切り替える',
    'switch_to_passkey' => 'Passkey認証に切り替える',
    'switch_to_email' => 'メール認証に切り替える',

    // 回復コード
    'recovery_code' => [
        'title' => '回復コード',
        'prompt' => '回復コードを入力してください。デバイスにアクセスできない場合、回復コードを使用してログインできます。',
        'code_label' => '回復コード',
        'format_hint' => '20桁の数字を入力してください（ハイフンあり・なし両方可）',
        'submit' => '認証してログイン',
        'invalid' => '回復コードが無効です。',
        'invalid_with_attempts' => '回復コードが無効です。残り試行回数: :attempts回',
        'use_recovery_code' => '回復コード',
        'back_to_two_fa' => '二段階認証に戻る',
    ],

    // 共通
    'back_to_login' => 'ログイン画面に戻る',
    'alternative_methods_prompt' => '別の認証方法を使用しますか？',
    'awaiting_approval' => '承認待機中...',
    
    // 2FA設定（汎用）
    'settings' => [
        'title' => '二段階認証設定',
        'mode_label' => '二段階認証',
        'method_label' => '認証方法',
        'help' => '二段階認証を使用するタイミングを設定します。',
    ],
    
    // 2FAモード
    'mode' => [
        'disabled' => '無効',
        'enabled' => '有効',
        'use_profile' => 'プロフィール設定に従う',
        'always' => '常に有効',
    ],
    
    // 2FA方法
    'method' => [
        'email' => 'メール認証',
        'passkey' => 'Passkey（生体認証）',
    ],
    
    // デバイス管理
    'devices' => [
        'passkey_devices' => 'Passkeyデバイス',
        'no_devices' => 'デバイスが登録されていません',
        'add_device' => 'デバイスを追加',
        'delete_device' => 'デバイスを削除',
        'delete_all' => '全て削除',
        'registered_at' => '登録日時',
        'last_used' => '最終使用',
    ],
    
    // 回復コード管理
    'recovery_codes' => [
        'title' => '回復コード',
        'generate' => '回復コードを生成',
        'regenerate' => '回復コードを再生成',
        'not_generated' => '回復コードはまだ生成されていません。',
        'remaining' => '残り :count 個の回復コードがあります。',
        'warning' => 'これらのコードは一度しか表示されません。<br>ダウンロード、コピー、スクリーンショット、写真撮影、印刷などの方法で安全な場所に保管してください。<br>また、コードは他人と共有しないでください。',
        'download' => 'ダウンロード',
        'copy' => 'コピー',
        'confirm_saved' => '回復コードを安全な場所に保管しました',
        'auto_generated_title' => '回復コードが自動生成されました',
        'auto_generated_message' => '二段階認証の初回クリア後、緊急時のために回復コードが自動生成されました。これらのコードは今後表示されませんので、必ず保管してください。',
    ],
    
    // ロックアウト
    'lockout' => [
        'message' => '二段階認証の試行回数が上限に達しました。:minutes分後に再度お試しください。',
        'locked' => '二段階認証の試行回数が上限に達しました。:minutes分間ロックされます。',
    ],
    
    // 生体認証（Passkey）
    'biometric' => [
        'https_required' => 'HTTPS接続が必要です。',
        'challenge_generation_failed' => 'チャレンジの生成に失敗しました。',
        'registered_successfully' => '生体認証を登録しました。',
        'registration_failed' => '生体認証の登録に失敗しました。',
        'revoked_successfully' => '生体認証を削除しました。',
        'not_found' => '生体認証が見つかりません。',
        'revocation_failed' => '生体認証の削除に失敗しました。',
        'all_revoked_successfully' => 'すべての生体認証を削除しました（:count件）。',
        'revoke_all_failed' => '生体認証の一括削除に失敗しました。',
    ],
    
    // パスキー登録促進モーダル
    'passkey_prompt' => [
        'title' => 'Passkey(生体認証)の登録をおすすめします',
        'message' => 'Passkeyを登録すると、指紋認証や顔認証でより安全かつ便利にログインできます。',
        'register_now' => '今すぐ登録',
        'later' => '後で登録',
        'dont_show_again' => '今後この画面を表示しない',
        'dismissed' => 'パスキー登録促進モーダルを非表示に設定しました。',
        'reset' => 'パスキー登録促進モーダルの設定をリセットしました。',
    ],
];
