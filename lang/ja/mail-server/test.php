<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    // メールテスト機能
    'title' => 'メールテスト',
    'description' => 'メールサーバーの設定をテストします。',
    'description_admin_email' => '管理者メールアドレス宛にテストメールが送信されます。',
    'three_stage_test_incomplete' => 'メールテストが未完了です',
    'three_stage_test_complete' => 'メールテストが完了しました',
    'connection_test' => 'サーバー接続テスト',
    'send_test' => 'メール送信テスト',
    'receive_test' => 'メール受信確認',
    'test_passed' => 'テスト合格',
    'test_not_completed' => '未実行',
    'mail_test_complete' => 'メール機能テスト完了',
    'mail_test_incomplete' => 'メール機能テスト未完了',
    'mail_test_warning_features' => 'メンバー全体設定のロックアウト通知、パスワードリセット、ログイン通知、二段階認証機能を使用するには、すべてのメールテストを完了してください。',
    'mail_test_warning_temporary' => 'テスト結果は一時的に保存されます。更新ボタンを押すまで、設定やテスト結果は保存されません。',
    'mail_receive_test_completed' => 'メール受信テストが完了しました。設定を保存してください。',
    'connection_test_required' => '接続テストを先に実行してください。',

    // テストメール内容
    'test_mail' => [
        'subject' => 'メール送信テスト',
        'greeting' => 'こんにちは！',
        'body' => ':app_name からのテストメールです。

メール送信テストが正常に完了しました。
メール受信確認を完了するには、以下のリンクをクリックしてください：

:verification_url

このリンクをクリックすることで、メール機能の完全なテストが完了します。',
        'body_with_verification' => ':app_name からのテストメールです。

メール送信テストが正常に完了しました。
メール受信確認を完了するには、以下のリンクをクリックしてください：

:verification_url

このリンクをクリックすることで、メール機能の完全なテストが完了します。',
        'test_details_title' => 'メール送信テスト',
        'app_name' => 'アプリケーション名:',
        'test_datetime' => 'テスト実行日時:',
        'verification_required' => 'このメールが正常に受信できているかを確認するため、以下のボタンをクリックしてください。',
        'verify_button' => 'メール受信を確認',
        'manual_verification' => 'ボタンが機能しない場合は、以下のURLを直接ブラウザにコピーしてアクセスしてください:',
        'regards' => 'よろしくお願いいたします。',
        'success' => 'テストメールが正常に送信されました。受信トレイをご確認ください。',
        'failed' => 'メール送信に失敗しました: :error',
    ],

    // メールテスト機能（共通）
    'test_functions' => [
        'test_connection_button' => '接続テスト',
        'test_mail_button' => 'メール送信テスト',
        'testing' => 'テスト中',
        'testing_connection' => '接続中...',
        'testing_mail' => '送信中...',
        'mail_test_description' => 'メールサーバーの接続とメール送信をテストできます。',
        'mail_test_description_2' => 'メール送信機能を有効するには、必ず接続テストとメール送信テストを実行してください。',
        'connection_test_error' => '接続テストでエラーが発生しました。メールサーバーの設定が正しいかご確認ください。',
        'mail_send_test_error' => 'テストメールの送信に失敗しました: :error',
        'mail_send_test_success' => 'テストメールを :email に送信しました。',
        'mail_send_test_failed' => 'テストメールの送信に失敗しました: :error',
        'connection_test_not_supported' => ':mailer メーラーは接続テストに対応していません。',
        'connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'connection_test_failed' => 'メールサーバーへの接続に失敗しました',
        'mail_connection_test_not_supported' => ':mailer メーラーは接続テストに対応していません。',
        'mail_connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'mail_connection_test_failed' => 'メールサーバーへの接続に失敗しました: :error',
        'send_test_success' => 'テストメールを :email に送信しました。',
        'send_test_failed' => 'テストメールの送信に失敗しました',
        'test_mail_success' => 'テストメールが正常に送信されました。受信トレイをご確認し、メール内のリンクから受信確認を完了させてください。',
        'test_mail_failed' => 'メール送信に失敗しました: :error',
        'connection_test_required' => 'メール送信テストを実行する前に、まず接続テストを完了させてください。',
        'member_not_found' => 'ログイン中のメンバーが見つかりません。再度ログインしてください。',
        'three_stage_test_incomplete' => 'メールテストが未完了です',
        'three_stage_test_complete' => 'メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
    ],

    // 3段階メールテスト機能
    'test_advanced' => [
        'test_email_subject' => 'メールサーバー設定テスト',
        'test_email_body' => 'これはメールサーバー設定のテストメールです。このメールが正常に受信できた場合、メールサーバーの設定が正しく動作しています。',
        'test_email_body_with_verification' => "これはメールサーバー設定のテストメールです。このメールが正常に受信できた場合、メールサーバーの設定が正しく動作しています。\n\nメール受信確認を完了するには、以下のリンクをクリックしてください：\n:verification_url\n\nこのリンクをクリックすることで、メール受信テストが完了します。",
        'connection_test_not_supported' => ':mailer メーラーは接続テストをサポートしていません。',
        'connection_test_success' => 'メールサーバーへの接続に成功しました。',
        'connection_test_failed' => 'メールサーバーへの接続に失敗しました',
        'smtp_connection_error' => '接続エラー: :error (エラーコード: :errno)',
        'smtp_response_invalid' => 'SMTPサーバーからの応答が不正です: :response',
        'smtp_starttls_failed' => 'STARTTLS の開始に失敗しました: :response',
        'smtp_tls_crypto_failed' => 'TLS暗号化の有効化に失敗しました',
        'smtp_auth_login_failed' => 'AUTH LOGIN コマンドが失敗しました: :response',
        'smtp_username_auth_failed' => 'ユーザー名認証が失敗しました: :response',
        'smtp_password_auth_failed' => 'パスワード認証が失敗しました: :response',
        'send_test_success' => 'テストメールを :email に送信しました。',
        'send_test_failed' => 'テストメールの送信に失敗しました',
        'verification_token_invalid' => 'メール確認トークンが無効です。',
        'verification_error' => 'メール確認中にエラーが発生しました: :error',
        'verification_success' => [
            'title' => 'メール受信確認完了',
            'heading' => 'メール受信確認が完了しました',
            'description' => 'メールサーバーの設定が正しく動作していることが確認されました。',
            'next_steps_title' => '次のステップ',
            'next_steps' => [
                'close_window' => 'このウィンドウを閉じる',
                'continue_install' => 'インストール画面に戻って設定を続行する',
            ],
            'close_button' => 'ウィンドウを閉じる',
            'completed_message' => 'メール受信確認が完了しました',
        ],
        'three_stage_test_incomplete' => '3段階メールテストが未完了です',
        'three_stage_test_complete' => '3段階メールテストが完了しました',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認',
    ],

    // JavaScript用メッセージ
    'js_messages' => [
        'test_route_not_set' => 'テストルートが設定されていません',
        'mail_test_route_not_set' => 'メールテストルートが設定されていません',
        'connection_test_first' => '先に接続テストを実行してください',
        'testing' => 'テスト中...',
        'mail_test_failed_side_note' => 'メールサーバーの設定が正しいか、メールサーバーの動作状況をご確認ください。',
        'connection_test_success_default' => '接続テストが成功しました',
        'connection_test_failed_default' => '接続テストが失敗しました',
        'mail_test_success_default' => 'メール送信テストが成功しました',
        'mail_test_failed_default' => 'メール送信テストが失敗しました。',
        'connection_test_error' => '接続テストでエラーが発生しました。',
        'mail_test_error' => 'メール送信テストでエラーが発生しました。',
        'mail_receive_test_completed' => 'メール受信テスト完了を検出',
        'mail_receive_verified' => 'メール受信確認が完了しました',
    ],
];
