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
    'title' => 'プロフィール',
    'heading' => 'プロフィール設定',
    'description' => 'アカウント名、メールアドレス、言語設定、外観モード、二段階認証などの個人設定を管理します。',
    'use_system_default' => 'システムデフォルトを使用',
    'language_help' => '個別の言語設定です。未選択の場合はシステムのデフォルト言語が使用されます。',
    'account_name_help' => 'ログインに使用するアカウント名です。3〜20文字の半角英数字を使用してください。',
    'display_name_help' => '管理バーやプロフィールに表示される名前です。空欄の場合はアカウント名が表示されます。',
    'password_change_only' => 'パスワード（変更する場合のみ入力）',
    'updated' => 'プロフィールが更新されました。',
    'login_notification_global_setting_help' => 'この設定はメンバー全体設定で制御されています。',
    'single_method_available' => '利用可能な認証方法',
    'submit' => 'プロフィールを更新',
    'updated_with_email_verification' => 'プロフィールが更新されました。<br>新しいメールアドレスに認証メールを送信しました。<br>メールを確認してメールアドレスの変更を完了してください。',
    'confirm_title' => 'プロフィール更新の確認',
    'confirm_message' => 'プロフィールを更新しますか？',
    'email_verification_success' => 'メールアドレスの変更が完了しました。',
    'account_verification_success' => 'アカウントの認証が完了しました。',
    'email_verification_invalid' => '認証リンクが無効です。',
    'email_already_verified' => 'このメールアドレスは既に認証済みです。',
    'pending_email_notice' => ':email への変更待ちです。送信された認証メールを確認して認証を完了してください。<br>メールが届いていない場合は、メールアドレスに間違いがないか、迷惑メールに入っていないか、ご確認ください。',
    'current_email' => '現在のメールアドレス: :email',
    'email_change_help' => 'メールアドレスを変更した場合、新しいメールアドレスに認証メールが送信されます。<br>認証が完了するまで変更は反映されません。',
    'email_change_help_no_mail' => 'メールアドレスを変更した場合、即時反映されます。',
    'updated_email_immediate' => 'プロフィールが更新されました。メールアドレスが変更されました。',
    
    // 2FA管理（プロフィール画面固有）
    'two_fa_management' => '二段階認証管理',
    'recovery_codes' => '回復コード',
    'passkey_devices' => 'Passkey(生体認証)デバイス',
    
    // 回復コード管理
    'recovery_codes_generate_confirm' => '回復コードを生成しますか？<br>生成されたコードは安全な場所に保管してください。',
    'recovery_codes_regenerate_confirm' => '回復コードを再生成しますか？既存の回復コードは全て無効になります。',
    'recovery_codes_generated' => '回復コードが生成されました。',
    'recovery_codes_regenerated' => '回復コードが再生成されました。',
    'recovery_codes_generation_error' => '回復コードの生成に失敗しました。',
    'recovery_codes_regenerate_too_soon' => '回復コードは :time まで再生成できません。',
    
    // Passkey説明
    'passkey_info_title' => 'Passkeyについて',
    'passkey_info_1' => 'デバイスを登録すると、二段階認証で認証コードを入力する必要がなくなります。',
    'passkey_info_2' => '生体認証（指紋認証、顔認証など）またはデバイスのPINでログインできます。',
    'passkey_info_3' => 'Touch ID、Face ID、Windows Helloなどに対応しています。',
    'passkey_info_4' => '管理者はPasskeyデバイスの追加はできません。メンバー本人のみが実行できます。',
    
    // Passkey管理
    'passkey_not_supported' => 'お使いのブラウザはPasskeyに対応していません。',
    'passkey_device_name_prompt' => 'このデバイスの名前を入力してください（例: iPhone、MacBook Pro）',
    'passkey_register_success' => 'Passkeyを登録しました。',
    'passkey_registered' => 'Passkeyを登録しました。',
    'passkey_register_success_title' => 'Passkey登録完了',
    'passkey_register_error' => 'Passkeyの登録に失敗しました。',
    'passkey_register_options_error' => 'Passkey登録オプションの取得に失敗しました。',
    'passkey_cancelled' => 'Passkey登録がキャンセルされました。',
    'passkey_already_registered' => 'このPasskeyは既に登録されています。',
    'passkey_deleted' => 'Passkeyを削除しました。',
    'passkey_delete_success_title' => 'Passkey削除完了',
    'passkey_deleted_all' => '全てのPasskey（:count件）を削除しました。',
    'passkey_not_found' => 'Passkeyが見つかりません。',
    'passkey_delete_error' => 'Passkeyの削除に失敗しました。',
    'passkey_delete_all_error' => 'Passkeyの一括削除に失敗しました。',
    'device_delete_success_title' => 'デバイス削除完了',
    'no_passkeys_to_delete' => '削除するPasskeyがありません。',
    'all_passkeys_deleted' => '全てのPasskey（:count件）を削除しました。',
    'confirm_delete_passkey_title' => 'Passkey削除の確認',
    'confirm_delete_passkey_message' => 'このPasskeyを削除してもよろしいですか？',
    'confirm_delete_all_passkeys_title' => '全Passkey削除の確認',
    'confirm_delete_all_passkeys_message' => '全てのPasskeyを削除してもよろしいですか？この操作は取り消せません。',
    
    // Passkeyデバイス名入力
    'passkey_device_name_title' => 'デバイス名の入力',
    'passkey_device_name_message' => 'このPasskeyデバイスに識別しやすい名前を付けてください。',
    'passkey_device_name_label' => 'デバイス名',
    
    // 回復コード説明
    'recovery_codes_info_title' => '回復コードについて',
    'recovery_codes_info_1' => '回復コードは二段階認証デバイスにアクセスできない場合の緊急手段です。',
    'recovery_codes_info_2' => '回復コードは生成時のみ表示され、その後は表示されません。',
    'recovery_codes_admin_note' => '管理者は回復コードの生成・再生成はできません。メンバー本人のみが実行できます。',
    'recovery_codes_info_3' => '生成された各回復コードは1回のみ使用可能で、使用後は無効になります。',
    'recovery_codes_info_4' => '一度回復コードを生成すると一定時間の間は再生成できません。',
    'recovery_codes_info_5' => '回復コードは他人と共有しないでください。',
    'recovery_codes_info_6' => '生成された回復コードはダウンロード、コピー、スクリーンショット、写真撮影、印刷などの方法で安全な場所に保管してください。',
    
    // 二段階認証要求メッセージ
    'two_factor_requires_mail_server' => '二段階認証を使用するには、基本設定でメールサーバーの設定とテストを完了してください。',
    'two_fa_disabled_notice' => '二段階認証管理を行うには、プロフィール設定で二段階認証を有効にしてください。',
    'passkey_disabled_notice' => 'Passkeyデバイスを追加するには、メンバー全体設定でPasskey認証を有効にしてください。',
    'passkey_no_devices_notice' => 'Passkey認証が有効になっていますが、まだデバイスが登録されていません。<a href=":url" class="underline font-semibold">二段階認証管理</a>でPasskeyデバイスを登録してください。',
];
