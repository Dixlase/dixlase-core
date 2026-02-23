<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\Tests\Feature\Admin;

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminSettingsController;
use Tests\TestCase;

/**
 * 法務設定画面のフィーチャーテスト
 */
class DixlaseLegalAdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $indexUrl;

    private string $updateUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // インストール済みとして扱う
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // プラグインのビューと翻訳を手動登録
        $this->app['view']->addNamespace(
            'dixlase-legal',
            base_path('plugins/DixlaseLegal/resources/views')
        );
        $this->app['translator']->addNamespace(
            'dixlase-legal',
            base_path('plugins/DixlaseLegal/lang')
        );

        // ルートを手動登録
        $adminUrl = config('admin.admin_url', 'admin');
        $this->indexUrl = "/{$adminUrl}/legal-pages/settings";
        $this->updateUrl = "/{$adminUrl}/legal-pages/settings";

        $router = app('router');
        $router->prefix($adminUrl)
            ->middleware(['web', 'auth:member'])
            ->group(function () use ($router) {
                $router->prefix('legal-pages')
                    ->name('dixlase-legal::admin.legal-pages.')
                    ->group(function () use ($router) {
                        $router->get('/settings', [DixlaseLegalAdminSettingsController::class, 'index'])->name('settings.index');
                        $router->match(['patch'], '/settings', [DixlaseLegalAdminSettingsController::class, 'update'])->name('settings.update');
                    });
            });

        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    /**
     * 未ログインユーザーはリダイレクトされること
     */
    public function test_guest_is_redirected(): void
    {
        $response = $this->get($this->indexUrl);

        $response->assertRedirect();
    }

    /**
     * 管理者が設定画面にアクセスできること
     */
    public function test_admin_can_access_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $response->assertOk();
        $response->assertViewIs('dixlase-legal::admin.legal-pages.settings.index');
        $response->assertViewHas('cookieConsentEnabled');
    }

    /**
     * デフォルトではクッキー同意が無効であること
     */
    public function test_cookie_consent_is_disabled_by_default(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $this->assertFalse($response->viewData('cookieConsentEnabled'));
    }

    /**
     * クッキー同意を有効にできること
     */
    public function test_can_enable_cookie_consent(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'cookie_consent_enabled' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settingRepo = app(BaseSettingRepositoryInterface::class);
        $this->assertEquals('1', $settingRepo->get('dixlase_legal_cookie_consent_enabled'));
    }

    /**
     * クッキー同意を無効にできること
     */
    public function test_can_disable_cookie_consent(): void
    {
        // まずコントローラー経由で有効化
        $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'cookie_consent_enabled' => '1',
            ]);

        // 無効化（トグルOFFの場合はフォームからフィールドが送信されない）
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, []);

        $response->assertRedirect();

        $settingRepo = app(BaseSettingRepositoryInterface::class);
        $this->assertEquals('0', $settingRepo->get('dixlase_legal_cookie_consent_enabled'));
    }

    /**
     * 有効化後に画面に反映されること
     */
    public function test_enabled_setting_is_displayed_on_reload(): void
    {
        // コントローラー経由で有効化
        $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'cookie_consent_enabled' => '1',
            ]);

        // キャッシュをクリアして最新の値を取得できるようにする
        $this->app['cache']->flush();

        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $this->assertTrue($response->viewData('cookieConsentEnabled'));
    }
}
