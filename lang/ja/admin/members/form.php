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
    'account_name_help' => 'ログインに使用するアカウント名です。3〜20文字の半角英数字を使用してください。',
    'display_name_help' => '管理バーやプロフィールに表示される名前です。空欄の場合はアカウント名が表示されます。',
    'password_change_only' => 'パスワード（変更する場合のみ）',
    'login_notification' => 'ログイン通知',
    'two_fa_mode' => '二段階認証モード',
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
    'two_fa_global_fixed' => '二段階認証設定はメンバー全体設定で「:setting」に固定されています。<br>変更する場合は、メンバー全体設定で「プロフィール設定に従う」に変更してください。',
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
    'two_fa_method_note' => '認証方法の全体設定は',
    'change_in_global_settings' => 'メンバー全体設定',
    'passkey_disabled_globally' => '全体設定で無効',
    'default_two_fa_method' => 'デフォルトの認証方法',
    'default_two_factor_method_help' => '二段階認証時に最初に表示される認証方法を選択します。',
    'passkey_disabled_default_email_only' => 'パスキー認証を無効にしているため、デフォルトの認証方法は自動的にメール認証になります。',
    'two_fa_management_admin_note' => '管理者はPasskeyデバイスの追加や回復コードの生成はできません。削除のみ可能です。追加・生成はメンバー本人のみが実行できます。',
    'two_fa_cannot_enable_warning' => '二段階認証を有効化できません。メールサーバーの設定、パスキーの登録、または回復コードの生成のいずれかが必要です。',
    
    // ロールの権限範囲説明
    'role_permissions_info' => 'ロールごとの権限範囲',
    'role_super_admin_description' => 'すべての管理機能にアクセスでき、他の管理者の管理も可能です。システム設定の変更権限を持ちます。',
    'role_admin_description' => '管理画面のほとんどの機能にアクセスできますが、他の管理者の管理やシステム設定の変更はできません。',
    'role_editor_description' => 'コンテンツの作成・編集・公開が可能です。他のメンバーが作成したコンテンツも編集できます。',
    'role_author_description' => '自分が作成したコンテンツのみ作成・編集・公開が可能です。他のメンバーのコンテンツは編集できません。',
    'role_contributor_description' => 'コンテンツの作成・編集が可能ですが、公開はできません。編集者以上の承認が必要です。',
    'role_receptionist_description' => '受付業務に必要な限定的な機能のみ利用できます。コンテンツの作成・編集はできません。',
    'role_guest_description' => '最小限の閲覧権限のみを持ちます。ほとんどの管理機能にアクセスできません。',
];
