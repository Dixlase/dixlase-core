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
    'mailers' => [
        'smtp' => 'SMTP（標準）',
        'sendmail' => 'Sendmail',
        'log' => 'Log（ログ出力）',
        'array' => 'Array（配列保存）',
        'failover' => 'Failover（冗長化）',
        'mailgun' => 'Mailgun（外部）',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
    ],
    'encryptions' => [
        '' => 'なし',
        'tls' => 'TLS（推奨）',
        'ssl' => 'SSL',
    ],

    // メールサーバー設定フィールド
    'server_settings' => [
        'mailer' => 'メーラー',
        'mail_host' => 'ホスト',
        'mail_port' => 'ポート',
        'mail_username' => 'ユーザー名',
        'mail_password' => 'パスワード',
        'mail_encryption' => '暗号化',
        'mail_from_address' => '送信元メールアドレス',
        'mail_from_name' => '送信元名',
    ],

    // メール設定・テスト共通
    'settings' => [
        'mailer' => 'Mailer',
        'mail_host' => 'ホスト名',
        'mail_port' => 'ポート番号',
        'mail_username' => 'ユーザー名',
        'mail_password' => 'パスワード',
        'mail_encryption' => '暗号化方式',
        'mail_from_address' => '送信元メールアドレス',
        'mail_test' => 'メール送信テスト',
        'mail_test_description' => '現在の設定でテストメールを送信します。',
        'mail_test_description_2' => 'メール送信機能を有効するには、必ず接続テストとメール送信テストを実行してください。',
        'test_connection_button' => '接続テスト',
        'test_mail_button' => 'テストメール送信',
        'testing_connection' => '接続中...',
        'testing_mail' => '送信中...',
        'mail_test_error' => 'メール送信テストでエラーが発生しました。',
        'last_test_date' => '最終テスト日時',
        'mail_server_warning' => 'メールサーバー未設定',
        'mail_server_warning_message' => 'メールサーバーの設定とテストが未実行のため、メール送信機能が利用できません。',
        'mail_server_test_passed' => 'メールサーバー接続テスト合格済み。メール送信機能が利用できます。',
        'save_settings_reminder' => '設定を保存してください',
        'save_settings_reminder_message' => '変更を有効にするため、必ず設定を保存してください。',
    ],

    // バリデーションメッセージ
    'validation' => [
        'mail_mailer_required' => 'メーラーを選択してください。',
        'mail_host_required' => 'メールホストを入力してください。',
        'mail_port_required' => 'メールポートを入力してください。',
        'mail_port_numeric' => 'メールポートは数値で入力してください。',
        'mail_from_address_email' => '送信元アドレスは有効なメールアドレスである必要があります。',
    ],

    // コントローラーメッセージ
    'controller_messages' => [
        'settings_updated' => '設定が更新されました。',
        'test_session_cleared' => 'テストセッションがクリアされました。',
        'mailer_not_supported' => 'メーラー ":mailer" は接続テストをサポートしていません。',
        'connection_success' => 'メールサーバーへの接続が正常に確認されました。',
        'connection_failed' => 'メールサーバーへの接続に失敗しました: :error',
        'verification_token_invalid' => 'メール確認トークンが無効です。',
        'verification_error' => 'メール確認中にエラーが発生しました: :error',
    ],
];
