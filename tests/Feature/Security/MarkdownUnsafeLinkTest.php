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

namespace Tests\Feature\Security;

use App\Services\PageContentService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Markdown page bodies are rendered with raw HTML deliberately allowed --
 * PageContentService documents this as preserving the old setSafeMode(false)
 * contract, with CommonMark's tagfilter neutralising script, iframe and style.
 * That trade-off is intentional and is NOT what these tests change.
 *
 * What was missing is allow_unsafe_links. Measured on this converter:
 *
 *   [click](javascript:alert(1))  ->  <a href="javascript:alert(1)">click</a>
 *   ![x](javascript:alert(1))     ->  <img src="javascript:alert(1)" ...>
 *
 * The option only filters URLs written with Markdown link/image syntax; raw
 * HTML passes through either way. So turning it on closes a vector without
 * costing anything the documented contract promises -- which is why it is
 * worth doing even though the raw-HTML vectors necessarily remain.
 */
class MarkdownUnsafeLinkTest extends TestCase
{
    private function render(string $markdown): string
    {
        $service = app(PageContentService::class);
        $method = new ReflectionMethod($service, 'renderMarkdown');
        $method->setAccessible(true);

        return $method->invoke($service, $markdown);
    }

    public function test_markdown_links_cannot_carry_the_javascript_scheme(): void
    {
        $html = $this->render('[click](javascript:alert(1))');

        $this->assertStringNotContainsString(
            'javascript:',
            $html,
            'A javascript: URL written with Markdown link syntax must not reach the rendered href.'
        );
        $this->assertStringContainsString('click', $html, 'The link text itself should survive.');
    }

    public function test_markdown_images_cannot_carry_the_javascript_scheme(): void
    {
        $html = $this->render('![x](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    /**
     * The documented contract. If this breaks, the fix went too far and
     * authors lost the ability to write HTML in page bodies.
     */
    public function test_inline_html_still_passes_through(): void
    {
        $this->assertStringContainsString(
            '<b>bold</b>',
            $this->render('<b>bold</b>'),
            'Inline HTML is deliberately allowed in page bodies; the link filter must not disturb it.'
        );

        $this->assertStringContainsString(
            '<a href="https://example.com">ok</a>',
            $this->render('<a href="https://example.com">ok</a>'),
            'Ordinary HTML anchors must be unaffected.'
        );
    }

    public function test_ordinary_markdown_links_still_work(): void
    {
        $html = $this->render('[site](https://example.com) and [mail](mailto:a@example.com)');

        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('href="mailto:a@example.com"', $html);
    }

    /**
     * tagfilter behaviour, asserted so a converter swap cannot quietly drop it.
     */
    public function test_script_tags_remain_neutralised(): void
    {
        $this->assertStringNotContainsString(
            '<script>',
            $this->render('<script>alert(1)</script>'),
            'CommonMark tagfilter must keep escaping script tags.'
        );
    }

    /**
     * Preview must agree with the published page. An author who sees a working
     * javascript: link in preview would reasonably assume it works live.
     */
    public function test_preview_renders_markdown_the_same_way(): void
    {
        $preview = app(\App\Services\ContentPreviewService::class)
            ->renderFromSlug('[click](javascript:alert(1))', 'markdown');

        $this->assertStringNotContainsString(
            'javascript:',
            $preview,
            'Preview and the published page must apply the same link filtering.'
        );
    }
}
