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
    'mail_title' => 'メールサーバー設定',
    'mail_header' => 'メールサーバー設定(任意)',
    'mail_description' => 'アプリケーションがメールを送信するために使用する<br>メールサーバーの情報を入力します。<br>この設定はスキップしてインストール後に設定することも可能です。',

    // メールサーバー設定関連
    'mail_server_settings' => 'メールサーバー設定',
    'mail_connection_test' => 'メール接続テスト',

    // メールテスト機能
    'mail_test' => [
        'title' => 'メールテスト',
        'description' => 'メールサーバーの接続とメール送信をテストできます。',
        'description_admin_email' => 'テストメールは基本設定で入力した管理者メールアドレスに送信されます。',
    ],
    'mail_test_description' => 'メールサーバーの接続とメール送信をテストできます。',
    'mail_test_description_admin_email' => 'テストメールは基本設定で入力した管理者メールアドレスに送信されます。',
    'admin_email_not_found' => '管理者メールアドレスが見つかりません。基本設定で管理者メールアドレスを入力してください。',

    'mail_test_advanced' => [
        'three_stage_test_incomplete' => '3段階メールテストが未完了です',
        'three_stage_test_complete' => '3段階メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
    ],

    // インストール方法に応じた案内
    'mailpit_heading' => 'Mailpit でのメール確認',
    'mailpit_ready' => 'Docker インストーラがテスト用のメールサーバー Mailpit を用意しており、設定は入力済みです。',
    'mailpit_details' => [
        'Mailpit に送ったメールは、実際には誰にも配信されません。Mailpit の画面で内容を確認できます。',
        '実際にメールを送るには、代わりに SMTP サーバーの情報を入力してください。インストール後に設定することもできます。',
    ],
    'mailpit_open_inbox' => 'テストメールは Mailpit に届きます。送信したら、次の URL で Mailpit を開いて確認してください。',
    'smtp_heading' => 'SMTP サーバー',
    'smtp_required' => 'メールを送るには SMTP サーバーが必要です。',
    'smtp_required_details' => [
        'レンタルサーバーやメール配信サービスの SMTP サーバーを使ってください。ホスト・ポート・ユーザー名・パスワードは、そのサービスの設定画面に表示されています。',
        'まだ用意していない場合は、この手順を飛ばして、インストール後に設定できます。',
    ],
];
