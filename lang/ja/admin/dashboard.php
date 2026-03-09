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
 */

return [
    'heading' => 'ダッシュボード',
    'description' => 'サイトの概要を確認できます。',

    // モード切替
    'simple_mode' => 'シンプル',
    'detailed_mode' => '詳細',

    // セキュリティ概要
    'security_overview' => 'セキュリティ概要',
    'maintenance_mode' => 'メンテナンスモード',
    'maintenance_mode_active' => 'メンテナンスモードが有効です。訪問者はサイトにアクセスできません。',
    'maintenance_mode_inactive' => 'メンテナンスモードは無効です。サイトは通常稼働中です。',
    'safe_mode' => 'セーフモード',
    'safe_mode_active' => 'セーフモードが有効です。一部の機能が制限されています。',
    'safe_mode_inactive' => 'セーフモードは無効です。すべての機能が利用可能です。',
    'csp_mode' => 'CSPモード',
    'csp_development_warning' => '本番環境で開発用CSPモード（Report-Only）を使用中です。標準モードへの切り替えを推奨します。',
    'csp_mode_ok' => 'CSPモードは適切に設定されています。',
    'debug_mode' => 'デバッグモード',
    'debug_mode_warning' => '本番環境でデバッグモードが有効です。機密情報が漏洩する可能性があります。',
    'debug_mode_ok' => 'デバッグモードは無効です。',
    'two_fa_status' => '二要素認証',
    'two_fa_enabled' => '有効（:method）',
    'two_fa_disabled' => '未設定です。二要素認証の設定を推奨します。',

    // メール状態
    'mail_status' => 'メールサーバー',
    'mail_not_configured' => 'メールサーバーの設定が不完全です。メール送信に失敗する可能性があります。',
    'mail_using_log_driver' => '「:driver」ドライバーを使用中です。メールは実際には配信されません。',
    'mail_configured' => 'メールサーバーは正しく設定されています。',
    'mail_test_not_completed' => 'メールサーバーは設定済みですが、接続・送信テストがまだ完了していません。',

    // CAPTCHA状態
    'captcha_status' => 'CAPTCHA',
    'captcha_not_configured' => 'CAPTCHAが未設定です。スパム防止のため設定を推奨します。',
    'captcha_configured' => 'CAPTCHAは正しく設定されています。',
    'captcha_test_not_completed' => 'CAPTCHAは設定済みですが、認証テストがまだ完了していません。',

    // システム情報
    'system_info' => 'システム情報',
    'php_version' => 'PHPバージョン',
    'laravel_version' => 'Laravelバージョン',
    'dixlase_version' => 'Dixlaseバージョン',

    // コンテンツ概要
    'content_overview' => 'コンテンツ概要',

    // プラグイン通知
    'plugin_notifications' => 'プラグイン通知',
    'view_settings' => '設定を見る',
    'status_info' => '情報',

    // 拡張機能概要
    'extension_overview' => '拡張機能の概要',
    'plugins' => 'プラグイン',
    'themes' => 'テーマ',
    'installed' => 'インストール済み',
    'enabled' => '有効',
    'health_overview' => '健全性の概要',
    'manage_plugins' => 'プラグイン管理',
    'no_audits' => 'プラグインの監査はまだ実行されていません。',

    // メンバー概要
    'member_overview' => 'メンバー概要',
    'total_members' => '合計',
    'active' => 'アクティブ',
    'inactive' => '非アクティブ',
    'role_distribution' => 'ロール分布',
    'two_fa_rate_label' => '2FA有効率',
    'recent_logins' => '最近のログイン',
    'no_recent_logins' => '最近のログイン記録がありません。',
    'manage_members' => 'メンバー管理',

    // ステータスラベル
    'status_ok' => 'OK',
    'status_warning' => '警告',
    'status_recommendation' => '推奨',
];
