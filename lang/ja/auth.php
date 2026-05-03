<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
    | 認証関連の翻訳（共通）
    |--------------------------------------------------------------------------
    |
    | 管理画面とユーザー画面で共通して使用される認証関連の翻訳
    |
    */

    'failed' => 'ログイン情報が正しくありません。',
    'failed_with_attempts' => 'ログイン情報が正しくありません。残り :attempts 回試行できます。',
    'password' => 'パスワードが正しくありません。',
    'throttle' => 'ログイン試行回数が多すぎます。:seconds秒後に再度お試しください。',
    'lockout' => 'ログイン試行回数が上限に達しました。:minutes分後に再度お試しください。',
    'two_fa_locked_out' => '二段階認証の試行回数が上限に達しました。:minutes分後に再度お試しください。',

    // パスワードリセット
    'reset' => [
        'sent' => 'パスワードリセットリンクをメールで送信しました。',
        'token' => 'このパスワードリセットトークンは無効です。',
        'user' => 'このメールアドレスのユーザーが見つかりません。',
        'password' => 'パスワードは8文字以上で、確認用パスワードと一致する必要があります。',
        'reset' => 'パスワードをリセットしました。',
        'throttled' => 'しばらく待ってから再度お試しください。',
    ],

    // 認証モード（通知設定用）
    'authentication_mode' => [
        'notification' => [
            'disabled' => '無効',
            'different_device' => '異なるデバイス・IPでのログイン時のみ',
            'always' => '常に通知',
            'use_profile_setting' => 'プロフィール設定に従う',
        ],
    ],

    // ログインフォーム（共通）
    'login_field' => 'メールアドレスまたはアカウント名',
    'continue' => '続ける',
    'password_field' => 'パスワード',
    'login_button' => 'ログイン',
    'back_to_identifier' => '戻る',
    'remember_me' => 'ログイン状態を保持する',
    'forgot_password' => 'パスワードをお忘れですか？',

    // パスキー認証（共通）
    'passkey_login' => 'パスキーでログイン',
    'login_with_passkey' => 'パスキーでログイン',
    'passkey_cancelled' => 'パスキー認証がキャンセルされました',
    'no_passkey_registered' => 'パスキーが登録されていません',
    'two_fa_disabled' => '二段階認証が無効になっています',
    'change_account' => 'アカウントを変更',

    // IP制限
    'ip_lockout' => 'このIPアドレスからのログイン試行回数が上限に達しました。しばらく時間をおいてから再度お試しください。',
];
