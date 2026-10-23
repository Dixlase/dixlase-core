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

    public function test_a_single_line_break_renders_as_br(): void
    {
        $markdown = "一行目\n二行目\n\n次の段落";

        $this->assertStringContainsString("<p>一行目<br>\n二行目</p>", $this->render($markdown));
        // The preview renders the same, with raw HTML allowed or escaped.
        $this->assertStringContainsString(
            "一行目<br>\n二行目",
            app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN, false),
        );
    }

    public function test_code_blocks_and_lists_keep_their_line_breaks(): void
    {
        $html = $this->render("- a\n- b\n\n```\ncode1\ncode2\n```");

        $this->assertStringContainsString("<li>a</li>\n<li>b</li>", $html);
        $this->assertStringContainsString("code1\ncode2", $html);
        $this->assertStringNotContainsString('code1<br>', $html);
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

    public function test_a_heading_takes_an_id_and_classes_for_in_page_links(): void
    {
        $markdown = "- [What happened](#what-happened)\n\n## 何が起きたのか {#what-happened .note}\n\n本文\n{.dx-roles}";

        $html = $this->render($markdown);

        $this->assertStringContainsString('<h2 class="note" id="what-happened">何が起きたのか</h2>', $html);
        $this->assertStringContainsString('<p class="dx-roles">本文</p>', $html);
        $this->assertStringContainsString('<a href="#what-happened">', $html);
        $this->assertStringNotContainsString('{#', $html);
        // The preview renders the same, with raw HTML allowed or escaped.
        $this->assertSame($html, app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN));
        $this->assertStringContainsString(
            '<h2 class="note" id="what-happened">',
            app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN, false),
        );
    }

    public function test_attributes_other_than_id_and_class_are_dropped(): void
    {
        $markdown = "## Title {#t onclick=\"alert(1)\" style=\"color:red\" data-x=1}\n\n[link](https://example.com){href=\"javascript:alert(1)\" onmouseover=\"x()\"}";

        foreach ([
            $this->render($markdown),
            app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN, false),
        ] as $html) {
            $this->assertStringContainsString('<h2 id="t">Title</h2>', $html);
            $this->assertStringContainsString('<a href="https://example.com">link</a>', $html);
            $this->assertStringNotContainsString('onclick', $html);
            $this->assertStringNotContainsString('style=', $html);
            $this->assertStringNotContainsString('data-x', $html);
            $this->assertStringNotContainsString('javascript:', $html);
            $this->assertStringNotContainsString('onmouseover', $html);
        }
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
