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

    /*
    |--------------------------------------------------------------------------
    | 認証言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、認証中にさまざまなメッセージをユーザーに表示するために使用されます。
    | これらの言語行は、アプリケーションの要件に応じて自由に変更できます。
    |
    */

    'failed' => '認証情報が正しくありません。',
    'failed_with_attempts' => '認証情報が正しくありません。残り :attempts 回の試行が可能です。',
    'lockout' => 'ログイン試行回数が上限に達しました。:minutes 分後に再試行してください。',
    'ip_lockout' => 'このIPアドレスからのログイン試行が一時的に制限されています。',
    '2fa_locked_out' => '二段階認証の試行回数が上限に達しました。:minutes 分後に再試行してください。',
    '2fa_invalid_code' => '認証コードが正しくありません。',
    '2fa_code_expired' => '認証コードの有効期限が切れています。',
    'recovery_code_used' => '回復コードを使用しました。残り :count 個です。',
    'recovery_code_warning' => '回復コードの残数が少なくなっています。プロフィール設定から新しい回復コードを生成してください。',
    'password' => '提供されたパスワードが正しくありません。',
    'throttle' => 'ログイン試行が多すぎます。:seconds 秒後に再試行してください。',
    'email_not_verified' => 'このアカウントはメール認証が完了していません。登録されたメールアドレスに送信された認証メールを確認し、アカウントの認証を完了してください。',
    'verify_email_login_required' => 'アカウントの認証を完了するには、ログインしてください。ログイン後、自動的に認証が完了します。',
    'verify_email_change_login_required' => 'メールアドレスの変更を完了するには、ログインしてください。ログイン後、自動的に変更が完了します。',
    'verification_required' => 'メール認証が必要です',
    
    // Passkey認証
    'passkey_https_required' => 'Passkey認証にはHTTPS接続が必要です。',
    'passkey_not_registered' => 'Passkeyが登録されていません。',
    'passkey_challenge_error' => 'Passkey認証チャレンジの生成に失敗しました。',
    'passkey_verification_failed' => 'Passkey認証に失敗しました。',
    'passkey_verification_error' => 'Passkey認証の検証中にエラーが発生しました。',
    'verification_notice_message' => 'このアカウントはメール認証が完了していません。管理画面を使用するには、登録されたメールアドレスに送信された認証メールのリンクをクリックし、ログインしてください。',
    'verification_link_sent' => '新しい認証リンクをメールアドレスに送信しました。',
    'resend_verification_email' => '認証メールを再送信',
    'verification_token_expired' => '認証トークンの有効期限が切れています。新しい認証メールをリクエストしてください。',
    'verification_member_mismatch' => 'ログインしたアカウントと認証待ちのアカウントが一致しません。',
    'verification_invalid' => '認証トークンが無効です。',
    'verification_failed' => 'メール認証に失敗しました。もう一度お試しください。',

    // 共通フィールド
    'login_title' => ':type ログイン',
    'login_description' => ':type アカウントにログインしてください。',
    'email' => 'メールアドレス',
    'password' => 'パスワード',
    'remember_me' => 'ログイン状態を保持する',
    'login' => 'ログイン',
    'forgot_password' => 'パスワードをお忘れですか？',
    'no_account' => 'アカウントをお持ちでない方は',
    'register' => '新規登録',

    // アカウント状態
    'account_inactive' => 'このアカウントは無効化されています。',
    'account_suspended' => 'このアカウントは停止されています。',
    'email_not_verified' => 'メールアドレスの認証が完了していません。',

    // 二段階認証（共通）
    'two_factor_title' => '二段階認証',
    'two_factor_description' => '登録されたメールアドレスに認証コードを送信しました。',
    'two_factor_code' => '認証コード',
    'two_factor_verify' => '認証する',
    'two_factor_resend' => 'コードを再送信',
    'two_factor_invalid' => '認証コードが正しくありません。',
    'two_factor_expired' => '認証コードの有効期限が切れました。',
    'two_factor_sent' => '認証コードを送信しました。',
    'two_factor_send_failed' => '認証コードの送信に失敗しました。',
    'two_factor_resend_success' => '認証コードを再送信しました。',
    'recovery_code_invalid' => '回復コードが正しくありません。',
    'session_expired' => 'セッションの有効期限が切れました。再度ログインしてください。',

    // パスワードリセット（共通）
    'reset_password_title' => 'パスワードリセット',
    'reset_password_description' => 'メールアドレスを入力してください。パスワードリセット用のリンクをお送りします。',
    'reset_password_button' => 'リセットリンクを送信',
    'reset_password_sent' => 'パスワードリセット用のリンクをメールで送信しました。',
    'new_password' => '新しいパスワード',
    'confirm_password' => 'パスワード（確認）',
    'reset_password' => 'パスワードをリセット',
    'reset_password_success' => 'パスワードがリセットされました。',
    'delete_account' => 'アカウントを削除',

    // メール認証（共通）
    'verify_email_title' => 'メールアドレスの認証',
    'verify_email_description' => 'ご登録いただいたメールアドレスに認証リンクを送信しました。',
    'verify_email_message' => 'ご登録ありがとうございます。メールアドレスに送信された認証リンクをクリックして、アカウントの認証を完了してください。メールが届いていない場合は、再送信ボタンをクリックしてください。',
    'verify_email_resend' => '認証メールを再送信',
    'verify_email_sent' => '新しい認証リンクをメールアドレスに送信しました。',
    'verify_email_success' => 'メールアドレスの認証が完了しました。',
    'back_to_login' => 'ログイン画面に戻る',

    // ログアウト
    'logout' => 'ログアウト',
    'logout_success' => 'ログアウトしました。',

    // 新規登録（共通）
    'register_title' => '新規登録',
    'register_description' => 'アカウント情報を入力してください。',
    'account_name' => 'アカウント名',
    'account_name_placeholder' => '半角英数字とアンダースコア（3〜20文字）',
    'account_name_help' => 'ログインに使用するアカウント名です。半角英数字とアンダースコア（_）のみ使用できます。',
    'display_name' => '表示名',
    'password_confirmation' => 'パスワード（確認）',
    'register_button' => '登録する',
    'already_registered' => 'すでにアカウントをお持ちの方は',
    'register_success' => '登録が完了しました。',
    'send_password_reset_link' => 'パスワードリセットリンクを送信',

    // その他
    'password_incorrect' => 'パスワードが正しくありません。',

    // ===========================================
    // 認証モード（二段階認証・通知設定共通）
    // ===========================================
    'authentication_mode' => [
        'two_factor' => [
            'disabled' => '無効',
            'different_device' => '異なるデバイス・IPでのログイン時',
            'always' => '常に有効',
            'use_profile_setting' => 'プロフィール設定に従う',
        ],
        'notification' => [
            'disabled' => '無効',
            'different_device' => '異なるデバイス・IPでのログイン時のみ通知',
            'always' => '常に通知',
            'use_profile_setting' => 'プロフィール設定に従う',
        ],
    ],

    // 二段階認証設定
    'two_factor_authentication' => '二段階認証',
    'two_factor_settings' => '二段階認証設定',
    'two_factor_global_setting_fixed' => 'この設定は全体設定により固定されています。',
    'two_factor_method_global_setting_fixed' => 'この認証方法は全体設定により固定されています。',
    
    // 二段階認証モード（後方互換性のため残す）
    'two_factor_mode' => [
        'label' => '二段階認証設定',
        'options' => [
            0 => '無効',
            1 => '異なるデバイス・IPでのログイン時',
            2 => '有効',
            3 => 'メンバーのプロフィール設定に従う',
        ]
    ],
    
    // 二段階認証方法
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
    
    // ログイン通知設定
    'login_notification_mode' => [
        'label' => 'ログイン通知の設定',
        'options' => [
            0 => '無効',
            1 => '異なる端末/IP時のみ有効',
            2 => '有効',
            3 => ':account_typeのプロフィール設定を反映',
        ]
    ],
];

