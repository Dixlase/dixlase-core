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
 */

return [
    'heading' => 'メンバー管理',
    'description' => '登録されているメンバーの一覧表示、検索、編集、削除を行います。',
    'force_logout_all' => '全メンバー強制ログアウト',
    'force_logout_all_confirmation_title' => '全メンバー強制ログアウトの確認',
    'force_logout_all_confirmation_message' => '自分以外の全メンバーを強制的にログアウトさせますか？この操作は取り消せません。',
    'search_title' => 'メンバー検索',
    'search_placeholder' => 'メンバー名またはメールアドレスで検索',
    'table' => [
        'unknown_role' => '不明なロール',
        'caption' => 'メンバー一覧',
    ],
    'messages' => [
        'initial_member_role_protected' => '初期メンバーアカウントの権限は変更できません。',
        'initial_member_status_protected' => '初期メンバーアカウントは無効化できません。',
        'initial_member_cannot_delete' => '初期メンバーアカウントは削除できません。',
        'permissions_saved' => '権限設定を保存しました。',
        'insufficient_permissions' => 'この操作を行う権限がありません。',
        'deleted' => 'メンバーを削除しました。',
    ],
    'validation' => [
        'mail_server_not_tested' => 'ロックアウト通知機能、パスワードリセット機能、ログイン通知機能、2段階認証機能を使用するには、基本設定でメールサーバーの接続テストに合格する必要があります。',
        'mail_server_warning' => 'メールサーバー未設定',
        'mail_server_warning_message' => 'メールサーバーの設定とテストが未実行のため、ロックアウト通知機能、パスワードリセット機能、ログイン通知機能、2段階認証機能は動作しません。',
        'mail_server_test_passed' => 'メールサーバー接続テスト合格済み。ロックアウト通知、パスワードリセット機能、ログイン通知機能、2段階認証機能が利用できます。',
        'please_configure_in' => 'でメールサーバーを設定してください。',
        'name_required' => '名前は必須です。',
        'email_required' => 'メールアドレスは必須です。',
        'email_invalid' => 'メールアドレスの形式が正しくありません。',
        'email_unique' => 'このメールアドレスは既に登録されています。',
        'password_required' => 'パスワードは必須です。',
        'password_min' => 'パスワードは最低8文字必要です。',
        'password_confirmed' => 'パスワード確認が一致しません。',
        'role_required' => 'ロールを選択してください。',
        'role_invalid' => '無効な権限が選択されました。',
        'appearance_required' => '外観設定を選択してください。',
        'appearance_invalid' => '無効な外観設定が選択されています。',
        'status_required' => 'ステータスを選択してください。',
        'status_invalid' => '無効なステータスが選択されています。',
    ],
    'force_setting_1' => '個別設定は変更できません。',
    'force_setting_2' => 'がメンバー全体設定で選択されているためです。',
    'delete_confirm_title' => 'メンバーの削除',
    'delete_confirm_message' => 'メンバー「:name」を削除しますか？この操作は取り消せません。',
    'admin_operations' => '管理操作',
    'initial_admin_account' => '初期管理者アカウント',
    'initial_admin_restriction' => 'このアカウントは初期管理者のため、削除や強制ログアウトはできません。システムの安全性を保つため、これらの操作は制限されています。',
    'force_logout_button' => '強制ログアウト',
    'delete_member_button' => 'メンバーを削除',
];
