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
    // メール受信確認成功ページ
    'verification_success' => [
        'title' => 'メール受信確認完了',
        'heading' => 'メール受信確認が完了しました',
        'description' => 'メール機能のテストが正常に完了しました。',
        'actions' => 'アクション',
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

    // メール受信確認エラーページ
    'verification_error' => [
        'title' => 'メール受信確認エラー',
        'heading' => 'メール受信確認でエラーが発生しました',
        'invalid_token_description' => 'このメール認証リンクは無効または期限切れです。',
        'verification_error_description' => 'メール受信確認の処理中にエラーが発生しました。',
        'general_error_description' => '予期しないエラーが発生しました。',
        'solution_title' => '対処方法',
        'actions' => 'アクション',
        'solution_steps' => [
            'このウィンドウを閉じてください',
            '基本設定画面で新しいテストメールを送信してください',
            '新しいメール内のリンクから受信確認を行ってください',
        ],
        'close_button' => 'ウィンドウを閉じる',
        'error_occurred' => 'メール受信確認でエラーが発生しました。',
    ],
    
    // メール受信確認機能（共通）
    'verification_token_invalid' => 'メール認証トークンが無効です。',
    'verification_error' => 'メール認証処理中にエラーが発生しました: :error',
    'verification_success_common' => [
        'title' => 'メール受信確認完了',
        'heading' => 'メール受信確認が完了しました',
        'description' => 'メールサーバーの設定が正しく動作していることが確認されました。',
        'next_steps_title' => '次の手順',
        'next_steps' => [
            'close_window' => 'このウィンドウを閉じてください',
            'continue_install' => 'インストール画面に戻って設定を続行してください',
            'save_settings' => '設定を保存してください',
            'data_saved' => 'データは一時的に保存されています',
        ],
        'close_button' => 'ウィンドウを閉じる',
        'completed_message' => 'メール受信確認が完了しました'
    ],
];
