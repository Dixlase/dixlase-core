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
    'heading' => 'メール設定',
    'description' => 'メールサーバーの接続情報を設定し、接続・送信・受信テストを実行します。',
    'mail_server_settings' => 'メールサーバー設定',
    'admin_email_settings' => 'システム管理者メールアドレス',
    'admin_email_settings_description' => 'システム管理者のメールアドレスを設定します。エラー通知やシステム関連の重要な情報の送信先として使用されます。',
    'admin_email' => '管理者メールアドレス',
    'admin_email_help' => 'システム管理者のメールアドレスを入力してください。',
    'admin_email_mail_test_required' => 'メールサーバーのテストが完了していません。エラー通知を使用するには、接続テスト、送信テスト、受信テストをすべて完了してください。',
    'settings_updated' => 'メール設定が更新されました。',
    'test_session_cleared' => 'メールテストセッションがクリアされました。',
    'mail_test_complete' => 'メール機能テスト完了',
    'mail_test_incomplete' => 'メール機能テスト未完了',
    'mail_receive_test_completed' => 'メール受信テストが完了しました。設定を保存してください。',
    'notification_email_help' => 'システムエラー通知を受信するメールアドレスを入力してください。',
    'notification_mail_test_required' => 'エラー通知機能を使用するには、上記のメール機能テストをすべて完了してください。',
    'view_messages' => [
        'mail_test_complete' => 'メール機能テスト完了',
        'mail_test_incomplete' => 'メール機能テスト未完了',
        'mail_test_warning_features' => 'メンバー全体設定のロックアウト通知、パスワードリセット、ログイン通知、二段階認証機能を使用するには、すべてのメールテストを完了してください。',
        'mail_test_warning_temporary' => 'テスト結果は一時的に保存されます。更新ボタンを押すまで、設定やテスト結果は保存されません。',
        'connection_test' => 'サーバー接続テスト',
        'send_test' => 'メール送信テスト',
        'receive_test' => 'メール受信確認テスト',
        'test_passed' => 'テスト合格',
        'test_not_completed' => '未実行',
        'mail_receive_test_completed' => 'メール受信テストが完了しました。設定を保存してください。',
    ],
    'mail_verification_success' => [
        'title' => 'メール受信確認完了',
        'heading' => 'メール受信確認が完了しました',
        'description' => 'メール機能のテストが正常に完了しました。',
        'already_verified_heading' => 'メール受信確認済み',
        'already_verified_description' => 'このメールの受信確認は既に完了しています。',
        'next_steps_title' => '次の手順',
        'next_steps' => [
            'close_window' => 'このウィンドウを閉じてください',
            'save_settings' => '設定を保存してテスト結果を確定してください',
            'data_saved' => 'データが保存されました',
        ],
        'next_steps_install' => [
            'close_window' => 'このウィンドウを閉じてください',
            'continue_install' => 'インストールを続行してください',
        ],
        'important_notice_title' => '重要なお知らせ',
        'important_notice' => 'テスト結果は一時的なものです。設定を保存するまで確定されません。',
        'close_button' => 'ウィンドウを閉じる',
        'completed_message' => 'メール受信確認が完了しました。',
    ],
    'controller_messages' => [
        'settings_updated' => '基本設定が更新されました。',
        'test_session_cleared' => 'メールテストセッションがクリアされました。',
        'mailer_not_supported' => ':mailer ドライバーでは接続テストをサポートしていません。',
        'connection_success' => 'メールサーバーへの接続が成功しました。',
        'connection_failed' => 'メールサーバーへの接続が失敗しました: :error',
        'verification_token_invalid' => '無効な検証トークンです。',
        'verification_error' => 'メール受信確認でエラーが発生しました: :error',
    ],
    'validation' => [
        'app_name_required' => 'アプリケーション名は必須です。',
        'locale_required' => '言語を選択してください。',
        'timezone_invalid' => '有効なタイムゾーンを選択してください。',
        'mail_mailer_required' => 'メールドライバーを選択してください。',
        'mail_host_required' => 'メールホストを入力してください。',
        'mail_port_required' => 'メールポートを入力してください。',
        'mail_port_numeric' => 'メールポートは数値で入力してください。',
        'maintenance_mode_required' => 'メンテナンスモードの設定を選択してください。',
    ],
    'connection_test_required' => '接続テストを先に実行してください。',
    'last_test_date' => '最終テスト日時',
    'mail_server_warning' => 'メールサーバー未設定',
    'mail_server_warning_message' => 'メールサーバーの設定とテストが未実行のため、メール送信機能が利用できません。',
    'mail_server_test_passed' => 'メールサーバー接続テスト合格済み。メール送信機能が利用できます。',
    'save_settings_reminder' => '設定を保存してください',
    'save_settings_reminder_message' => '変更を有効にするため、必ず設定を保存してください。',
];
