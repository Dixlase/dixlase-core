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
    'login_notification_global_setting' => 'ログイン通知メールの全体設定',
    'login_attempt_limit_settings' => 'ログイン試行制限設定',
    'two_fa_mode_global_setting' => '二段階認証の全体設定',
    'enabled_two_fa_methods_label' => '有効な二段階認証方法',
    'enabled_two_fa_methods_help' => 'メール認証は常に有効です。Passkeyを有効にすると、メンバーはプロフィール設定で認証方法を選択できます。',
    'email_always_enabled_note' => 'メール認証は全メンバーが使用できる基本的な認証方法として常に有効になっています。',
    'two_fa_expire_settings' => '二段階認証の有効期限設定',
    'two_fa_expire_minutes' => '認証の有効期限',
    'two_factor_expire_minutes_help' => 'メール認証コードとデバイス認証の有効期限を設定します（1〜60分）。',
    'two_fa_resend_interval_seconds' => '認証メール再送信間隔',
    'two_factor_resend_interval_seconds_help' => '認証メールを再送信できるまでの待機時間を設定します（60〜600秒、1〜10分）。',
    'two_fa_attempt_limit_settings' => '2FA試行制限設定',
    'two_fa_max_attempts' => '最大試行回数',
    'two_fa_max_attempts_help' => '2FA認証の最大試行回数を設定します（1〜10回）。全認証方法の合計です。',
    'two_fa_attempt_window' => '試行制限時間枠',
    'two_fa_attempt_window_help' => '試行回数をカウントする時間枠を設定します（5〜60分）。',
    'two_fa_lockout_duration' => 'ロックアウト時間',
    'two_fa_lockout_duration_help' => '試行回数超過後のロックアウト時間を設定します（5〜1440分）。',
    'two_fa_lockout_notification_enabled' => '2FAロックアウト通知',
    'two_fa_lockout_notification_enabled_help' => 'ロックアウト発生時にメール通知を送信します。',
    'passkey_settings' => 'Passkey設定',
    'passkey_enabled' => 'Passkey機能',
    'passkey_enabled_help' => 'Passkey（生体認証）機能の有効/無効を設定します。',
    'max_passkey_devices' => 'Passkey最大登録数',
    'max_passkey_devices_help' => 'メンバー1人あたりのPasskeyデバイス最大登録数を設定します（1〜5台）。',
    'recovery_code_settings' => '回復コード設定',
    'recovery_codes_count' => '回復コード生成個数',
    'recovery_codes_count_help' => 'メンバーごとに生成する回復コードの個数を設定します（1〜5個）。',
    'recovery_code_regenerate_interval' => '回復コード再生成間隔',
    'recovery_code_regenerate_interval_help' => '回復コードを再生成できるまでの待機時間を設定します（1〜168時間）。',
    'captcha_admin_login_settings' => 'CAPTCHA設定（管理画面ログイン用）',
    'captcha_admin_login_settings_description' => '管理画面ログイン時のCAPTCHA認証を設定します。',
    'captcha_screens' => 'CAPTCHA表示画面',
    'captcha_admin_login_enabled' => '管理画面ログインでCAPTCHAを使用',
    'captcha_password_reset_enabled' => 'パスワードリセットでCAPTCHAを使用',
    'captcha_admin_login_help' => '有効にすると、管理画面ログイン時にCAPTCHA認証が要求されます。',
    'captcha_not_enabled' => 'CAPTCHAが有効になっていません。<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">セキュリティ設定</a>でCAPTCHAを有効にしてください。',
    'captcha_not_authenticated' => 'CAPTCHAの認証テストが完了していません。<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">セキュリティ設定</a>で認証テストを行ってください。',
    
    // Phase 2: セキュリティ設定統合
    'login_attempt_limit_description' => 'ログイン試行制限は全体設定で管理されています。',
    'managed_in_security_settings' => 'ログイン試行制限は全体設定 > セキュリティ設定で管理されています。',
    'go_to_security_settings' => 'セキュリティ設定を開く',
    'current_settings' => '現在の設定',
    'status' => '状態',
    'max_attempts' => '最大試行回数',
    'time_window' => '時間窓',
    'lockout_duration' => 'ロックアウト時間',
    'times' => '回',
    'minutes' => '分',
];
