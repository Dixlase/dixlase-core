<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * フロントページ管理のフィーチャーテスト
 */

namespace Tests\Feature\Admin\Front;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\FrontPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminFrontPageTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        // 管理画面のビューネームスペースを登録
        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================
    // Index
    // =========================================================

    public function test_index_shows_empty_state_when_no_content(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.index'));

        $response->assertOk();
        $response->assertViewIs('admin::front.index');
        $response->assertViewHas('frontPage', null);
    }

    public function test_index_shows_content_info_when_content_exists(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.index'));

        $response->assertOk();
        $response->assertViewHas('frontPage');
        $response->assertViewHas('editorTypeLabel');
        $response->assertViewHas('storageTypeLabel');
        $response->assertViewHas('langName');
    }

    // =========================================================
    // Create
    // =========================================================

    public function test_create_form_is_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.create'));

        $response->assertOk();
        $response->assertViewIs('admin::front.create');
        $response->assertViewHas('languages');
        $response->assertViewHas('editorCardOptions');
        $response->assertViewHas('editorOptions');
        $response->assertViewHas('storageOptions');
        $response->assertViewHas('templates');
    }

    public function test_create_redirects_to_edit_when_content_exists(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.create'));

        $response->assertRedirect(route('admin.front.edit'));
    }

    // =========================================================
    // Store
    // =========================================================

    public function test_store_creates_content_with_database_storage(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'html',
                'storage_type' => 'database',
                'content' => '<h1>Hello World</h1>',
            ]);

        $response->assertRedirect(route('admin.front.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Hello World</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    public function test_store_creates_content_with_file_storage(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'markdown',
                'storage_type' => 'file',
                'content' => '# Hello World',
            ]);

        $response->assertRedirect(route('admin.front.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'lang' => 'en',
            'editor_type' => ContentEditorType::MARKDOWN,
            'storage_type' => ContentStorageType::FILE,
        ]);

        Storage::disk('local')->assertExists('core/front/content.md');
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), []);

        $response->assertSessionHasErrors(['lang', 'editor_type', 'storage_type']);
    }

    public function test_store_validates_invalid_editor_type(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'gui',
                'storage_type' => 'database',
            ]);

        $response->assertSessionHasErrors(['editor_type']);
    }

    // =========================================================
    // Edit
    // =========================================================

    public function test_edit_form_shows_content(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test Content</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.edit'));

        $response->assertOk();
        $response->assertViewIs('admin::front.edit');
        $response->assertViewHas('frontPage');
        $response->assertViewHas('body', '<h1>Test Content</h1>');
        $response->assertViewHas('editorType', 'html');
    }

    public function test_edit_redirects_to_create_when_no_content(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.edit'));

        $response->assertRedirect(route('admin.front.create'));
    }

    // =========================================================
    // Update
    // =========================================================

    public function test_update_saves_content_changes(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Old Content</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => '<h1>New Content</h1>',
            ]);

        $response->assertRedirect(route('admin.front.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'content' => '<h1>New Content</h1>',
        ]);
    }

    public function test_update_switches_storage_type_from_file_to_database(): void
    {
        Storage::fake('local');

        $frontPage = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::FILE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        // ファイルを事前作成
        Storage::disk('local')->put('core/front/content.html', '<h1>Test</h1>');

        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => '<h1>Updated in DB</h1>',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'storage_type' => ContentStorageType::DATABASE,
            'content' => '<h1>Updated in DB</h1>',
        ]);

        // ファイルが削除されていること
        Storage::disk('local')->assertMissing('core/front/content.html');
    }

    public function test_update_redirects_when_no_content_exists(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => 'test',
            ]);

        $response->assertRedirect(route('admin.front.create'));
    }

    // =========================================================
    // Destroy (Reset)
    // =========================================================

    public function test_destroy_deletes_content(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->delete(route('admin.front.destroy'));

        $response->assertRedirect(route('admin.front.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('front_pages', [
            'page_type' => 'main_content',
        ]);
    }

    public function test_destroy_deletes_file_when_file_storage(): void
    {
        Storage::fake('local');

        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '# Test',
            'editor_type' => ContentEditorType::MARKDOWN,
            'storage_type' => ContentStorageType::FILE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        Storage::disk('local')->put('core/front/content.md', '# Test');

        $response = $this->actingAs($this->admin, 'member')
            ->delete(route('admin.front.destroy'));

        $response->assertRedirect(route('admin.front.index'));
        $response->assertSessionHas('success');

        Storage::disk('local')->assertMissing('core/front/content.md');
        $this->assertDatabaseMissing('front_pages', [
            'page_type' => 'main_content',
        ]);
    }

    public function test_destroy_handles_no_content_gracefully(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->delete(route('admin.front.destroy'));

        $response->assertRedirect(route('admin.front.index'));
        $response->assertSessionHas('success');
    }

    // =========================================================
    // Store - JS/CSS (HTML editor)
    // =========================================================

    public function test_store_saves_custom_js_and_css_for_html_editor(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'html',
                'storage_type' => 'database',
                'content' => '<h1>Hello</h1>',
                'custom_js' => 'console.log("test");',
                'custom_css' => 'body { color: red; }',
            ]);

        $response->assertRedirect(route('admin.front.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'editor_type' => ContentEditorType::HTML,
            'custom_js' => 'console.log("test");',
            'custom_css' => 'body { color: red; }',
        ]);
    }

    public function test_store_ignores_custom_js_and_css_for_markdown_editor(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'markdown',
                'storage_type' => 'database',
                'content' => '# Hello',
                'custom_js' => 'console.log("test");',
                'custom_css' => 'body { color: red; }',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'editor_type' => ContentEditorType::MARKDOWN,
            'custom_js' => null,
            'custom_css' => null,
        ]);
    }

    public function test_store_saves_js_css_files_for_html_editor_with_file_storage(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'html',
                'storage_type' => 'file',
                'content' => '<h1>Hello</h1>',
                'custom_js' => 'alert("hi");',
                'custom_css' => '.test { display: none; }',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        Storage::disk('local')->assertExists('core/front/content.html');
        Storage::disk('local')->assertExists('core/front/script.js');
        Storage::disk('local')->assertExists('core/front/style.css');
    }

    // =========================================================
    // Edit - JS/CSS view data
    // =========================================================

    public function test_edit_passes_js_css_for_html_editor(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'custom_js' => 'console.log("edit");',
            'custom_css' => 'h1 { color: blue; }',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.edit'));

        $response->assertOk();
        $response->assertViewHas('isHtmlEditor', true);
        $response->assertViewHas('customJs', 'console.log("edit");');
        $response->assertViewHas('customCss', 'h1 { color: blue; }');
    }

    public function test_edit_does_not_pass_js_css_for_markdown_editor(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '# Test',
            'editor_type' => ContentEditorType::MARKDOWN,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.edit'));

        $response->assertOk();
        $response->assertViewHas('isHtmlEditor', false);
        $response->assertViewHas('customJs', null);
        $response->assertViewHas('customCss', null);
    }

    // =========================================================
    // Update - JS/CSS
    // =========================================================

    public function test_update_saves_custom_js_and_css_for_html_editor(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Old</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => '<h1>New</h1>',
                'custom_js' => 'console.log("updated");',
                'custom_css' => 'body { margin: 0; }',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        $this->assertDatabaseHas('front_pages', [
            'page_type' => 'main_content',
            'content' => '<h1>New</h1>',
            'custom_js' => 'console.log("updated");',
            'custom_css' => 'body { margin: 0; }',
        ]);
    }

    public function test_update_ignores_js_css_for_markdown_editor(): void
    {
        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '# Old',
            'editor_type' => ContentEditorType::MARKDOWN,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => '# New',
                'custom_js' => 'should be ignored',
                'custom_css' => 'should be ignored',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        $frontPage = FrontPage::findByType('main_content');
        $this->assertNull($frontPage->custom_js);
        $this->assertNull($frontPage->custom_css);
    }

    public function test_update_deletes_js_css_files_when_switching_to_database(): void
    {
        Storage::fake('local');

        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'custom_js' => 'alert("hi");',
            'custom_css' => '.test {}',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::FILE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        Storage::disk('local')->put('core/front/content.html', '<h1>Test</h1>');
        Storage::disk('local')->put('core/front/script.js', 'alert("hi");');
        Storage::disk('local')->put('core/front/style.css', '.test {}');

        $response = $this->actingAs($this->admin, 'member')
            ->put(route('admin.front.edit.update'), [
                'storage_type' => 'database',
                'content' => '<h1>Updated</h1>',
                'custom_js' => 'console.log("db");',
                'custom_css' => 'body {}',
            ]);

        $response->assertRedirect(route('admin.front.edit'));

        Storage::disk('local')->assertMissing('core/front/content.html');
        Storage::disk('local')->assertMissing('core/front/script.js');
        Storage::disk('local')->assertMissing('core/front/style.css');
    }

    // =========================================================
    // Destroy - JS/CSS files
    // =========================================================

    public function test_destroy_deletes_js_css_files_for_html_editor(): void
    {
        Storage::fake('local');

        FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'custom_js' => 'alert("hi");',
            'custom_css' => '.test {}',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::FILE,
            'status' => ContentStatus::PUBLISHED,
        ]);

        Storage::disk('local')->put('core/front/content.html', '<h1>Test</h1>');
        Storage::disk('local')->put('core/front/script.js', 'alert("hi");');
        Storage::disk('local')->put('core/front/style.css', '.test {}');

        $response = $this->actingAs($this->admin, 'member')
            ->delete(route('admin.front.destroy'));

        $response->assertRedirect(route('admin.front.index'));

        Storage::disk('local')->assertMissing('core/front/content.html');
        Storage::disk('local')->assertMissing('core/front/script.js');
        Storage::disk('local')->assertMissing('core/front/style.css');

        $this->assertDatabaseMissing('front_pages', [
            'page_type' => 'main_content',
        ]);
    }

    // =========================================================
    // Settings
    // =========================================================

    public function test_settings_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.settings'));

        $response->assertOk();
        $response->assertViewIs('admin::front.settings');
    }

    public function test_update_settings_redirects_with_success(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.settings.store'), []);

        $response->assertRedirect(route('admin.front.settings'));
        $response->assertSessionHas('success');
    }
}
