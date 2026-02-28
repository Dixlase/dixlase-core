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
    'heading' => 'ログイン試行制限設定',
    'description' => 'ログイン試行回数の制限とロックアウトに関する設定を管理します。',

    // デフォルトログイン試行制限設定
    'default_login_attempt_settings' => 'デフォルトログイン試行制限設定',
    'default_login_attempt_description' => 'メンバーおよびプラグインでカスタム設定が無効な場合に適用されるデフォルトのログイン試行制限です。',

    // 基本設定
    'basic_settings' => '基本設定',
    'enabled' => 'ログイン試行制限を有効化',
    'enabled_help' => '有効にすると、指定回数以上のログイン失敗でアカウントまたはIPアドレスを一時的にロックアウトします。',
    'max_attempts' => '最大試行回数',
    'max_attempts_help' => '同一アカウントに対する最大ログイン試行回数（1-100回）',
    'max_attempts_ip' => 'IP最大試行回数',
    'max_attempts_ip_help' => '同一IPアドレスからの最大ログイン試行回数（1-200回）',
    'time_window' => '時間窓',
    'time_window_help' => 'ログイン試行回数をカウントする時間窓（1-1440分）',
    'lockout_duration' => 'ロックアウト時間',
    'lockout_duration_help' => 'ロックアウト時の待機時間（1-10080分）',
    'lockout_notification_enabled' => 'ロックアウト通知を有効化',
    'lockout_notification_help' => '有効にすると、ロックアウト発生時に管理者にメール通知を送信します',

    // ヒント
    'plugin_custom_hint' => 'プラグイン（DixlaseUsersなど）で独自のログイン試行制限を設定できます。プラグインでカスタム設定が有効な場合、そちらが優先されます。',

    // 二段階認証の詳細設定
    'two_fa_detailed_settings' => '二段階認証の詳細設定',
    'two_fa_detailed_settings_description' => '二段階認証のコード有効期限、再送信間隔、試行制限、リカバリーコードなどの詳細設定を管理します。',

    // ログイン識別子モード設定
    'login_identifier_mode_settings' => 'ログイン識別子設定',
    'login_identifier_mode_description' => '管理画面ログインで受け付ける識別子（メールアドレス / アカウント名）を選択します。',
    'login_identifier_mode' => 'ログイン識別子モード',

    'settings_updated' => 'ログイン関連設定を更新しました。',
    'updated' => 'ログイン関連設定を更新しました。',

    // ログイン通知設定
    'login_notification_settings' => 'ログイン通知設定',
    'login_notification_description' => 'メンバーがログインした際の通知設定を管理します。',
    'login_notification_mode' => 'ログイン通知モード',
];
