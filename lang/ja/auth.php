<?php

/**
 * This file is part of MySoftware.
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
    'two_factor' => [
        'prompt' => <<<TEXT
認証コードが書かれたメールを送信しました。
メールに書かれている6桁の認証コードを入力してください。
TEXT,
        'code_label' => '認証コード',
        'expire_notice' => '認証コードは :minutes 分間有効です。',
        'submit' => '認証してログイン',
        'resend' => '認証コードを再送信する',
        'invalid_code' => '認証コードが間違っているか、有効期限が切れています。',
        'resend_success' => 'メールを再送信しました。',
    ],
];
