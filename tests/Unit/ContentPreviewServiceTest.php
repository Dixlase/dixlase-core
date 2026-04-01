<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * ContentPreviewService のユニットテスト
 */

namespace Tests\Unit;

use App\Enums\ContentEditorType;
use App\Services\ContentPreviewService;
use Tests\TestCase;

class ContentPreviewServiceTest extends TestCase
{
    private ContentPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContentPreviewService::class);
    }

    public function test_render_html_content(): void
    {
        $result = $this->service->render('<h1>Hello</h1>', ContentEditorType::HTML);

        $this->assertStringContainsString('<h1>Hello</h1>', $result);
    }

    public function test_render_markdown_content(): void
    {
        $result = $this->service->render('# Hello World', ContentEditorType::MARKDOWN);

        $this->assertStringContainsString('<h1>Hello World</h1>', $result);
    }

    public function test_render_empty_content_returns_empty_string(): void
    {
        $result = $this->service->render('', ContentEditorType::HTML);

        $this->assertSame('', $result);
    }

    public function test_render_from_slug_with_valid_slug(): void
    {
        $result = $this->service->renderFromSlug('<p>Test</p>', 'html');

        $this->assertStringContainsString('<p>Test</p>', $result);
    }

    public function test_render_from_slug_with_markdown(): void
    {
        $result = $this->service->renderFromSlug('**bold**', 'markdown');

        $this->assertStringContainsString('<strong>bold</strong>', $result);
    }

    public function test_render_from_slug_with_invalid_slug_returns_empty(): void
    {
        $result = $this->service->renderFromSlug('<p>Test</p>', 'invalid');

        $this->assertSame('', $result);
    }

    public function test_render_from_slug_with_empty_content_returns_empty(): void
    {
        $result = $this->service->renderFromSlug('', 'html');

        $this->assertSame('', $result);
    }
}
