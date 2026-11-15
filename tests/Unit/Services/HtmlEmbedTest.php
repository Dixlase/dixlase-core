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
use App\Services\Csp\CspPolicyRegistry;
use App\Services\PageContentService;
use Tests\TestCase;

/**
 * Embeds written as `@[service](URL)` in an HTML body (#1031)
 */
class HtmlEmbedTest extends TestCase
{
    private const VIDEO = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    private function render(string $html): string
    {
        return app(ContentPreviewService::class)->render($html, ContentEditorType::HTML, false);
    }

    /**
     * @return list<string>
     */
    private function frameSrc(): array
    {
        return app(CspPolicyRegistry::class)->collectDirectives()['frame-src'] ?? [];
    }

    public function test_a_marker_on_its_own_line_plays_the_video_and_allows_its_origin(): void
    {
        $this->assertNotContains('https://www.youtube-nocookie.com', $this->frameSrc());

        $html = $this->render("<h2>Intro</h2>\n@[youtube](".self::VIDEO.")\n<p>After</p>");

        $this->assertStringContainsString('<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', $html);
        $this->assertStringContainsString("<h2>Intro</h2>\n", $html);
        $this->assertStringContainsString("\n<p>After</p>", $html);
        $this->assertContains('https://www.youtube-nocookie.com', $this->frameSrc());
    }

    public function test_a_marker_alone_in_a_paragraph_replaces_the_paragraph(): void
    {
        $html = $this->render("<p class=\"lead\">\n  @[youtube](https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;t=90)\n</p>");

        $this->assertStringStartsWith('<div class="dls-embed dls-embed-youtube"', $html);
        $this->assertStringContainsString('/embed/dQw4w9WgXcQ?start=90"', $html);
        $this->assertStringNotContainsString('<p', $html);
    }

    public function test_x_and_github_become_link_cards(): void
    {
        $html = $this->render("@[x](https://x.com/dixlase/status/123)\n<p>@[GitHub](https://github.com/Dixlase/dixlase-core)</p>");

        $this->assertStringContainsString('dls-embed-x', $html);
        $this->assertStringContainsString('href="https://x.com/dixlase/status/123"', $html);
        $this->assertStringContainsString('<strong style="display:block">Dixlase/dixlase-core</strong>', $html);
    }

    public function test_a_marker_that_is_not_alone_or_is_quoted_is_left_as_written(): void
    {
        $line = '@[youtube]('.self::VIDEO.')';
        $html = "<p>Watch {$line} now</p>\n<pre>\n{$line}\n</pre>\n<pre><code>{$line}</code></pre>\n<!--\n{$line}\n-->\n<textarea>\n{$line}\n</textarea>\n@[vimeo](https://vimeo.com/1)";

        $this->assertSame($html, $this->render($html));
        $this->assertNotContains('https://www.youtube-nocookie.com', $this->frameSrc());
    }

    public function test_an_unrecognised_or_unsafe_url_gives_no_player(): void
    {
        $html = $this->render("@[youtube](https://example.com/watch)\n@[youtube](javascript:alert(1))");

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('<a href="https://example.com/watch">', $html);
        $this->assertStringNotContainsString('href="javascript', $html);
    }

    public function test_a_published_page_renders_embeds_as_the_preview_does(): void
    {
        $html = "<h1>Title</h1>\n@[youtube](https://youtu.be/dQw4w9WgXcQ)\n<p>@[github](https://github.com/a/b)</p>";

        $this->assertSame(
            $this->render($html),
            app(PageContentService::class)->renderContent($html, ContentEditorType::HTML, ContentStorageType::DATABASE),
        );
    }

    public function test_html_without_a_marker_is_unchanged(): void
    {
        $html = '<p>Mail me@[example].com <a href="https://example.com">here</a></p>';

        $this->assertSame($html, app(ContentPreviewService::class)->expandEmbeds($html));
    }
}
