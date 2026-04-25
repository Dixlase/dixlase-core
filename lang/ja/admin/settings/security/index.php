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
    'heading' => 'セキュリティ設定',
    'description' => 'セキュリティ設定の概要と各機能の状態を確認できます。',
    'password_security' => 'パスワード漏洩チェック',
    'login_attempt_desc' => 'ログイン試行制限とログイン通知',
    'two_fa_desc' => '二段階認証とパスキー設定',
    'session_driver' => 'セッションドライバー',
    'captcha_active' => 'CAPTCHA有効',
    'captcha_test_required' => 'テスト未完了',
    'captcha_disabled' => '無効',
    'ip_active' => 'IP制限有効',
    'ip_inactive' => 'IP制限無効',
    'csp_mode' => 'モード',
    'csp_disabled' => '無効',
    'extensions_desc' => 'プラグイン・テーマのセキュリティポリシー',
    'extensions_preset' => 'セキュリティプリセット',
    'auto_configured' => 'この設定はかんたんモードでは自動で構成されます。',
    'notifications_active' => '通知有効',
    'notifications_disabled' => '通知無効',
    'mail_test_required' => 'メールテスト未完了',
    'integrity_ok' => '問題なし',
    'integrity_warning' => '警告あり',
    'integrity_critical' => '重大な問題',
    'integrity_no_baseline' => 'ベースライン未生成',
    'integrity_not_scanned' => '未スキャン',
    'debug_enabled' => 'デバッグON',
    'latest_integrity_scan' => '最新のファイル整合性スキャン',
    'scan_date' => 'スキャン日時',
    'files_scanned' => 'スキャンファイル数',
    'status' => 'ステータス',
    'view_details' => '詳細を見る',

    'nav' => [
        'password' => 'パスワード',
        'login_attempt' => 'ログイン',
        'two_fa' => '二段階認証',
        'captcha' => 'CAPTCHA',
        'session' => 'セッション',
        'notifications' => 'エラー通知',
        'csp' => 'CSP',
        'extensions' => '拡張機能',
        'ip' => 'IPアクセス制御',
        'integrity' => 'ファイル整合性',
        'environment' => '環境設定',
    ],
];
