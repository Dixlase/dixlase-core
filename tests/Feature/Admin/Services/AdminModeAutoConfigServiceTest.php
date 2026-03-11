<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Feature\Admin\Services;

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Enums\LogLevel;
use App\Helpers\ConfigHelper;
use App\Services\AdminModeAutoConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminModeAutoConfigService の各自動設定メソッドのテスト
 */
class AdminModeAutoConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminModeAutoConfigService $service;

    private SecuritySettingRepositoryInterface $securitySettingRepository;

    private MediaSettingRepositoryInterface $mediaSettingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securitySettingRepository = app(SecuritySettingRepositoryInterface::class);
        $this->mediaSettingRepository = app(MediaSettingRepositoryInterface::class);
        $this->service = app(AdminModeAutoConfigService::class);
    }

    // ========================================
    // applyAll: 全メソッド一括適用
    // ========================================

    public function test_apply_all_returns_results_for_all_menu_keys(): void
    {
        $results = $this->service->applyAll();

        $this->assertIsArray($results);

        $expectedKeys = [
            'settings.security.password',
            'settings.security.login',
            'settings.security.two-fa',
            'settings.security.notifications',
            'settings.security.session',
            'settings.security.csp',
            'settings.security.environment',
            'media.settings',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $results, "Missing key: {$key}");
            $this->assertTrue($results[$key], "Failed to apply: {$key}");
        }
    }

    // ========================================
    // applyPasswordDefaults: パスワード設定
    // ========================================

    public function test_apply_password_defaults_sets_correct_values(): void
    {
        $this->service->applyPasswordDefaults();

        $this->assertEquals('8', $this->securitySettingRepository->get('password_min_length'));
        $this->assertEquals('1', $this->securitySettingRepository->get('password_require_uppercase'));
        $this->assertEquals('1', $this->securitySettingRepository->get('password_require_number'));
        $this->assertEquals('1', $this->securitySettingRepository->get('password_require_symbol'));
        $this->assertEquals('1', $this->securitySettingRepository->get('password_reset_enabled'));
        $this->assertEquals('1', $this->securitySettingRepository->get('pwned_password_check_enabled'));
    }

    public function test_apply_password_defaults_overwrites_existing_values(): void
    {
        // カスタム値を事前設定
        $this->securitySettingRepository->set('password_min_length', '16');
        $this->securitySettingRepository->set('password_require_uppercase', '0');

        $this->service->applyPasswordDefaults();

        $this->assertEquals('8', $this->securitySettingRepository->get('password_min_length'));
        $this->assertEquals('1', $this->securitySettingRepository->get('password_require_uppercase'));
    }

    // ========================================
    // applyLoginDefaults: ログイン設定
    // ========================================

    public function test_apply_login_defaults_sets_correct_values(): void
    {
        $this->service->applyLoginDefaults();

        $this->assertEquals('1', $this->securitySettingRepository->get('login_attempt_limit_enabled'));
        $this->assertEquals('5', $this->securitySettingRepository->get('login_attempt_max_attempts'));
        $this->assertEquals('20', $this->securitySettingRepository->get('login_attempt_max_attempts_ip'));
        $this->assertEquals('15', $this->securitySettingRepository->get('login_attempt_time_window'));
        $this->assertEquals('30', $this->securitySettingRepository->get('login_attempt_lockout_duration'));
        $this->assertEquals('1', $this->securitySettingRepository->get('login_attempt_lockout_notification_enabled'));
    }

    // ========================================
    // applyTwoFaDefaults: 二段階認証設定
    // ========================================

    public function test_apply_two_fa_defaults_sets_correct_values(): void
    {
        $this->service->applyTwoFaDefaults();

        $this->assertEquals('5', $this->securitySettingRepository->get('two_fa_passkey_max_devices'));
        $this->assertEquals('10', $this->securitySettingRepository->get('two_fa_expire_minutes'));
        $this->assertEquals('60', $this->securitySettingRepository->get('two_fa_resend_interval_seconds'));
        $this->assertEquals('5', $this->securitySettingRepository->get('two_fa_max_attempts'));
        $this->assertEquals('15', $this->securitySettingRepository->get('two_fa_attempt_window'));
        $this->assertEquals('30', $this->securitySettingRepository->get('two_fa_lockout_duration'));
        $this->assertEquals('1', $this->securitySettingRepository->get('two_fa_lockout_notification_enabled'));
        $this->assertEquals('10', $this->securitySettingRepository->get('two_fa_recovery_codes_count'));
        $this->assertEquals('1', $this->securitySettingRepository->get('two_fa_recovery_code_regenerate_interval'));
    }

    // ========================================
    // applyNotificationDefaults: エラー通知設定
    // ========================================

    public function test_apply_notification_defaults_sets_correct_values(): void
    {
        $this->service->applyNotificationDefaults();

        $this->assertEquals('1', $this->securitySettingRepository->get('notification_enabled'));

        $expectedLevels = implode(',', [
            LogLevel::Emergency->value,
            LogLevel::Alert->value,
            LogLevel::Critical->value,
        ]);
        $this->assertEquals($expectedLevels, $this->securitySettingRepository->get('notification_log_levels'));
    }

    // ========================================
    // applySessionDefaults: セッション設定
    // ========================================

    public function test_apply_session_defaults_sets_correct_values(): void
    {
        // カスタム値を事前設定
        ConfigHelper::setSessionLifetime(30);
        ConfigHelper::setSessionEncrypt(true);

        $this->service->applySessionDefaults();

        $this->assertEquals(120, ConfigHelper::getSessionLifetime());
        $this->assertFalse(ConfigHelper::getSessionEncrypt());
    }

    // ========================================
    // applyCspDefaults: CSP設定
    // ========================================

    public function test_apply_csp_defaults_sets_correct_values(): void
    {
        $this->service->applyCspDefaults();

        $this->assertEquals('1', $this->securitySettingRepository->get('csp_enabled'));
        $this->assertEquals((string) CspMode::Standard->value, $this->securitySettingRepository->get('csp_mode'));
        $this->assertEquals('1', $this->securitySettingRepository->get('csp_log_violations'));
        $this->assertEquals('1', $this->securitySettingRepository->get('csp_exclude_dev_tools'));
        $this->assertEquals('1', $this->securitySettingRepository->get('csp_blocklist_check_enabled'));
        $this->assertEquals((string) CspBlocklistAction::Warn->value, $this->securitySettingRepository->get('csp_blocklist_action'));
        $this->assertEquals('tracking,malware,phishing,cryptominer', $this->securitySettingRepository->get('csp_blocklist_enabled_categories'));
    }

    // ========================================
    // applyEnvironmentDefaults: 環境設定
    // ========================================

    public function test_apply_environment_defaults_sets_correct_values(): void
    {
        // .envファイルのバックアップ
        $envPath = base_path('.env');
        $originalEnv = file_get_contents($envPath);

        try {
            $this->service->applyEnvironmentDefaults();

            // .envファイルの内容を直接確認（テスト中はconfigが再読み込みされないため）
            $envContent = file_get_contents($envPath);
            $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $envContent);
            $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $envContent);
        } finally {
            // .envファイルを元に戻す
            file_put_contents($envPath, $originalEnv);
        }
    }

    // ========================================
    // applyMediaDefaults: メディア設定
    // ========================================

    public function test_apply_media_defaults_sets_correct_values(): void
    {
        $this->service->applyMediaDefaults();

        $this->assertEquals('1', $this->mediaSettingRepository->get('mime_validation_enabled'));
        $this->assertEquals('1', $this->mediaSettingRepository->get('svg_sanitization_enabled'));
        $this->assertEquals('1', $this->mediaSettingRepository->get('zip_security_enabled'));
    }

    public function test_apply_media_defaults_overwrites_existing_values(): void
    {
        // セキュリティ機能を無効化した状態から適用
        $this->mediaSettingRepository->set('mime_validation_enabled', '0');
        $this->mediaSettingRepository->set('svg_sanitization_enabled', '0');

        $this->service->applyMediaDefaults();

        $this->assertEquals('1', $this->mediaSettingRepository->get('mime_validation_enabled'));
        $this->assertEquals('1', $this->mediaSettingRepository->get('svg_sanitization_enabled'));
    }
}
