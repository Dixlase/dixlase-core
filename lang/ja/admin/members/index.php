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
    'search_title' => 'メンバー検索',
    'search_placeholder' => 'メンバー名またはメールアドレスで検索',
    'table' => [
        'unknown_role' => '不明なロール',
        'caption' => 'メンバー一覧',
    ],
    'create' => [
        'heading' => '新規メンバー作成',
        'account_status' => 'アカウントの状態',
        'create_confirmation_title' => '作成確認',
        'create_confirmation_message' => 'この内容でメンバーを作成しますか？',
    ],
    'edit' => [
        'heading' => 'メンバー編集',
        'confirm_message' => 'この内容でメンバー情報を更新しますか？',
    ],
    'form' => [
        'password_change_only' => 'パスワード（変更する場合のみ）',
        'login_notification' => 'ログイン通知',
        'two_factor_mode' => '二段階認証モード',
        'initial_admin_status_fixed' => 'このアカウントは初期管理者のため、ステータスは変更できません。',
        'initial_admin_role_fixed' => '初期管理者のため、ロールは「スーパー管理者」に固定されています。',
        'mail_server_not_tested' => 'メールサーバーの設定・テストが完了していないため、この機能は動作しません。',
        'account_verification' => 'アカウント認証',
        'account_verification_disabled' => 'メールサーバーの設定・テストが完了していないため、自動的に認証済みになります。',
        'account_verified' => '認証済み',
        'account_unverified' => '未認証',
        'account_verified_send_email' => '認証メールを送信する',
        'account_verification_help_create' => '「認証メールを送信する」を選択すると、アカウント作成後にメンバーにメールアドレス確認用のメールが送信されます。',
        'account_verification_help_edit' => '「未認証」を選択すると認証状態がリセットされます。認証メールを送信する場合は下のボタンを使用してください。',
        'send_verification_email_button' => '認証メールを送信',
        'send_verification_email_title' => '認証メール送信確認',
        'send_verification_email_confirm' => '認証メールを送信しますか？アカウントは未認証状態に変更されます。',
        'email_confirmation' => 'メールアドレス（確認用）',
        'email_confirmation_help' => 'メールアドレスを再度入力してください。入力ミスを防ぐため、上記と同じメールアドレスを入力する必要があります。',
        'login_notification_global_fixed' => 'ログイン通知設定はメンバー全体設定で「:setting」に固定されています。<br>変更する場合は、メンバー全体設定で「プロフィール設定に従う」に変更してください。',
        'two_factor_global_fixed' => '二段階認証設定はメンバー全体設定で「:setting」に固定されています。<br>変更する場合は、メンバー全体設定で「プロフィール設定に従う」に変更してください。',
        'force_logout' => '強制ログアウト',
        'force_logout_description' => 'このメンバーを強制的にログアウトします。現在のセッションが削除されます。',
        'force_logout_button' => '強制ログアウト実行',
        'unlock_lockout' => 'ロックアウト解除',
        'unlock_lockout_description' => 'このメンバーのログインおよび二段階認証のロックアウトを解除します。失敗した試行記録が削除されます。',
        'unlock_lockout_button' => 'ロックアウト解除',
        'delete_member' => 'メンバー削除',
        'delete_member_description' => 'このメンバーを完全に削除します。この操作は取り消せません。',
        'delete_member_button' => 'メンバー削除',
        'recovery_codes_admin_note' => '管理者は回復コードの生成・再生成はできません。メンバー本人のみが実行できます。',
        'delete_recovery_codes' => '回復コードを削除',
        'confirm_delete_recovery_codes_title' => '回復コード削除の確認',
        'confirm_delete_recovery_codes_message' => 'このメンバーの全ての回復コードを削除してもよろしいですか？削除後はメンバー本人のみが再生成できます。',
        'recovery_codes_deleted' => '回復コード（:count件）を削除しました。',
        'recovery_codes_delete_success_title' => '回復コード削除完了',
        'recovery_codes_delete_error' => '回復コードの削除に失敗しました。',
    ],
    'modals' => [
        'force_logout' => [
            'title' => '強制ログアウト確認',
            'message' => ':name を強制的にログアウトしますか？',
            'confirm' => '強制ログアウト実行',
        ],
        'unlock_lockout' => [
            'title' => 'ロックアウト解除確認',
            'message' => ':name のログインおよび二段階認証のロックアウトを解除しますか？',
            'confirm' => 'ロックアウト解除',
        ],
        'delete' => [
            'title' => 'メンバー削除確認',
            'message' => ':name を完全に削除しますか？',
            'warning' => 'この操作は取り消せません。',
        ],
    ],
    'messages' => [
        'created' => '新しいメンバーを作成しました。',
        'created_with_verification_email' => '新しいメンバーアカウントを作成しました。<br>アカウントのメールアドレスに認証メールを送信しました。<br>メンバー様にアカウントの認証を済ませていただくようお知らせください。',
        'created_but_email_failed' => '新しいメンバーを作成しましたが、認証メールの送信に失敗しました。',
        'updated' => 'メンバー情報を更新しました。',
        'updated_with_verification_email' => 'メンバー情報を更新しました。認証メールを送信しました。',
        'updated_but_email_failed' => 'メンバー情報を更新しましたが、認証メールの送信に失敗しました。',
        'verification_email_sent' => '認証メールを送信しました。',
        'verification_email_failed' => '認証メールの送信に失敗しました。',
        'unlock_lockout_success' => 'ロックアウトを解除しました。',
        'initial_member_role_protected' => '初期メンバーアカウントの権限は変更できません。',
        'initial_member_status_protected' => '初期メンバーアカウントは無効化できません。',
        'initial_member_cannot_delete' => '初期メンバーアカウントは削除できません。',
        'permissions_saved' => '権限設定を保存しました。',
        'insufficient_permissions' => 'この操作を行う権限がありません。',
        'deleted' => 'メンバーアカウントを削除しました。',
        'force_logout_success' => 'メンバーを強制ログアウトしました。',
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
    'admin_operations' => '管理操作',
    'initial_admin_account' => '初期管理者アカウント',
    'initial_admin_restriction' => 'このアカウントは初期管理者のため、削除や強制ログアウトはできません。システムの安全性を保つため、これらの操作は制限されています。',
    'force_logout_button' => '強制ログアウト',
    'delete_member_button' => 'メンバーを削除',
];
