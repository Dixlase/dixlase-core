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

namespace Plugins\DixlaseLegal\Tests\Unit;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlaseLegal\App\Models\DixlaseLegalPage;
use Tests\TestCase;

/**
 * DixlaseLegalPage モデルのユニットテスト
 */
class DixlaseLegalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // プラグインのマイグレーションを実行
        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseLegal/database/migrations',
            '--realpath' => false,
        ]);
    }

    /**
     * モデルのテーブル名が正しいこと
     */
    public function test_model_has_correct_table(): void
    {
        $page = new DixlaseLegalPage;
        $this->assertEquals('plg_dixlase_legal_pages', $page->getTable());
    }

    /**
     * ステータスが正しくキャストされること
     */
    public function test_status_is_cast_to_enum(): void
    {
        $page = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'title' => 'テスト',
            'content_html' => '<p>テスト</p>',
            'editor_type' => 'html',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertInstanceOf(ContentStatus::class, $page->status);
        $this->assertEquals(ContentStatus::PUBLISHED, $page->status);
    }

    /**
     * エディタータイプが正しくキャストされること
     */
    public function test_editor_type_is_cast_to_enum(): void
    {
        $page = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'editor_type' => 'markdown',
            'status' => 'draft',
        ]);

        $this->assertInstanceOf(ContentEditorType::class, $page->editor_type);
        $this->assertEquals(ContentEditorType::MARKDOWN, $page->editor_type);
    }

    /**
     * isPublished() が正しく動作すること
     */
    public function test_is_published(): void
    {
        $published = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draft = DixlaseLegalPage::create([
            'slug' => 'terms-of-service',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        $this->assertTrue($published->isPublished());
        $this->assertFalse($draft->isPublished());
    }

    /**
     * getContentByEditorType() がHTMLコンテンツを返すこと
     */
    public function test_get_content_by_editor_type_html(): void
    {
        $page = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'content_html' => '<p>HTML内容</p>',
            'content_markdown' => null,
            'editor_type' => 'html',
            'status' => 'draft',
        ]);

        $this->assertEquals('<p>HTML内容</p>', $page->getContentByEditorType());
    }

    /**
     * getContentByEditorType() がMarkdownコンテンツを返すこと
     */
    public function test_get_content_by_editor_type_markdown(): void
    {
        $page = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'content_html' => null,
            'content_markdown' => '# プライバシーポリシー',
            'editor_type' => 'markdown',
            'status' => 'draft',
        ]);

        $this->assertEquals('# プライバシーポリシー', $page->getContentByEditorType());
    }

    /**
     * scopePublished() が公開済みのみ返すこと
     */
    public function test_scope_published(): void
    {
        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'status' => 'published',
            'published_at' => now(),
        ]);

        DixlaseLegalPage::create([
            'slug' => 'terms-of-service',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        $published = DixlaseLegalPage::published()->get();
        $this->assertCount(1, $published);
        $this->assertEquals('privacy-policy', $published->first()->slug);
    }

    /**
     * scopeForSlug() が指定スラッグのみ返すこと
     */
    public function test_scope_for_slug(): void
    {
        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        DixlaseLegalPage::create([
            'slug' => 'terms-of-service',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        $results = DixlaseLegalPage::forSlug('privacy-policy')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('privacy-policy', $results->first()->slug);
    }

    /**
     * scopeForLang() が指定言語のみ返すこと
     */
    public function test_scope_for_lang(): void
    {
        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'en',
            'status' => 'draft',
        ]);

        $results = DixlaseLegalPage::forLang('ja')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('ja', $results->first()->lang);
    }

    /**
     * ソフトデリートが動作すること
     */
    public function test_soft_delete(): void
    {
        $page = DixlaseLegalPage::create([
            'slug' => 'privacy-policy',
            'lang' => 'ja',
            'status' => 'draft',
        ]);

        $page->delete();

        $this->assertSoftDeleted('plg_dixlase_legal_pages', [
            'slug' => 'privacy-policy',
            'lang' => 'ja',
        ]);

        $this->assertCount(0, DixlaseLegalPage::all());
        $this->assertCount(1, DixlaseLegalPage::withTrashed()->get());
    }

    /**
     * ファクトリがモデルを正しく生成すること
     */
    public function test_factory_creates_model(): void
    {
        $page = DixlaseLegalPage::factory()->create();

        $this->assertInstanceOf(DixlaseLegalPage::class, $page);
        $this->assertNotNull($page->slug);
        $this->assertNotNull($page->lang);
    }

    /**
     * ファクトリのpublished状態が正しいこと
     */
    public function test_factory_published_state(): void
    {
        $page = DixlaseLegalPage::factory()->published()->create([
            'slug' => 'site-policy',
            'lang' => 'ja',
        ]);

        $this->assertTrue($page->isPublished());
        $this->assertNotNull($page->published_at);
    }

    /**
     * ファクトリのmarkdown状態が正しいこと
     */
    public function test_factory_markdown_state(): void
    {
        $page = DixlaseLegalPage::factory()->markdown()->create([
            'slug' => 'cookie-policy',
            'lang' => 'en',
        ]);

        $this->assertEquals(ContentEditorType::MARKDOWN, $page->editor_type);
        $this->assertNotNull($page->content_markdown);
        $this->assertNull($page->content_html);
    }
}
