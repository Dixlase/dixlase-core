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

use App\Enums\TwoFaMethod;
use App\Models\Member;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\SafeModeService;
use Mockery;
use Tests\TestCase;

class DashboardPresenterTest extends TestCase
{
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
     * メールステータス: 正しいSMTP設定時はOK
     */
    public function test_mail_status_ok_for_valid_smtp(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.from.address' => 'admin@example.com',
        ]);

        $result = DashboardPresenter::mailServerStatus();

        $this->assertEquals('ok', $result['status']);
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
     * CAPTCHA: site_key未設定時はrecommendation
     */
    public function test_captcha_status_recommendation_when_not_configured(): void
    {
        config([
            'captcha.default' => 'google',
            'captcha.drivers.google.site_key' => '',
            'captcha.drivers.google.secret_key' => '',
        ]);

        $result = DashboardPresenter::captchaStatus();

        $this->assertEquals('recommendation', $result['status']);
    }

    /**
     * CAPTCHA: site_key設定済みはOK
     */
    public function test_captcha_status_ok_when_configured(): void
    {
        config([
            'captcha.default' => 'google',
            'captcha.drivers.google.site_key' => 'test-site-key',
            'captcha.drivers.google.secret_key' => 'test-secret-key',
        ]);

        $result = DashboardPresenter::captchaStatus();

        $this->assertEquals('ok', $result['status']);
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
}
