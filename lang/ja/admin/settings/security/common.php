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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    // カテゴリ
    'categories' => [
        'auth' => '認証',
        'login' => 'ログイン',
        'session' => 'セッション',
        'captcha' => 'CAPTCHA',
        'ip' => 'IP制限',
        'csp' => 'CSP',
        'extension' => '拡張機能',
        'notification' => '通知',
        'api' => 'API',
        'lockdown' => 'ロックダウン',
    ],

    // 設定ラベル
    'labels' => [
        // 認証
        'two_fa_enabled' => '二段階認証',
        'two_fa_mode' => '二段階認証モード',
        'password_min_length' => 'パスワード最小文字数',
        'password_require_mixed_case' => '大文字小文字を必須にする',
        'password_require_numbers' => '数字を必須にする',
        'password_require_symbols' => '記号を必須にする',
        'password_check_pwned' => '漏洩パスワードチェック',

        // ログイン
        'login_max_attempts' => 'ログイン試行回数上限',
        'login_lockout_duration' => 'ロックアウト時間（分）',
        'login_notification_enabled' => 'ログイン通知',
        'lockout_notification_enabled' => 'ロックアウト通知',

        // セッション
        'session_driver' => 'セッションドライバー',
        'session_lifetime' => 'セッション有効期間（分）',
        'session_encrypt' => 'セッション暗号化',
        'members_session_lifetime_enabled' => 'メンバー用セッション有効期間を有効にする',
        'members_session_lifetime' => 'メンバー用セッション有効期間（分）',

        // CAPTCHA
        'captcha_enabled' => 'CAPTCHA有効',
        'captcha_driver' => 'CAPTCHAドライバー',
        'captcha_site_key' => 'サイトキー',
        'captcha_secret_key' => 'シークレットキー',
        'captcha_google_version' => 'reCAPTCHAバージョン',
        'captcha_google_min_score' => '最小スコア',

        // IP制限
        'enable_allowed_admin_ips' => '管理画面IP許可リストを有効にする',
        'allowed_admin_ips' => '管理画面許可IPリスト',
        'enable_blocked_admin_ips' => '管理画面IPブロックリストを有効にする',
        'blocked_admin_ips' => '管理画面ブロックIPリスト',
        'enable_allowed_front_ips' => 'フロントIP許可リストを有効にする',
        'allowed_front_ips' => 'フロント許可IPリスト',
        'enable_blocked_front_ips' => 'フロントIPブロックリストを有効にする',
        'blocked_front_ips' => 'フロントブロックIPリスト',

        // CSP
        'csp_enabled' => 'CSP有効',
        'csp_mode' => 'CSPモード',
        'csp_log_violations' => 'CSP違反をログに記録',
        'csp_trusted_domains' => '信頼済みドメイン',
        'csp_denied_domains' => '拒否ドメイン',
        'csp_blocklist_check_enabled' => 'ブロックリスト検出を有効にする',
        'csp_blocklist_action' => 'ブロックリスト検出時のアクション',

        // 拡張機能
        'extension_security_preset' => 'セキュリティプリセット',
        'extension_require_signature' => '署名を必須にする',
        'extension_require_permission_definition' => '権限定義を必須にする',
        'extension_allow_undefined_permissions' => '未定義の権限を許可する',
        'extension_plugin_max_health_level' => 'プラグイン最大許可健全性レベル',
        'extension_theme_max_health_level' => 'テーマ最大許可健全性レベル',
        'extension_allow_logic_themes' => 'ロジックを含むテーマを許可する',
        'extension_permission_mismatch_action' => '権限不一致時の動作',
        'extension_notify_on_install' => 'インストール時に通知',
        'extension_notify_on_uninstall' => 'アンインストール時に通知',
        'extension_log_operations' => '拡張機能操作をログに記録',

        // 通知
        'notification_enabled' => 'システム通知を有効にする',
        'notification_log_levels' => '通知するログレベル',

        // API
        'api_rate_limit_enabled' => 'レートリミットを有効にする',
        'api_rate_limit_per_minute' => '1分あたりのリクエスト上限',
        'api_signature_required' => '署名を必須にする',
        'api_timestamp_tolerance' => 'タイムスタンプ許容範囲（秒）',
    ],

    // ヘルプテキスト
    'help' => [
        'password_check_pwned' => 'Have I Been Pwnedデータベースを使用して、漏洩したパスワードをチェックします。',
        'csp_mode' => 'development: 開発用（緩い）、standard: 標準、strict: 厳格',
        'extension_security_preset' => 'relaxed: 緩い、balanced: バランス、strict: 厳格',
    ],
];
