<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Unit\Services;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Services\ContentPreviewService;
use App\Services\PageContentService;
use Tests\TestCase;

/**
 * PageContentService had no tests at all, which is how it went on calling
 * `new Parsedown()` — a class declared nowhere in composer.json — for every
 * Markdown page. `erusev/parsedown` only ever appears as a *dev* dependency of
 * league/commonmark, and dev dependencies are not installed transitively, so
 * the call could never resolve in a clean checkout.
 */
class PageContentServiceMarkdownTest extends TestCase
{
    private function render(string $markdown): string
    {
        return app(PageContentService::class)->renderContent(
            $markdown,
            ContentEditorType::MARKDOWN,
            ContentStorageType::DATABASE,
        );
    }

    public function test_markdown_renders_instead_of_blowing_up(): void
    {
        $html = $this->render("# Title\n\n**bold**");

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_headings_without_a_space_are_normalised(): void
    {
        // Mirrors ContentPreviewService::normalizeMarkdown(). Without it a
        // page would preview as a heading and publish as literal "#Title".
        $this->assertStringContainsString('<h1>Title</h1>', $this->render('#Title'));
    }

    public function test_rendering_agrees_with_preview(): void
    {
        // Before this fix the two disagreed by construction: preview went
        // through Str::markdown() while publishing went through Parsedown.
        $markdown = "#Heading\n\nsome *emphasis* and a [link](https://example.com)";

        $this->assertSame(
            app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN),
            $this->render($markdown),
        );
    }

    public function test_inline_html_is_allowed_but_script_tags_are_neutralised(): void
    {
        // Documents the HTML policy rather than asserting it is sufficient.
        // The old code set Parsedown's safe mode off to "allow HTML tags";
        // GithubFlavoredMarkdownConverter keeps ordinary inline HTML while its
        // tagfilter defuses <script>/<iframe>/<style>.
        $html = $this->render('<b>keep</b> <script>alert(1)</script>');

        $this->assertStringContainsString('<b>keep</b>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_render_falls_back_to_raw_content_when_the_renderer_raises_an_error(): void
    {
        // The fallback in renderContent() used to catch \Exception only, so an
        // \Error — which is what a missing class raises — sailed straight past
        // it and became a 500. The net existed but never caught anything.
        $service = new class extends PageContentService
        {
            protected function renderMarkdown(string $content): string
            {
                throw new \Error('simulated missing class');
            }
        };

        $this->assertSame(
            'raw markdown',
            $service->renderContent('raw markdown', ContentEditorType::MARKDOWN, ContentStorageType::DATABASE),
        );
    }
}
