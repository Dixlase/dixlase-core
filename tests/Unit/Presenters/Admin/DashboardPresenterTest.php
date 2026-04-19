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

use App\Enums\AuthenticationMode;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\SecuritySetting;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\SafeModeService;
use App\Services\TwoFa\TwoFaStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardPresenterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * siteHealth は11項目を返すことを確認
     */
    public function test_site_health_returns_eleven_items(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $twoFaStatusService = Mockery::mock(TwoFaStatusService::class);
        $twoFaStatusService->shouldReceive('isTwoFaEnabled')->andReturn(false);
        $this->app->instance(TwoFaStatusService::class, $twoFaStatusService);

        $user = new Member();
        $user->two_fa_mode = AuthenticationMode::Disabled->value;

        $result = DashboardPresenter::siteHealth($user);

        $this->assertCount(11, $result);

        $keys = array_column($result, 'key');
        $this->assertContains('maintenance_mode', $keys);
        $this->assertContains('safe_mode', $keys);
        $this->assertContains('site_mode', $keys);
        $this->assertContains('https', $keys);
        $this->assertContains('csp_mode', $keys);
        $this->assertContains('debug_mode', $keys);
        $this->assertContains('extension_mode', $keys);
        $this->assertContains('public_key', $keys);
        $this->assertContains('error_notification', $keys);
        $this->assertContains('file_integrity', $keys);
        $this->assertContains('two_fa', $keys);
    }

    /**
     * 2FAが無効の場合、recommendationステータスを返す
     */
    public function test_site_health_two_fa_recommendation_when_disabled(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $twoFaStatusService = Mockery::mock(TwoFaStatusService::class);
        $twoFaStatusService->shouldReceive('isTwoFaEnabled')->andReturn(false);
        $this->app->instance(TwoFaStatusService::class, $twoFaStatusService);

        $user = new Member();
        $user->two_fa_mode = AuthenticationMode::Disabled->value;

        $result = DashboardPresenter::siteHealth($user);

        $twoFa = collect($result)->firstWhere('key', 'two_fa');
        $this->assertEquals('recommendation', $twoFa['status']);
    }

    /**
     * 2FAが有効（常に有効）の場合、okステータスを返す
     */
    public function test_site_health_two_fa_ok_when_always_enabled(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $twoFaStatusService = Mockery::mock(TwoFaStatusService::class);
        $twoFaStatusService->shouldReceive('isTwoFaEnabled')->andReturn(true);
        $twoFaStatusService->shouldReceive('getActualTwoFaMode')->andReturn(AuthenticationMode::Always->value);
        $this->app->instance(TwoFaStatusService::class, $twoFaStatusService);

        $user = new Member();
        $user->two_fa_mode = AuthenticationMode::Always->value;

        $result = DashboardPresenter::siteHealth($user);

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

        // All mail tests must be completed for 'ok' status
        BaseSetting::set('mail_connection_tested', true);
        BaseSetting::set('mail_send_tested', true);
        BaseSetting::set('mail_receive_tested', true);

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
     * 各siteHealth項目に必要なキーが含まれる
     */
    public function test_site_health_items_have_required_keys(): void
    {
        $safeModeService = Mockery::mock(SafeModeService::class);
        $safeModeService->shouldReceive('hasAnyActive')->andReturn(false);
        $this->app->instance(SafeModeService::class, $safeModeService);

        $twoFaStatusService = Mockery::mock(TwoFaStatusService::class);
        $twoFaStatusService->shouldReceive('isTwoFaEnabled')->andReturn(false);
        $this->app->instance(TwoFaStatusService::class, $twoFaStatusService);

        $user = new Member();
        $user->two_fa_mode = AuthenticationMode::Disabled->value;

        $result = DashboardPresenter::siteHealth($user);

        foreach ($result as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('url', $item);
            $this->assertArrayHasKey('requires_advanced_mode', $item);
            $this->assertContains($item['status'], ['ok', 'warning', 'recommendation']);
            $this->assertIsBool($item['requires_advanced_mode']);
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

    /**
     * recentActivity は正しい構造を返す
     */
    public function test_recent_activity_returns_correct_structure(): void
    {
        $result = DashboardPresenter::recentActivity();

        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('failed_count', $result['summary']);
        $this->assertArrayHasKey('warning_count', $result['summary']);
        $this->assertIsArray($result['entries']);
    }

    /**
     * recentActivity はエントリに必要なキーが含まれる
     */
    public function test_recent_activity_entries_have_required_keys(): void
    {
        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'actor_name' => 'Test User',
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_INFO,
        ]);

        $result = DashboardPresenter::recentActivity();

        $this->assertCount(1, $result['entries']);

        $entry = $result['entries'][0];
        $this->assertArrayHasKey('action', $entry);
        $this->assertArrayHasKey('category', $entry);
        $this->assertArrayHasKey('actor_name', $entry);
        $this->assertArrayHasKey('outcome', $entry);
        $this->assertArrayHasKey('outcome_color', $entry);
        $this->assertArrayHasKey('severity', $entry);
        $this->assertArrayHasKey('severity_color', $entry);
        $this->assertArrayHasKey('target_label', $entry);
        $this->assertArrayHasKey('occurred_at', $entry);
        $this->assertEquals('login', $entry['action']);
        $this->assertEquals('Test User', $entry['actor_name']);
    }

    /**
     * recentActivity は最大10件に制限される
     */
    public function test_recent_activity_limited_to_ten(): void
    {
        for ($i = 0; $i < 15; $i++) {
            AuditLog::log([
                'category' => AuditLog::CATEGORY_AUTH,
                'action' => AuditLog::ACTION_LOGIN,
                'actor_name' => "User {$i}",
                'occurred_at' => now()->subMinutes($i),
            ]);
        }

        $result = DashboardPresenter::recentActivity();

        $this->assertCount(10, $result['entries']);
    }

    /**
     * recentActivity は24時間以前のエントリを除外する
     */
    public function test_recent_activity_excludes_old_entries(): void
    {
        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'actor_name' => 'Recent',
            'occurred_at' => now()->subHours(1),
        ]);

        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGOUT,
            'actor_name' => 'Old',
            'occurred_at' => now()->subHours(25),
        ]);

        $result = DashboardPresenter::recentActivity();

        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Recent', $result['entries'][0]['actor_name']);
    }

    /**
     * recentActivity のサマリーは失敗と警告を正しくカウントする
     */
    public function test_recent_activity_summary_counts_correctly(): void
    {
        // 成功
        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_INFO,
        ]);

        // 失敗
        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN_FAILED,
            'outcome' => AuditLog::OUTCOME_FAILURE,
            'severity' => AuditLog::SEVERITY_WARNING,
        ]);

        // 拒否
        AuditLog::log([
            'category' => AuditLog::CATEGORY_SECURITY,
            'action' => AuditLog::ACTION_IP_BLOCKED,
            'outcome' => AuditLog::OUTCOME_DENIED,
            'severity' => AuditLog::SEVERITY_ERROR,
        ]);

        $result = DashboardPresenter::recentActivity();

        $this->assertEquals(2, $result['summary']['failed_count']);
        $this->assertEquals(2, $result['summary']['warning_count']);
    }
}
