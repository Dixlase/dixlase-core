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
            ['value' => 'google']
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

        // 統一キー設定（プロバイダー共通）
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_site_key'],
            ['value' => '']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_secret_key'],
            ['value' => '']
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
            ['value' => 'warn']
        );

    }
}
