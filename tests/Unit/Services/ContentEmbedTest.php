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
 * Embeds written as `@[service](URL)` in Markdown (#731)
 */
class ContentEmbedTest extends TestCase
{
    private function render(string $markdown): string
    {
        // Pages and the blog render without raw HTML, as here.
        return app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN, false);
    }

    /**
     * @return list<string>
     */
    private function frameSrc(): array
    {
        return app(CspPolicyRegistry::class)->collectDirectives()['frame-src'] ?? [];
    }

    public function test_a_youtube_video_plays_from_youtube_nocookie(): void
    {
        $html = $this->render("Intro\n\n@[youtube](https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m30s)\n\nAfter");

        $this->assertStringContainsString('<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=90"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('title="'.__('components/content-editor.embed.youtube_title').'"', $html);
        $this->assertStringContainsString('<p>After</p>', $html);
    }

    public function test_every_youtube_url_form_gives_the_video(): void
    {
        foreach ([
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ] as $url) {
            $this->assertStringContainsString(
                'youtube-nocookie.com/embed/dQw4w9WgXcQ"',
                $this->render("@[youtube]({$url})"),
                $url,
            );
        }
    }

    public function test_a_youtube_embed_allows_its_origin_in_frame_src(): void
    {
        $this->assertNotContains('https://www.youtube-nocookie.com', $this->frameSrc());

        $this->render('@[youtube](https://youtu.be/dQw4w9WgXcQ)');

        $this->assertContains('https://www.youtube-nocookie.com', $this->frameSrc());
    }

    public function test_a_page_without_a_youtube_embed_leaves_frame_src_alone(): void
    {
        $this->render("@[x](https://x.com/jack/status/20)\n\n@[youtube](https://example.com/watch?v=dQw4w9WgXcQ)");

        $this->assertNotContains('https://www.youtube-nocookie.com', $this->frameSrc());
    }

    public function test_an_x_post_becomes_a_link_card(): void
    {
        $html = $this->render('@[x](https://twitter.com/jack/status/20?s=20)');

        $this->assertStringContainsString('class="dls-embed dls-embed-card dls-embed-x"', $html);
        $this->assertStringContainsString('href="https://x.com/jack/status/20"', $html);
        $this->assertStringContainsString(e(__('components/content-editor.embed.x_title', ['user' => '@jack'])), $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_a_github_repository_becomes_a_link_card(): void
    {
        $html = $this->render('@[github](https://github.com/Dixlase/dixlase-core/issues/731)');

        $this->assertStringContainsString('class="dls-embed dls-embed-card dls-embed-github"', $html);
        $this->assertStringContainsString('href="https://github.com/Dixlase/dixlase-core/issues/731"', $html);
        $this->assertStringContainsString('<strong style="display:block">Dixlase/dixlase-core#731</strong>', $html);
    }

    public function test_a_url_the_service_does_not_recognise_stays_a_plain_link(): void
    {
        $html = $this->render('@[youtube](https://example.com/watch?v=dQw4w9WgXcQ)');

        $this->assertSame('<p><a href="https://example.com/watch?v=dQw4w9WgXcQ">https://example.com/watch?v=dQw4w9WgXcQ</a></p>', trim($html));
    }

    public function test_a_url_that_is_not_http_stays_text(): void
    {
        $html = $this->render('@[github](javascript:alert(1))');

        $this->assertStringNotContainsString('href', $html);
        $this->assertStringNotContainsString('dls-embed', $html);
    }

    public function test_quotes_in_the_url_cannot_leave_the_attribute(): void
    {
        $html = $this->render('@[github](https://github.com/a/b?x="onmouseover=alert(1))');

        $this->assertStringNotContainsString('"onmouseover', $html);
        $this->assertStringContainsString('&quot;onmouseover', $html);
    }

    public function test_only_a_line_of_its_own_is_an_embed(): void
    {
        $html = $this->render("Watch @[youtube](https://youtu.be/dQw4w9WgXcQ) now\n\n@[vimeo](https://vimeo.com/1)\n\n```\n@[youtube](https://youtu.be/dQw4w9WgXcQ)\n```");

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString("<code>@[youtube](https://youtu.be/dQw4w9WgXcQ)\n</code>", $html);
    }

    public function test_a_published_page_renders_embeds_as_the_preview_does(): void
    {
        $markdown = "# Title\n\n@[youtube](https://youtu.be/dQw4w9WgXcQ)\n\n@[github](https://github.com/a/b)";

        $this->assertSame(
            app(ContentPreviewService::class)->render($markdown, ContentEditorType::MARKDOWN),
            app(PageContentService::class)->renderContent($markdown, ContentEditorType::MARKDOWN, ContentStorageType::DATABASE),
        );
    }
}
