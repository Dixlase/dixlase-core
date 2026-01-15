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

use Illuminate\Database\Seeder;
use App\Models\SecuritySetting;
use App\Enums\LogLevel;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\CspMode;
use App\Enums\CspBlocklistAction;
use App\Enums\SecurityAction;
use App\Enums\CaptchaProvider;

class SecuritySettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System error notification settings
        SecuritySetting::updateOrCreate(
            ['name' => 'notification_enabled'],
            ['value' => '1']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'notification_log_levels'],
            ['value' => implode(',', LogLevel::getDefaultNotificationLevels())]
        );

        // reCAPTCHA settings
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_enabled'],
            ['value' => '0']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_driver'],
            ['value' => CaptchaProvider::GOOGLE->value]
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_version'],
            ['value' => 'v3']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_min_score'],
            ['value' => '0.5']
        );



        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_project_id'],
            ['value' => '']
        );



        // CAPTCHA authentication result (boolean型、常にレコード保持)
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_authentication_result'],
            ['value' => '0']
        );


        // Google reCAPTCHA プロバイダー固有設定
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_site_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_secret_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enabled'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_verified'],
            ['value' => '0']
        );

        // Google reCAPTCHA Enterprise プロバイダー固有設定
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_site_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_secret_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_project_id'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_min_score'],
            ['value' => '0.5']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_enabled'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_enterprise_verified'],
            ['value' => '0']
        );

        // Cloudflare Turnstile プロバイダー固有設定
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_site_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_secret_key'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_enabled'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_verified'],
            ['value' => '0']
        );

        // フェイルオーバー設定
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_auto_failover_enabled'],
            ['value' => '1']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_failover_priority'],
            ['value' => 'turnstile,google_enterprise,google']
        );

        // IP Restriction settings
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_allowed_admin_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'allowed_admin_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_blocked_admin_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'blocked_admin_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_allowed_front_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'allowed_front_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_blocked_front_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'blocked_front_ips'],
            ['value' => '']
        );

        // Session management settings
        SecuritySetting::updateOrCreate(
            ['name' => 'session_driver'],
            ['value' => config('session.driver', 'file')]
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'session_encrypt'],
            ['value' => config('session.encrypt', false) ? '1' : '0']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'session_lifetime'],
            ['value' => (string) config('session.lifetime', 120)]
        );

        // Extension security settings (plugins and themes)
        // プリセットモード（デフォルト: balanced）
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_security_preset'],
            ['value' => ExtensionSecurityPreset::Balanced->value]
        );

        // 署名を必須にするか
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_require_signature'],
            ['value' => '0']
        );

        // 権限定義を必須にするか
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_require_permission_definition'],
            ['value' => '0']
        );

        // 未定義の権限を許可するか
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_allow_undefined_permissions'],
            ['value' => '1']
        );

        // プラグインの最大許可健全性レベル
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_plugin_max_health_level'],
            ['value' => (string) ExtensionSecurityLevel::Warning->value]
        );

        // テーマの最大許可健全性レベル
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_theme_max_health_level'],
            ['value' => (string) ExtensionSecurityLevel::NeedsAttention->value]
        );

        // ロジックを含むテーマを許可するか
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_allow_logic_themes'],
            ['value' => '1']
        );

        // 権限不一致時の動作（warn: 警告のみ, block: ブロック）
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_permission_mismatch_action'],
            ['value' => SecurityAction::default()->toString()]
        );

        // Extension notification settings (拡張機能操作通知)
        // プラグイン・テーマのインストール時にメール通知
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_notify_on_install'],
            ['value' => '1']
        );

        // プラグイン・テーマのアンインストール時にメール通知
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_notify_on_uninstall'],
            ['value' => '1']
        );

        // プラグイン・テーマの有効化時にメール通知
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_notify_on_enable'],
            ['value' => '1']
        );

        // プラグイン・テーマの無効化時にメール通知
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_notify_on_disable'],
            ['value' => '0']
        );

        // 健全性が「良好」以外の拡張機能操作時に警告メール
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_notify_on_unhealthy'],
            ['value' => '1']
        );

        // 拡張機能操作をログに記録
        SecuritySetting::updateOrCreate(
            ['name' => 'extension_log_operations'],
            ['value' => '1']
        );

        // CSP (Content Security Policy) settings
        // CSP有効/無効
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_enabled'],
            ['value' => '1']
        );

        // CSPモード（0: 開発, 1: 標準, 2: 厳格）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_mode'],
            ['value' => (string) CspMode::default()->value]
        );

        // CSP違反をログに記録
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_log_violations'],
            ['value' => '1']
        );

        // 信頼済みドメイン（改行区切り）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_trusted_domains'],
            ['value' => '']
        );

        // 拒否ドメイン（改行区切り）
        // プラグイン/テーマがこれらのドメインを使用しようとしても、CSPによりブロックされる
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_denied_domains'],
            ['value' => '']
        );

        // カスタムディレクティブ（JSON形式）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_custom_directives'],
            ['value' => '']
        );

        // CSPブロックリスト検出有効/無効
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_blocklist_check_enabled'],
            ['value' => '0']
        );

        // CSPブロックリスト検出時のアクション（0: 警告, 1: ブロック）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_blocklist_action'],
            ['value' => (string) CspBlocklistAction::default()->value]
        );

        // CSPブロックリスト有効カテゴリ（カンマ区切り）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_blocklist_enabled_categories'],
            ['value' => '']
        );

        // 開発ツール関連のCSP違反を除外（Vite開発サーバー、Windsurf/MCPブラウザプレビュー等）
        SecuritySetting::updateOrCreate(
            ['name' => 'csp_exclude_dev_tools'],
            ['value' => '1']
        );

    }
}
