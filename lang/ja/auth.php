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
];
