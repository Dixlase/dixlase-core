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

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\LegalPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminLegalPagesController;
use Tests\TestCase;

/**
 * 法務ページURL管理画面のフィーチャーテスト
 */
class DixlaseLegalAdminLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $indexUrl;

    private string $updateUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // インストール済みとして扱う（CheckInstallationReady ミドルウェアのバイパス）
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // プラグインの法務ページ種別設定を手動ロード（ServiceProviderが読み込まれないため）
        config(['admin.legal-pages' => require base_path('plugins/DixlaseLegal/config/admin/legal-pages.php')]);

        // プラグインのビューと翻訳を手動登録（ServiceProviderが読み込まれないため）
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
        $this->indexUrl = "/{$adminUrl}/legal-pages";
        $this->updateUrl = "/{$adminUrl}/legal-pages";

        $router = app('router');
        $router->prefix($adminUrl)
            ->middleware(['web', 'auth:member'])
            ->group(function () use ($router) {
                $router->prefix('legal-pages')
                    ->name('dixlase-legal::admin.legal-pages.')
                    ->group(function () use ($router) {
                        $router->get('/', [DixlaseLegalAdminLegalPagesController::class, 'index'])->name('index');
                        $router->match(['patch'], '/', [DixlaseLegalAdminLegalPagesController::class, 'update'])->name('update');
                    });
            });

        // ルートコレクションの名前解決テーブルを更新
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
     * 管理者が一覧画面にアクセスできること
     */
    public function test_admin_can_access_index(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $response->assertOk();
        $response->assertViewIs('dixlase-legal::admin.legal-pages.index');
        $response->assertViewHas('pages');
    }

    /**
     * ビューにページ種別データが含まれること
     */
    public function test_index_contains_page_types(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $response->assertOk();

        $pages = $response->viewData('pages');
        $this->assertIsArray($pages);
        $this->assertArrayHasKey('privacy-policy', $pages);
        $this->assertArrayHasKey('terms-of-service', $pages);
        $this->assertArrayHasKey('name', $pages['privacy-policy']);
        $this->assertArrayHasKey('icon', $pages['privacy-policy']);
        $this->assertArrayHasKey('url', $pages['privacy-policy']);
    }

    /**
     * 有効なURLで保存できること
     */
    public function test_can_save_valid_urls(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'urls' => [
                    'privacy-policy' => 'https://example.com/privacy',
                    'terms-of-service' => 'https://example.com/terms',
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // LegalPageService を通じて値が保存されたことを確認
        $legalPageService = app(LegalPageService::class);
        $this->assertEquals('https://example.com/privacy', $legalPageService->url('privacy-policy'));
        $this->assertEquals('https://example.com/terms', $legalPageService->url('terms-of-service'));
    }

    /**
     * 空のURLでクリアできること
     */
    public function test_can_clear_url_with_empty_value(): void
    {
        // まずURLを設定
        $legalPageService = app(LegalPageService::class);
        $legalPageService->setUrl('privacy-policy', 'https://example.com/privacy');

        // 空値で更新
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'urls' => [
                    'privacy-policy' => null,
                ],
            ]);

        $response->assertRedirect();

        $this->assertNull($legalPageService->url('privacy-policy'));
    }

    /**
     * 不正なURLでバリデーションエラーになること
     */
    public function test_invalid_url_fails_validation(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'urls' => [
                    'privacy-policy' => 'not-a-valid-url',
                ],
            ]);

        $response->assertSessionHasErrors('urls.privacy-policy');
    }

    /**
     * URLが2048文字を超える場合にバリデーションエラーになること
     */
    public function test_url_exceeding_max_length_fails_validation(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'urls' => [
                    'privacy-policy' => 'https://example.com/' . str_repeat('a', 2040),
                ],
            ]);

        $response->assertSessionHasErrors('urls.privacy-policy');
    }

    /**
     * 保存後にページを再表示すると保存した値が表示されること
     */
    public function test_saved_urls_are_displayed_on_reload(): void
    {
        // URLを保存
        $this->actingAs($this->admin, 'member')
            ->patch($this->updateUrl, [
                'urls' => [
                    'privacy-policy' => 'https://example.com/privacy',
                ],
            ]);

        // 再表示して値が含まれることを確認
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $pages = $response->viewData('pages');
        $this->assertEquals('https://example.com/privacy', $pages['privacy-policy']['url']);
    }
}
