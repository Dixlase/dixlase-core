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
    'password' => '提供されたパスワードが正しくありません。',
    'throttle' => 'ログイン試行が多すぎます。:seconds 秒後に再試行してください。',
    'email_not_verified' => 'このアカウントはメール認証が完了していません。登録されたメールアドレスに送信された認証メールを確認し、アカウントの認証を完了してください。',
    'verify_email_login_required' => 'アカウントの認証を完了するには、ログインしてください。ログイン後、自動的に認証が完了します。',
    'verify_email_change_login_required' => 'メールアドレスの変更を完了するには、ログインしてください。ログイン後、自動的に変更が完了します。',
    'verification_required' => 'メール認証が必要です',
    'verification_notice_message' => 'このアカウントはメール認証が完了していません。管理画面を使用するには、登録されたメールアドレスに送信された認証メールのリンクをクリックし、ログインしてください。',
    'verification_link_sent' => '新しい認証リンクをメールアドレスに送信しました。',
    'resend_verification_email' => '認証メールを再送信',
    'verification_token_expired' => '認証トークンの有効期限が切れています。新しい認証メールをリクエストしてください。',
    'verification_member_mismatch' => 'ログインしたアカウントと認証待ちのアカウントが一致しません。',
    'verification_invalid' => '認証トークンが無効です。',
    'verification_failed' => 'メール認証に失敗しました。もう一度お試しください。',
    'two_factor' => [
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
        
        // デバイス認証
        'device' => [
            'title' => 'デバイス認証',
            'prompt' => '登録済みのデバイスで認証を承認してください。',
            'waiting_title' => 'デバイス認証待機中',
            'waiting_message' => 'お使いのデバイスで認証リクエストを承認してください。',
        ],
        
        // 生体認証
        'biometric' => [
            'title' => '生体認証',
            'prompt' => '生体認証を使用してログインしてください。',
            'waiting_title' => '生体認証待機中',
            'waiting_message' => 'Touch ID、Face ID、または指紋認証を使用してください。',
        ],
    ],
];
