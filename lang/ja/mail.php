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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
        'member_id' => 'メンバーID:',
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
            'member_info' => '【メンバー情報】',
            'name' => '名前',
            'email' => 'メールアドレス',
            'login_info' => '以下のURLから管理画面にログインできます。',
            'url_info' => '【URL情報】',
            'front_url' => 'フロントページURL',
            'admin_url' => '管理画面URL',
            'thanks' => 'ご利用ありがとうございます。',
            'regards' => 'よろしくお願いいたします。',
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
            'subject' => 'ログイン承認リクエスト',
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
        'timestamp' => '発生日時',
        'settings' => 'ロックアウト設定',
        'max_attempts' => '最大試行回数',
        'time_window' => '時間枠',
        'lockout_duration' => 'ロックアウト期間',
        'times' => ':count 回',
        'minutes' => ':minutes 分',
        'action_required' => 'セキュリティ上の理由により、このログインロックアウトを確認し、必要に応じて適切な対応を行ってください。',
        'thanks' => 'よろしくお願いいたします。',
    ],

    'file-integrity' => [
        'subject' => '【:site_name】ファイル整合性アラート - :status',
        'title' => 'ファイル整合性アラート',
        'greeting' => ':site_name のファイル整合性チェックで問題が検出されました。',
        'intro' => 'スキャン結果: :status',
        'scan_info' => 'スキャン情報',
        'scan_date' => 'スキャン日時',
        'status' => 'ステータス',
        'status_critical' => '重大',
        'status_warning' => '警告',
        'status_unknown' => '不明',
        'files_scanned' => 'スキャンファイル数',
        'issues_summary' => '検出された問題',
        'changed_files' => '変更されたファイル',
        'added_files' => '追加されたファイル',
        'removed_files' => '削除されたファイル',
        'suspicious_files' => '疑わしいファイル',
        'action_required' => '詳細を確認し、必要に応じて対処してください。',
        'view_details_button' => '詳細を確認',
        'thanks' => 'よろしくお願いいたします。',
    ],

    'extension' => [
        'subject_installed' => '【:app_name】:type「:name」がインストールされました',
        'subject_uninstalled' => '【:app_name】:type「:name」がアンインストールされました',
        'subject_enabled' => '【:app_name】:type「:name」が有効化されました',
        'subject_disabled' => '【:app_name】:type「:name」が無効化されました',
        'subject_unhealthy_warning' => '【:app_name 警告】健全性に注意が必要な:typeが操作されました',
        'type_plugin' => 'プラグイン',
        'type_theme' => 'テーマ',
        'greeting' => 'システム管理者様',
        'message_installed' => ':type「:name」がインストールされました。',
        'message_uninstalled' => ':type「:name」がアンインストールされました。',
        'message_enabled' => ':type「:name」が有効化されました。',
        'message_disabled' => ':type「:name」が無効化されました。',
        'message_unhealthy_warning' => '健全性が「良好」以外の:typeが操作されました。内容をご確認ください。',
        'details_title' => '操作詳細',
        'extension_name' => '拡張機能名',
        'extension_type' => '種類',
        'operation' => '操作',
        'operation_installed' => 'インストール',
        'operation_uninstalled' => 'アンインストール',
        'operation_enabled' => '有効化',
        'operation_disabled' => '無効化',
        'operated_by' => '操作者',
        'operated_at' => '操作日時',
        'health_status' => '健全性',
        'health_healthy' => '良好',
        'health_warning' => '注意',
        'health_needs_attention' => '要確認',
        'health_not_verified' => '未確認',
        'version' => 'バージョン',
        'unhealthy_notice' => 'この拡張機能は健全性が「:level」です。使用する機能や権限について確認することをお勧めします。',
        'auto_notification' => 'この通知はセキュリティ設定に基づいて自動送信されています。',
        'regards' => 'よろしくお願いいたします。',
    ],

    'member-notification' => [
        'admin_notification' => [
            'member_verified' => [
                'subject' => 'メンバーアカウントの認証完了通知',
                'greeting' => 'システム管理者様',
                'title' => 'メンバーアカウントの認証完了通知',
                'message' => 'メンバーアカウントの認証が完了しました。',
                'member_info' => '【メンバー情報】',
                'name' => '名前',
                'email' => 'メールアドレス',
                'verified_at' => '認証完了日時',
                'login_available' => 'このメンバーはログイン可能な状態になりました。',
                'urls' => '【URL情報】',
                'front_url' => 'フロントページURL',
                'admin_url' => '管理画面URL',
                'notification_time' => '通知日時',
                'regards' => 'よろしくお願いいたします。',
            ],
        ],
    ],

    'send_success' => 'メールを送信しました。',
    'queued_success' => 'メールを送信キューに追加しました。',
];
