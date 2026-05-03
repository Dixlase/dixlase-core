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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    'admin_ip_allowlist' => '管理画面許可IPリスト',
    'admin_ip_allowlist_enabled' => '管理画面IP許可リストを有効にするか',
    'admin_ip_blocklist' => '管理画面ブロックIPリスト',
    'admin_ip_blocklist_enabled' => '管理画面IPブロックリストを有効にするか',
    'allow_themes_with_logic' => 'ロジックを含むテーマを許可するか',
    'allow_undefined_permissions' => '未定義の権限を許可するか',
    'api_rate_limit_enabled' => 'APIレートリミットを有効にするか',
    'api_requests_per_minute' => '1分あたりのAPIリクエスト上限',
    'api_signature_required' => 'API署名を必須にするか',
    'api_timestamp_tolerance_seconds' => 'APIタイムスタンプ許容範囲（秒）',
    'audit_scan_expiration_days' => '監査スキャン期限日数（これを超えると「期限切れ」バッジが表示される）',
    'captcha_driver' => 'CAPTCHAドライバー',
    'captcha_secret_key' => 'CAPTCHAシークレットキー',
    'captcha_site_key' => 'CAPTCHAサイトキー',
    'captcha_toggle' => 'CAPTCHA有効/無効',
    'csp_blocklist_action' => 'CSPブロックリスト検出時のアクション（0: warn, 1: block）',
    'csp_blocklist_detection_enabled' => 'CSPブロックリスト検出を有効にするか',
    'csp_log_violations' => 'CSP違反をログに記録するか',
    'csp_mode' => 'CSPモード（0: development, 1: standard, 2: strict）',
    'csp_toggle' => 'CSP有効/無効',
    'denied_domains' => '拒否ドメイン（改行区切り）',
    'extension_security_preset' => '拡張機能セキュリティプリセット',
    'front_ip_allowlist' => 'フロント許可IPリスト',
    'front_ip_allowlist_enabled' => 'フロントIP許可リストを有効にするか',
    'front_ip_blocklist' => 'フロントブロックIPリスト',
    'front_ip_blocklist_enabled' => 'フロントIPブロックリストを有効にするか',
    'lockout_duration_minutes' => 'ロックアウト時間（分）',
    'lockout_notification_enabled' => 'ロックアウト通知を有効にするか',
    'log_extension_operations' => '拡張機能操作をログに記録するか',
    'login_max_attempts' => 'ログイン試行回数上限',
    'login_notification_enabled' => 'ログイン通知を有効にするか',
    'member_session_lifetime_enabled' => 'メンバー用セッション有効期間を有効にするか',
    'member_session_lifetime_minutes' => 'メンバー用セッション有効期間（分）',
    'notification_log_levels' => '通知するログレベル（カンマ区切り）',
    'notify_on_install' => 'インストール時に通知するか',
    'notify_on_uninstall' => 'アンインストール時に通知するか',
    'password_breach_check_enabled' => '漏洩パスワードチェックを有効にするか',
    'password_min_length' => 'パスワード最小文字数',
    'password_require_mixed_case' => 'パスワードに大文字小文字を必須にするか',
    'password_require_numbers' => 'パスワードに数字を必須にするか',
    'password_require_symbols' => 'パスワードに記号を必須にするか',
    'permission_definition_required' => '権限定義を必須にするか',
    'permission_mismatch_behavior' => '権限不一致時の動作',
    'plugin_max_health_level' => 'プラグインの最大許可健全性レベル',
    'recaptcha_min_score' => 'Google reCAPTCHA最小スコア',
    'recaptcha_version' => 'Google reCAPTCHAバージョン',
    'session_driver' => 'セッションドライバー',
    'session_encryption' => 'セッション暗号化',
    'session_lifetime_minutes' => 'セッション有効期間（分）',
    'signature_required' => '署名を必須にするか',
    'system_notifications_enabled' => 'システム通知を有効にするか',
    'theme_max_health_level' => 'テーマの最大許可健全性レベル',
    'trusted_domains' => '信頼済みドメイン（改行区切り）',
    'two_factor_auth_mode' => '二段階認証モード（optional/required/disabled）',
    'two_factor_auth_toggle' => '二段階認証の有効/無効',
];
