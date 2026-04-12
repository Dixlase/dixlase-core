<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Front page custom JS/CSS external file delivery feature tests
 */

namespace Tests\Feature\Front;

use App\Enums\ContentEditorType;
use App\Models\FrontPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrontCustomAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('DixlasePages プラグイン (FrontPageFactory) 依存');

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================
    // JavaScript route
    // =========================================================

    public function test_script_route_returns_js_content_with_correct_content_type(): void
    {
        FrontPage::factory()->withCustomAssets()->create();

        $response = $this->get(route('front.custom-script'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('console.log("hello");', false);
    }

    public function test_script_route_returns_cache_headers(): void
    {
        FrontPage::factory()->withCustomAssets()->create();

        $response = $this->get(route('front.custom-script'));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=3600, public');
        $response->assertHeader('ETag');
    }

    public function test_script_route_returns_404_when_no_custom_js(): void
    {
        FrontPage::factory()->create([
            'custom_js' => null,
        ]);

        $response = $this->get(route('front.custom-script'));

        $response->assertNotFound();
    }

    public function test_script_route_returns_404_when_custom_js_is_empty(): void
    {
        FrontPage::factory()->create([
            'custom_js' => '',
        ]);

        $response = $this->get(route('front.custom-script'));

        $response->assertNotFound();
    }

    // =========================================================
    // CSS route
    // =========================================================

    public function test_style_route_returns_css_content_with_correct_content_type(): void
    {
        FrontPage::factory()->withCustomAssets()->create();

        $response = $this->get(route('front.custom-style'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $response->assertSee('body { color: red; }');
    }

    public function test_style_route_returns_cache_headers(): void
    {
        FrontPage::factory()->withCustomAssets()->create();

        $response = $this->get(route('front.custom-style'));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=3600, public');
        $response->assertHeader('ETag');
    }

    public function test_style_route_returns_404_when_no_custom_css(): void
    {
        FrontPage::factory()->create([
            'custom_css' => null,
        ]);

        $response = $this->get(route('front.custom-style'));

        $response->assertNotFound();
    }

    // =========================================================
    // No front page
    // =========================================================

    public function test_script_route_returns_404_when_no_front_page(): void
    {
        $response = $this->get(route('front.custom-script'));

        $response->assertNotFound();
    }

    public function test_style_route_returns_404_when_no_front_page(): void
    {
        $response = $this->get(route('front.custom-style'));

        $response->assertNotFound();
    }

    // =========================================================
    // Non-HTML editor type
    // =========================================================

    public function test_script_route_returns_404_for_markdown_editor(): void
    {
        FrontPage::factory()->markdown()->create([
            'custom_js' => 'console.log("should not serve");',
        ]);

        $response = $this->get(route('front.custom-script'));

        $response->assertNotFound();
    }

    public function test_style_route_returns_404_for_markdown_editor(): void
    {
        FrontPage::factory()->markdown()->create([
            'custom_css' => 'body { color: red; }',
        ]);

        $response = $this->get(route('front.custom-style'));

        $response->assertNotFound();
    }

    // =========================================================
    // File storage
    // =========================================================

    public function test_script_route_serves_js_from_file_storage(): void
    {
        Storage::fake('local');

        FrontPage::factory()->fileStorage()->create([
            'editor_type' => ContentEditorType::HTML->value,
            'custom_js' => null,
        ]);

        Storage::disk('local')->put('core/front/script.js', 'window.loaded = true;');

        $response = $this->get(route('front.custom-script'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('window.loaded = true;');
    }

    public function test_style_route_serves_css_from_file_storage(): void
    {
        Storage::fake('local');

        FrontPage::factory()->fileStorage()->create([
            'editor_type' => ContentEditorType::HTML->value,
            'custom_css' => null,
        ]);

        Storage::disk('local')->put('core/front/style.css', '.custom { display: flex; }');

        $response = $this->get(route('front.custom-style'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $response->assertSee('.custom { display: flex; }');
    }

    // =========================================================
    // ETag
    // =========================================================

    public function test_etag_changes_when_content_changes(): void
    {
        $frontPage = FrontPage::factory()->withCustomAssets()->create();

        $response1 = $this->get(route('front.custom-script'));
        $etag1 = $response1->headers->get('ETag');

        $frontPage->update(['custom_js' => 'console.log("updated");']);

        $response2 = $this->get(route('front.custom-script'));
        $etag2 = $response2->headers->get('ETag');

        $this->assertNotEquals($etag1, $etag2);
    }
}
