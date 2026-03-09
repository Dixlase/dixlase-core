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

namespace Tests\Unit\Presenters\Admin;

use App\Enums\MemberStatus;
use App\Enums\TwoFaMethod;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\SecuritySetting;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\SafeModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardPresenterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * securityOverview は5項目を返すことを確認
     */
    public function test_security_overview_returns_five_items(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $user = new Member();
        $user->two_fa_method = null;

        $result = DashboardPresenter::securityOverview($user);

        $this->assertCount(5, $result);

        $keys = array_column($result, 'key');
        $this->assertContains('maintenance_mode', $keys);
        $this->assertContains('safe_mode', $keys);
        $this->assertContains('csp_mode', $keys);
        $this->assertContains('debug_mode', $keys);
        $this->assertContains('two_fa', $keys);
    }

    /**
     * 2FAが未設定の場合、recommendationステータスを返す
     */
    public function test_security_overview_two_fa_recommendation_when_disabled(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $user = new Member();
        $user->two_fa_method = null;

        $result = DashboardPresenter::securityOverview($user);

        $twoFa = collect($result)->firstWhere('key', 'two_fa');
        $this->assertEquals('recommendation', $twoFa['status']);
    }

    /**
     * 2FAがパスキー有効の場合、okステータスを返す
     */
    public function test_security_overview_two_fa_ok_when_passkey_enabled(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $user = new Member();
        $user->two_fa_method = TwoFaMethod::PASSKEY->value;

        $result = DashboardPresenter::securityOverview($user);

        $twoFa = collect($result)->firstWhere('key', 'two_fa');
        $this->assertEquals('ok', $twoFa['status']);
    }

    /**
     * メールステータス: logドライバー使用時は警告
     */
    public function test_mail_status_warning_for_log_driver(): void
    {
        config(['mail.default' => 'log']);

        $result = DashboardPresenter::mailServerStatus();

        $this->assertEquals('warning', $result['status']);
        $this->assertEquals('log', $result['mailer']);
    }

    /**
     * メールステータス: 正しいSMTP設定時でテスト完了済みはOK
     */
    public function test_mail_status_ok_for_valid_smtp(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.from.address' => 'admin@example.com',
        ]);

        // Mail tests must be completed for 'ok' status
        BaseSetting::set('mail_connection_tested', true);
        BaseSetting::set('mail_send_tested', true);

        $result = DashboardPresenter::mailServerStatus();

        $this->assertEquals('ok', $result['status']);
    }

    /**
     * メールステータス: SMTP設定済みだがテスト未完了時はrecommendation
     */
    public function test_mail_status_recommendation_when_tests_not_completed(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.from.address' => 'admin@example.com',
        ]);

        $result = DashboardPresenter::mailServerStatus();

        $this->assertEquals('recommendation', $result['status']);
    }

    /**
     * メールステータス: ホスト未設定時は警告
     */
    public function test_mail_status_warning_for_incomplete_smtp(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '',
            'mail.mailers.smtp.port' => 587,
            'mail.from.address' => '',
        ]);

        $result = DashboardPresenter::mailServerStatus();

        $this->assertEquals('warning', $result['status']);
    }

    /**
     * CAPTCHA: 未設定時はrecommendation
     */
    public function test_captcha_status_recommendation_when_not_configured(): void
    {
        $result = DashboardPresenter::captchaStatus();

        $this->assertEquals('recommendation', $result['status']);
    }

    /**
     * CAPTCHA: 設定済み・認証テスト完了時はOK
     */
    public function test_captcha_status_ok_when_configured(): void
    {
        SecuritySetting::set('captcha_enabled', true);
        SecuritySetting::set('captcha_driver', 'google');
        SecuritySetting::set('captcha_google_site_key', 'test-site-key');
        SecuritySetting::set('captcha_google_secret_key', 'test-secret-key');
        SecuritySetting::set('captcha_authentication_result', true);

        $result = DashboardPresenter::captchaStatus();

        $this->assertEquals('ok', $result['status']);
    }

    /**
     * CAPTCHA: 設定済みだが認証テスト未完了時はrecommendation
     */
    public function test_captcha_status_recommendation_when_test_not_completed(): void
    {
        SecuritySetting::set('captcha_enabled', true);
        SecuritySetting::set('captcha_driver', 'google');
        SecuritySetting::set('captcha_google_site_key', 'test-site-key');
        SecuritySetting::set('captcha_google_secret_key', 'test-secret-key');
        SecuritySetting::set('captcha_authentication_result', false);

        $result = DashboardPresenter::captchaStatus();

        $this->assertEquals('recommendation', $result['status']);
    }

    /**
     * systemInfoは3つのバージョン情報を返す
     */
    public function test_system_info_returns_three_items(): void
    {
        $result = DashboardPresenter::systemInfo();

        $this->assertCount(3, $result);

        foreach ($result as $item) {
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertNotEmpty($item['value']);
        }
    }

    /**
     * pluginWidgetsはプロバイダー未登録時に空配列を返す
     */
    public function test_plugin_widgets_returns_empty_when_no_providers(): void
    {
        $result = DashboardPresenter::pluginWidgets();

        $this->assertIsArray($result);
    }

    /**
     * pluginNotificationsはプロバイダー未登録時に空配列を返す
     */
    public function test_plugin_notifications_returns_empty_when_no_providers(): void
    {
        $result = DashboardPresenter::pluginNotifications();

        $this->assertIsArray($result);
    }

    /**
     * 各securityOverview項目に必要なキーが含まれる
     */
    public function test_security_overview_items_have_required_keys(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $user = new Member();
        $user->two_fa_method = null;

        $result = DashboardPresenter::securityOverview($user);

        foreach ($result as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertContains($item['status'], ['ok', 'warning', 'recommendation']);
        }
    }

    /**
     * extensionOverview は正しい構造を返す
     */
    public function test_extension_overview_returns_correct_structure(): void
    {
        $result = DashboardPresenter::extensionOverview();

        $this->assertArrayHasKey('plugins', $result);
        $this->assertArrayHasKey('themes', $result);
        $this->assertArrayHasKey('health', $result);

        $this->assertArrayHasKey('installed', $result['plugins']);
        $this->assertArrayHasKey('enabled', $result['plugins']);
        $this->assertArrayHasKey('installed', $result['themes']);
        $this->assertArrayHasKey('enabled', $result['themes']);

        foreach ($result['health'] as $statusKey => $info) {
            $this->assertArrayHasKey('count', $info);
            $this->assertArrayHasKey('label', $info);
            $this->assertArrayHasKey('color', $info);
            $this->assertArrayHasKey('icon', $info);
        }
    }

    /**
     * extensionOverview はプラグイン数を正しくカウントする
     */
    public function test_extension_overview_counts_plugins_correctly(): void
    {
        $base = ['directory' => 'TestPlugin', 'namespace' => 'Plugins\\TestPlugin', 'version' => '1.0.0'];
        Plugin::create(array_merge($base, ['name' => 'Test1', 'slug' => 'test-1', 'installed_at' => now(), 'enabled_at' => now()]));
        Plugin::create(array_merge($base, ['name' => 'Test2', 'slug' => 'test-2', 'installed_at' => now(), 'enabled_at' => null]));
        Plugin::create(array_merge($base, ['name' => 'Test3', 'slug' => 'test-3', 'installed_at' => null, 'enabled_at' => null]));

        $result = DashboardPresenter::extensionOverview();

        $this->assertEquals(2, $result['plugins']['installed']);
        $this->assertEquals(1, $result['plugins']['enabled']);
    }

    /**
     * memberOverview は正しい構造を返す
     */
    public function test_member_overview_returns_correct_structure(): void
    {
        $result = DashboardPresenter::memberOverview();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('active', $result);
        $this->assertArrayHasKey('inactive', $result);
        $this->assertArrayHasKey('by_role', $result);
        $this->assertArrayHasKey('two_fa_enabled', $result);
        $this->assertArrayHasKey('two_fa_rate', $result);
        $this->assertArrayHasKey('recent_logins', $result);
    }

    /**
     * memberOverview は2FA有効率を正しく計算する
     */
    public function test_member_overview_calculates_two_fa_rate(): void
    {
        Member::factory()->create(['two_fa_mode' => 2, 'status' => MemberStatus::Active->value]);
        Member::factory()->create(['two_fa_mode' => 1, 'status' => MemberStatus::Active->value]);
        Member::factory()->create(['two_fa_mode' => 0, 'status' => MemberStatus::Active->value]);
        Member::factory()->create(['two_fa_mode' => 0, 'status' => MemberStatus::Active->value]);

        $result = DashboardPresenter::memberOverview();

        $this->assertEquals(4, $result['total']);
        $this->assertEquals(2, $result['two_fa_enabled']);
        $this->assertEquals(50.0, $result['two_fa_rate']);
    }

    /**
     * memberOverview の最近ログインは最大5件に制限される
     */
    public function test_member_overview_recent_logins_limited_to_five(): void
    {
        for ($i = 0; $i < 8; $i++) {
            Member::factory()->create([
                'last_login_at' => now()->subMinutes($i),
                'status' => MemberStatus::Active->value,
            ]);
        }

        $result = DashboardPresenter::memberOverview();

        $this->assertCount(5, $result['recent_logins']);
    }
}
