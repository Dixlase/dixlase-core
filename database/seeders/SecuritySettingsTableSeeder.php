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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace Database\Seeders;

use App\Enums\CaptchaProvider;
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\LogLevel;
use App\Enums\SecurityAction;
use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class SecuritySettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System error notification settings
        GlobalSetting::updateOrCreate(
            ['name' => 'notification_enabled'],
            ['value' => '1']
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'notification_log_levels'],
            ['value' => implode(',', LogLevel::getDefaultNotificationLevels())]
        );

        // Default password policy settings (moved from MembersSettingsSeeder)
        GlobalSetting::updateOrCreate(
            ['name' => 'password_min_length'],
            ['value' => '8']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'password_require_uppercase'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'password_require_number'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'password_require_symbol'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'password_reset_enabled'],
            ['value' => '0']
        );
        // Password security settings
        GlobalSetting::updateOrCreate(
            ['name' => 'pwned_password_check_enabled'],
            ['value' => '1']
        );

        // Default login attempt limit settings (moved from MembersSettingsSeeder)
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_limit_enabled'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_max_attempts'],
            ['value' => '5']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_max_attempts_ip'],
            ['value' => '10']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_time_window'],
            ['value' => '15']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_lockout_duration'],
            ['value' => '30']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_attempt_lockout_notification_enabled'],
            ['value' => '1']
        );

        // Login notification settings (moved from MembersSettingsSeeder)
        GlobalSetting::updateOrCreate(
            ['name' => 'login_notification_mode'],
            ['value' => '3']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_notification_send_to_system'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'login_notification_system_email'],
            ['value' => '']
        );

        // Two-factor authentication basic settings (moved from MembersSettingsSeeder)
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_mode'],
            ['value' => '3'] // 0=無効, 1=異なるデバイス, 2=常に有効, 3=プロフィール設定に従う
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_passkey_mode'],
            ['value' => '1'] // 0=無効, 1=有効（デフォルト: 有効）
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_passkey_max_devices'],
            ['value' => '3'] // Passkey最大登録数（1-5）
        );

        // Two-factor authentication detailed settings (moved from MembersSettingsSeeder)
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_expire_minutes'],
            ['value' => '5']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_resend_interval_seconds'],
            ['value' => '60']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_max_attempts'],
            ['value' => '5']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_attempt_window'],
            ['value' => '15']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_lockout_duration'],
            ['value' => '30']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_lockout_notification_enabled'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_recovery_codes_count'],
            ['value' => '5']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_recovery_code_regenerate_interval'],
            ['value' => '24']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'two_fa_verification_timeout'],
            ['value' => '10']
        );

        // reCAPTCHA settings
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_enabled'],
            ['value' => '0']
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_driver'],
            ['value' => CaptchaProvider::GOOGLE->value]
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_version'],
            ['value' => 'v3']
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_min_score'],
            ['value' => '0.5']
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_project_id'],
            ['value' => '']
        );

        // CAPTCHA authentication result (boolean型、常にレコード保持)
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_authentication_result'],
            ['value' => '0']
        );

        // Google reCAPTCHA プロバイダー固有設定
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_site_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_secret_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enabled'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_verified'],
            ['value' => '0']
        );

        // Google reCAPTCHA Enterprise プロバイダー固有設定
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_site_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_secret_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_project_id'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_min_score'],
            ['value' => '0.5']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_enabled'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_verified'],
            ['value' => '0']
        );

        // Cloudflare Turnstile プロバイダー固有設定
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_turnstile_site_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_turnstile_secret_key'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_turnstile_enabled'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_turnstile_verified'],
            ['value' => '0']
        );

        // フェイルオーバー設定
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_auto_failover_enabled'],
            ['value' => '1']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'captcha_failover_priority'],
            ['value' => 'turnstile,google_enterprise,google']
        );

        // IP Restriction settings
        GlobalSetting::updateOrCreate(
            ['name' => 'enable_allowed_admin_ips'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'allowed_admin_ips'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'enable_blocked_admin_ips'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'blocked_admin_ips'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'enable_allowed_front_ips'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'allowed_front_ips'],
            ['value' => '']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'enable_blocked_front_ips'],
            ['value' => '0']
        );
        GlobalSetting::updateOrCreate(
            ['name' => 'blocked_front_ips'],
            ['value' => '']
        );

        // Session management settings
        GlobalSetting::updateOrCreate(
            ['name' => 'session_driver'],
            ['value' => config('session.driver', 'file')]
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'session_encrypt'],
            ['value' => config('session.encrypt', false) ? '1' : '0']
        );

        GlobalSetting::updateOrCreate(
            ['name' => 'session_lifetime'],
            ['value' => (string) config('session.lifetime', 120)]
        );

        // Extension security settings (plugins and themes)
        // プリセットモード（デフォルト: balanced）
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_security_preset'],
            ['value' => ExtensionSecurityPreset::Balanced->value]
        );

        // 署名を必須にするか
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_require_signature'],
            ['value' => '0']
        );

        // 権限定義を必須にするか
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_require_permission_definition'],
            ['value' => '0']
        );

        // 未定義の権限を許可するか
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_allow_undefined_permissions'],
            ['value' => '1']
        );

        // プラグインの最大許可健全性レベル
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_plugin_max_health_level'],
            ['value' => (string) ExtensionSecurityLevel::Warning->value]
        );

        // テーマの最大許可健全性レベル
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_theme_max_health_level'],
            ['value' => (string) ExtensionSecurityLevel::NeedsAttention->value]
        );

        // ロジックを含むテーマを許可するか
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_allow_logic_themes'],
            ['value' => '1']
        );

        // 権限不一致時の動作（warn: 警告のみ, block: ブロック）
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_permission_mismatch_action'],
            ['value' => SecurityAction::default()->toString()]
        );

        // Extension notification settings (拡張機能操作通知)
        // プラグイン・テーマのインストール時にメール通知
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_notify_on_install'],
            ['value' => '1']
        );

        // プラグイン・テーマのアンインストール時にメール通知
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_notify_on_uninstall'],
            ['value' => '1']
        );

        // プラグイン・テーマの有効化時にメール通知
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_notify_on_enable'],
            ['value' => '1']
        );

        // プラグイン・テーマの無効化時にメール通知
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_notify_on_disable'],
            ['value' => '0']
        );

        // 健全性が「良好」以外の拡張機能操作時に警告メール
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_notify_on_unhealthy'],
            ['value' => '1']
        );

        // 拡張機能操作をログに記録
        GlobalSetting::updateOrCreate(
            ['name' => 'extension_log_operations'],
            ['value' => '1']
        );

        // CSP (Content Security Policy) settings
        // CSP有効/無効
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_enabled'],
            ['value' => '1']
        );

        // CSPモード（0: 開発, 1: 標準, 2: 厳格）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_mode'],
            ['value' => (string) CspMode::default()->value]
        );

        // CSP違反をログに記録
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_log_violations'],
            ['value' => '1']
        );

        // 信頼済みドメイン（改行区切り）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_trusted_domains'],
            ['value' => '']
        );

        // 拒否ドメイン（改行区切り）
        // プラグイン/テーマがこれらのドメインを使用しようとしても、CSPによりブロックされる
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_denied_domains'],
            ['value' => '']
        );

        // カスタムディレクティブ（JSON形式）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_custom_directives'],
            ['value' => '']
        );

        // CSPブロックリスト検出有効/無効
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_blocklist_check_enabled'],
            ['value' => '0']
        );

        // CSPブロックリスト検出時のアクション（0: 警告, 1: ブロック）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_blocklist_action'],
            ['value' => (string) CspBlocklistAction::default()->value]
        );

        // CSPブロックリスト有効カテゴリ（カンマ区切り）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_blocklist_enabled_categories'],
            ['value' => '']
        );

        // 開発ツール関連のCSP違反を除外（Vite開発サーバー、Windsurf/MCPブラウザプレビュー等）
        GlobalSetting::updateOrCreate(
            ['name' => 'csp_exclude_dev_tools'],
            ['value' => '1']
        );
    }
}
