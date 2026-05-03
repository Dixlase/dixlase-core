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
    'password-reset' => [
        'subject' => 'パスワードリセット通知',
        'greeting' => 'こんにちは！',
        'line1' => 'アカウントのパスワードリセット要求を受け取ったため、このメールをお送りしています。',
        'action' => 'パスワードをリセット',
        'line2' => 'このパスワードリセットリンクは :count 分後に期限切れになります。',
        'line3' => 'パスワードリセットを要求していない場合は、何もする必要はありません。',
        'regards' => 'よろしくお願いいたします',
    ],

    'login-notification' => [
        'subject_user' => '【ログイン通知】:nameさん、:contextにログインがありました',
        'subject_system' => '【システム通知】:contextへのログインがありました',
        'title' => 'ログイン通知',
        'user_message' => ':nameさん、:contextにログインがありました。',
        'system_message' => 'システム通知',
        'details_title' => 'ログイン詳細:',
        'datetime' => '日時:',
        'ip_address' => 'IPアドレス:',
        'user_agent' => 'User-Agent:',
        'user_id' => 'ユーザーID:',
        'security_notice' => 'もしこのログインに心当たりがない場合は、すぐにパスワードを変更してください。',
        'access_site' => 'サイトにアクセス',
        'action_subcopy' => '":button_text" ボタンをクリックできない場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:',
        'regards' => 'よろしくお願いいたします。',
        'context' => [
            'admin' => '管理画面',
        ],
    ],

    'verify-email' => [
        'member' => [
            'subject' => 'メールアドレスの確認',
            'subject_account' => ':typeアカウントの確認',
            'greeting' => ':nameさん、こんにちは！',
            'message_create' => 'ご登録ありがとうございます。以下のボタンをクリックして、メールアドレスの認証を完了してください。',
            'message_email_change' => 'メールアドレスが変更されました。以下のボタンをクリックして、メールアドレスの変更を完了させてください。',
            'message_resend' => 'メールアドレスの認証が必要です。以下のボタンをクリックして、認証を完了してください。',
            'action_verify_account' => 'メールアドレスを認証',
            'action_change_email' => 'メールアドレス変更を確認',
            'manual_verification' => '上のボタンが機能しない場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:',
            'expiration' => 'このリンクは :minutes 分後に期限切れになります。',
            'security_notice' => 'このメールに心当たりがない場合は、何もする必要はありません。',
            'regards' => 'よろしくお願いいたします。',
        ],
        'member_verification_completed' => [
            'subject' => 'メンバーアカウントの認証完了',
            'greeting' => ':nameさん、こんにちは！',
            'message' => 'メールアドレスの認証が完了しました。',
        ],
    ],

    'two-fa' => [
        'security_notice' => 'このログインに心当たりがない場合は、第三者によってログインが試行された可能性があります。不正アクセスのリスクがありますので、至急、パスワードを変更するか、システム管理者にお問い合わせください。',
        'regards' => 'よろしくお願いいたします。',
        'details_title' => 'ログイン試行の詳細',
        'ip_address' => 'IPアドレス',
        'user_agent' => 'ブラウザ/デバイス',
        'datetime' => '日時',
        'timestamp' => '日時',
        'email' => [
            'subject' => '【:app_name】二段階認証コード',
            'greeting' => '二段階認証',
            'message' => 'ログインするには、以下の認証コードを入力してください。',
            'instructions' => 'このコードは10分間有効です。心当たりがない場合は、このメールを無視してください。',
        ],
        'device' => [
            'title' => '新しいデバイスからのログイン試行',
            'greeting' => ':nameさん',
            'message' => '新しいデバイスからログインが試行されました。',
            'action_prompt' => 'このログインを承認または拒否してください。',
            'approve_button' => 'ログインを承認',
            'deny_button' => 'ログインを拒否',
        ],
    ],

    'lockout' => [
        'subject' => '【セキュリティ警告】管理画面ログインロックアウト発生',
        'title' => '管理画面ログインロックアウト通知',
        'message' => '管理画面でログインロックアウトが発生しました。不正なログイン試行の可能性があります。',
        'details' => 'ロックアウト詳細',
        'identifier' => 'メールアドレス',
        'ip_address' => 'IPアドレス',
        'user_agent' => 'ユーザーエージェント',
        'datetime' => '日時',
        'lockout_duration' => 'ロックアウト期間',
        'minutes' => ':minutes 分',
    ],

    'file-integrity' => [
        'subject' => '【:site_name】ファイル整合性アラート - :status',
        'title' => 'ファイル整合性アラート',
        'greeting' => ':site_name のファイル整合性チェックで問題が検出されました。',
        'intro' => 'スキャン結果: :status',
        'scan_info' => 'スキャン情報',
        'scan_date' => 'スキャン日時',
        'status' => 'ステータス',
    ],

    'extension' => [
        'subject_installed' => '【:app_name】:type「:name」がインストールされました',
        'subject_uninstalled' => '【:app_name】:type「:name」がアンインストールされました',
        'subject_enabled' => '【:app_name】:type「:name」が有効化されました',
        'subject_disabled' => '【:app_name】:type「:name」が無効化されました',
        'subject_unhealthy_warning' => '【:app_name 警告】健全性に注意が必要な:typeが操作されました',
    ],

    'member-notification' => [
        'admin_notification' => [
            'member_verified' => [
                'subject' => 'メンバーアカウントの認証完了通知',
                'greeting' => 'システム管理者様',
                'title' => 'メンバーアカウントの認証完了通知',
                'message' => 'メンバーアカウントの認証が完了しました。',
            ],
        ],
    ],
];
