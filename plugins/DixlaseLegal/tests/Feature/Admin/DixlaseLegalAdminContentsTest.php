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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminContentsController;
use Plugins\DixlaseLegal\App\Models\DixlaseLegalPage;
use Tests\TestCase;

/**
 * 法務ページコンテンツ管理画面のフィーチャーテスト
 */
class DixlaseLegalAdminContentsTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $indexUrl;

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

        // プラグインのマイグレーションを実行
        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseLegal/database/migrations',
            '--realpath' => false,
        ]);

        // ルートを手動登録
        $adminUrl = config('admin.admin_url', 'admin');
        $this->indexUrl = "/{$adminUrl}/legal-pages/contents";

        $router = app('router');
        $router->prefix($adminUrl)
            ->middleware(['web', 'auth:member'])
            ->group(function () use ($router) {
                $router->prefix('legal-pages')
                    ->name('dixlase-legal::admin.legal-pages.')
                    ->group(function () use ($router) {
                        $router->get('/contents', [DixlaseLegalAdminContentsController::class, 'index'])->name('contents.index');
                        $router->get('/contents/{slug}/{lang}/edit', [DixlaseLegalAdminContentsController::class, 'edit'])->name('contents.edit');
                        $router->match(['patch'], '/contents/{slug}/{lang}', [DixlaseLegalAdminContentsController::class, 'update'])->name('contents.update');
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
     * 管理者が一覧画面にアクセスできること
     */
    public function test_admin_can_access_contents_index(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $response->assertOk();
        $response->assertViewIs('dixlase-legal::admin.legal-pages.contents.index');
        $response->assertViewHas('matrix');
        $response->assertViewHas('languages');
    }

    /**
     * マトリックスデータにページ種別と言語が含まれること
     */
    public function test_index_matrix_contains_page_types_and_languages(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $matrix = $response->viewData('matrix');
        $this->assertIsArray($matrix);
        $this->assertArrayHasKey('privacy-policy', $matrix);
        $this->assertArrayHasKey('languages', $matrix['privacy-policy']);
        $this->assertArrayHasKey('ja', $matrix['privacy-policy']['languages']);
        $this->assertArrayHasKey('en', $matrix['privacy-policy']['languages']);
    }

    /**
     * 既存コンテンツがマトリックスに反映されること
     */
    public function test_existing_content_appears_in_matrix(): void
    {
        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'title' => 'プライバシーポリシー',
            'content_html' => '<p>テスト</p>',
            'editor_type' => 'html',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get($this->indexUrl);

        $matrix = $response->viewData('matrix');
        $jaData = $matrix['privacy-policy']['languages']['ja'];
        $this->assertTrue($jaData['exists']);
        $this->assertEquals('プライバシーポリシー', $jaData['title']);
    }

    /**
     * 管理者が編集画面にアクセスできること
     */
    public function test_admin_can_access_edit_page(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->get("/{$adminUrl}/legal-pages/contents/privacy-policy/ja/edit");

        $response->assertOk();
        $response->assertViewIs('dixlase-legal::admin.legal-pages.contents.edit');
        $response->assertViewHas('slug', 'privacy-policy');
        $response->assertViewHas('lang', 'ja');
    }

    /**
     * 存在しないスラッグで404を返すこと
     */
    public function test_edit_returns_404_for_invalid_slug(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->get("/{$adminUrl}/legal-pages/contents/nonexistent/ja/edit");

        $response->assertNotFound();
    }

    /**
     * 存在しない言語で404を返すこと
     */
    public function test_edit_returns_404_for_invalid_lang(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->get("/{$adminUrl}/legal-pages/contents/privacy-policy/xx/edit");

        $response->assertNotFound();
    }

    /**
     * 新規コンテンツを作成できること
     */
    public function test_can_create_new_content(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->patch("/{$adminUrl}/legal-pages/contents/privacy-policy/ja", [
                'title' => 'プライバシーポリシー',
                'content' => '<p>個人情報の取り扱いについて</p>',
                'editor_type' => 'html',
                'status' => 'draft',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plg_dixlase_legal_pages', [
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'title' => 'プライバシーポリシー',
            'editor_type' => 'html',
            'status' => 'draft',
        ]);
    }

    /**
     * 既存コンテンツを更新できること
     */
    public function test_can_update_existing_content(): void
    {
        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'title' => '旧タイトル',
            'content_html' => '<p>旧コンテンツ</p>',
            'editor_type' => 'html',
            'status' => 'draft',
        ]);

        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->patch("/{$adminUrl}/legal-pages/contents/privacy-policy/ja", [
                'title' => '新タイトル',
                'content' => '<p>新コンテンツ</p>',
                'editor_type' => 'html',
                'status' => 'published',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('plg_dixlase_legal_pages', [
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'title' => '新タイトル',
            'status' => 'published',
        ]);

        // 重複レコードが作成されていないこと
        $this->assertEquals(1, DixlaseLegalPage::where('slug', 'privacy-policy')->where('lang', 'ja')->count());
    }

    /**
     * Markdownコンテンツを保存できること
     */
    public function test_can_save_markdown_content(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->patch("/{$adminUrl}/legal-pages/contents/terms-of-service/en", [
                'title' => 'Terms of Service',
                'content' => '# Terms\n\nPlease read carefully.',
                'editor_type' => 'markdown',
                'status' => 'draft',
            ]);

        $response->assertRedirect();

        $page = DixlaseLegalPage::where('slug', 'terms-of-service')->where('lang', 'en')->first();
        $this->assertNotNull($page);
        $this->assertEquals('markdown', $page->editor_type->value);
        $this->assertNotNull($page->content_markdown);
        $this->assertNull($page->content_html);
    }

    /**
     * scheduledステータスではpublished_atが必須であること
     */
    public function test_scheduled_status_requires_published_at(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->patch("/{$adminUrl}/legal-pages/contents/privacy-policy/ja", [
                'title' => 'テスト',
                'content' => '<p>テスト</p>',
                'editor_type' => 'html',
                'status' => 'scheduled',
            ]);

        $response->assertSessionHasErrors('published_at');
    }

    /**
     * タイトルが255文字を超える場合にバリデーションエラーになること
     */
    public function test_title_exceeding_max_length_fails_validation(): void
    {
        $adminUrl = config('admin.admin_url', 'admin');

        $response = $this->actingAs($this->admin, 'member')
            ->patch("/{$adminUrl}/legal-pages/contents/privacy-policy/ja", [
                'title' => str_repeat('a', 256),
                'content' => '<p>テスト</p>',
                'editor_type' => 'html',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('title');
    }
}
