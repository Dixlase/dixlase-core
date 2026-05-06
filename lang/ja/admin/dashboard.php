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
    'heading' => 'ダッシュボード',
    'description' => 'サイトの概要を確認できます。',

    // サイトヘルス
    'site_health' => 'サイトヘルス',
    'maintenance_mode' => 'メンテナンスモード',
    'maintenance_mode_active' => 'メンテナンスモードが有効です。訪問者はサイトにアクセスできません。',
    'maintenance_mode_inactive' => 'メンテナンスモードは無効です。サイトは通常稼働中です。',
    'safe_mode' => 'セーフモード',
    'safe_mode_active' => 'セーフモードが有効です。一部の機能が制限されています。',
    'safe_mode_inactive' => 'セーフモードは無効です。すべての機能が利用可能です。',
    'environment_settings' => '環境設定',
    'environment_local' => 'ローカル開発モードで稼働中です。公開前に APP_ENV を production に変更してください。',
    'environment_staging' => 'ステージングモードで稼働中です。',
    'environment_production' => '本番モードで稼働中です。',
    'environment_other' => '「:env」モードで稼働中です。',
    'https_status' => 'HTTPS',
    'https_force_ssl_enabled' => 'Force SSLが有効です。すべてのリクエストはHTTPSにリダイレクトされます。',
    'https_production_no_force' => '本番環境でHTTPSが強制されていません。Force SSLの有効化を推奨します。',
    'https_current_secure' => '現在のリクエストはHTTPSです。Force SSLは無効です。',
    'https_disabled' => 'HTTPSが使用されていません。HTTPSの有効化を強く推奨します。',
    'csp_mode' => 'CSPモード',
    'csp_disabled' => 'CSPが無効です。XSS攻撃リスクが大幅に上昇します。',
    'csp_development_warning' => '開発用CSPモード（Report-Only）で稼働中です。本番環境では標準モードへの切り替えを推奨します。',
    'csp_mode_ok' => 'CSPモードは適切に設定されています。',
    'debug_mode' => 'デバッグモード',
    'debug_mode_warning' => '本番環境でデバッグモードが有効です。機密情報が漏洩する可能性があります。',
    'debug_mode_dev_ok' => 'デバッグモードが有効です（開発・ステージング環境）。',
    'debug_mode_ok' => 'デバッグモードは無効です。',
    'extension_mode' => '拡張機能セキュリティ設定',
    'extension_mode_strict' => '厳格モードが有効です。署名必須で健全な拡張機能のみ許可されます。',
    'extension_mode_balanced' => 'バランスモードが有効です。信頼できる配布元と監視済みの拡張機能が許可されます。',
    'extension_mode_development' => '開発モードが有効です。セキュリティチェックが緩和されています — 本番環境では非推奨。',
    'extension_mode_custom' => 'カスタムモードが有効です（ユーザー定義の設定）。',
    'public_key_status' => '公開鍵管理',
    'public_key_available' => '鍵管理サイトから公開鍵を取得できています。',
    'public_key_unavailable' => '鍵管理サイトが利用できません。プラグイン署名検証は無効です。',
    'error_notification_status' => 'エラー通知',
    'error_notification_enabled' => 'エラー通知が有効です。重大なイベントはメールで通知されます。',
    'error_notification_disabled' => 'エラー通知が無効です。本番環境では有効化を推奨します。',
    'file_integrity_status' => 'ファイル整合性',
    'file_integrity_ok' => '直近のスキャンでファイル整合性に問題はありませんでした。',
    'file_integrity_warning' => '直近のベースライン以降、:count 件のファイルが変更されています。',
    'file_integrity_critical' => ':count 件の不審なファイルを検出しました。早急に確認してください。',
    'file_integrity_no_baseline' => 'ベースラインスキャンがまだ実行されていません。初回スキャンの実行を推奨します。',
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
    'updates_available_label' => 'アップデート可能',
    'updates_available_summary' => 'プラグイン :plugins 件 / テーマ :themes 件',
    'updates_all_up_to_date' => 'すべて最新です',

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

    // 最近のアクティビティ
    'recent_activity' => '最近のアクティビティ',
    'activity_system' => 'システム',
    'activity_no_entries' => '直近24時間のアクティビティはありません。',
    'activity_failed_count' => ':count件の失敗',
    'activity_warning_count' => ':count件の警告',
    'activity_last_24h' => '直近24時間',
    'view_logs' => 'ログを確認',
    'outcome_success' => '成功',
    'outcome_failure' => '失敗',
    'outcome_denied' => '拒否',
    'outcome_pending' => '保留',
    'outcome_unknown' => '不明',

    // アクションラベル
    'action_login' => 'ログイン',
    'action_logout' => 'ログアウト',
    'action_login_failed' => 'ログイン失敗',
    'action_login_identifier_check' => 'ID確認',
    'action_login_identifier_not_found' => 'ID不一致',
    'action_passkey_auth_success' => 'パスキー認証',
    'action_passkey_auth_failed' => 'パスキー認証失敗',
    'action_new_device_login' => '新規デバイスログイン',
    'action_password_changed' => 'パスワード変更',
    'action_password_reset' => 'パスワードリセット',
    'action_email_changed' => 'メールアドレス変更',
    'action_two_fa_enabled' => '2FA有効化',
    'action_two_fa_disabled' => '2FA無効化',
    'action_two_fa_code_sent' => '2FAコード送信',
    'action_two_fa_code_verified' => '2FA認証成功',
    'action_two_fa_code_failed' => '2FAコード失敗',
    'action_recovery_code_used' => '回復コード使用',
    'action_passkey_registered' => 'パスキー登録',
    'action_passkey_revoked' => 'パスキー削除',
    'action_device_trusted' => 'デバイス信頼',
    'action_device_blocked' => 'デバイスブロック',
    'action_device_removed' => 'デバイス削除',
    'action_ip_blocked' => 'IPブロック',
    'action_ip_allowed' => 'IP許可',
    'action_lockout_triggered' => 'ロックアウト発動',
    'action_lockout_released' => 'ロックアウト解除',
    'action_step_up_auth_required' => 'ステップアップ認証要求',
    'action_step_up_auth_completed' => 'ステップアップ認証完了',
    'action_session_created' => 'セッション作成',
    'action_session_destroyed' => 'セッション破棄',
    'action_forced_logout' => '強制ログアウト',
    'action_plugin_installed' => 'プラグインインストール',
    'action_plugin_enabled' => 'プラグイン有効化',
    'action_plugin_disabled' => 'プラグイン無効化',
    'action_plugin_uninstalled' => 'プラグインアンインストール',
    'action_plugin_updated' => 'プラグイン更新',
    'action_theme_installed' => 'テーマインストール',
    'action_theme_enabled' => 'テーマ有効化',
    'action_theme_disabled' => 'テーマ無効化',
    'action_theme_uninstalled' => 'テーマアンインストール',
    'action_theme_updated' => 'テーマ更新',
    'action_settings_updated' => '設定更新',
    'action_member_created' => 'メンバー作成',
    'action_member_updated' => 'メンバー更新',
    'action_member_deleted' => 'メンバー削除',
    'action_role_changed' => 'ロール変更',

    // ステータスラベル
    'status_ok' => 'OK',
    'status_warning' => '警告',
    'status_recommendation' => '推奨',
    'status_critical' => '重大',
];
