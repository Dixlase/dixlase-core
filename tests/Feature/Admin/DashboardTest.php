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

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            \App\Http\Middleware\EnsureEmailIsVerified::class,
            \App\Http\Middleware\CheckMenuAccess::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    /**
     * 認証済みユーザーがダッシュボードにアクセスできることを確認
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    /**
     * ダッシュボードにセキュリティ概要データが渡されることを確認
     */
    public function test_dashboard_has_security_overview_data(): void
    {
        // securityOverview は siteHealth に統合された
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        $response->assertViewHas('siteHealth');
        $this->assertIsArray($response->viewData('siteHealth'));
    }

    /**
     * ダッシュボードにメール状態データが渡されることを確認
     */
    public function test_dashboard_has_mail_status_data(): void
    {
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        // mailStatus は siteHealth 内の 'mail' エントリに統合された
        $response->assertViewHas('siteHealth');
        $items = $response->viewData('siteHealth');
        $mail = collect($items)->firstWhere('key', 'mail');
        $this->assertNotNull($mail, 'siteHealth に mail エントリが含まれていること');
    }

    /**
     * ダッシュボードにCAPTCHA状態データが渡されることを確認
     */
    public function test_dashboard_has_captcha_status_data(): void
    {
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        // captchaStatus は siteHealth 内の 'captcha' エントリに統合された
        $response->assertViewHas('siteHealth');
        $items = $response->viewData('siteHealth');
        $captcha = collect($items)->firstWhere('key', 'captcha');
        $this->assertNotNull($captcha, 'siteHealth に captcha エントリが含まれていること');
    }

    /**
     * ダッシュボードにシステム情報データが渡されることを確認
     */
    public function test_dashboard_has_system_info_data(): void
    {
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        $response->assertViewHas('systemInfo');
        $systemInfo = $response->viewData('systemInfo');
        $this->assertIsArray($systemInfo);
        $this->assertCount(3, $systemInfo);
    }

    /**
     * ダッシュボードにプラグインウィジェットデータが渡されることを確認
     */
    public function test_dashboard_has_plugin_widgets_data(): void
    {
        $user = Member::factory()->create();

        $response = $this->actingAs($user, 'member')->get(route('admin.dashboard'));

        $response->assertViewHas('pluginWidgets');
        $pluginWidgets = $response->viewData('pluginWidgets');
        $this->assertIsArray($pluginWidgets);
    }

    /**
     * 未認証ユーザーがダッシュボードにアクセスできないことを確認
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect();
    }
}
