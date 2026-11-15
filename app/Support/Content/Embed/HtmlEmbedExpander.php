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

declare(strict_types=1);

namespace App\Support\Content\Embed;

/**
 * Expand `@[service](URL)` lines in an HTML body (#1031)
 *
 * The Markdown editor's embeds are parsed by CommonMark; an HTML body is not,
 * so the same marker is found here instead and rendered by the same
 * {@see ContentEmbedRenderer}, which also adds the player's origin to the
 * page's CSP frame-src. A marker counts only when it is all its paragraph
 * holds: alone on a line, or alone in a `<p>`. One inside a sentence, a
 * comment, or a `<pre>`, `<code>`, `<textarea>`, `<script>` or `<style>`
 * element is left as written, so an article can show the syntax itself.
 */
final class HtmlEmbedExpander
{
    private const PATTERN = '~'
        // Regions left alone, consumed whole so a marker inside is skipped.
        .'(?<skip><!--.*?-->|<(?<tag>pre|code|textarea|script|style)\b[^>]*>.*?</(?P=tag)\s*>)'
        // A marker alone in a <p>…
        .'|^[ \t]*<p(?:\s[^>]*)?>\s*@\[(?<s1>[a-z]+)\]\([ \t]*(?<u1>[^\s()<>]+)[ \t]*\)\s*</p>[ \t]*$'
        // …or alone on a line.
        .'|^[ \t]*@\[(?<s2>[a-z]+)\]\([ \t]*(?<u2>[^\s()<>]+)[ \t]*\)[ \t]*$'
        .'~ism';

    public function __construct(private readonly ContentEmbedRenderer $renderer) {}

    public function expand(string $html): string
    {
        if (! str_contains($html, '@[')) {
            return $html;
        }

        return preg_replace_callback(self::PATTERN, function (array $match): string {
            if (($match['skip'] ?? '') !== '') {
                return $match[0];
            }

            $service = strtolower(($match['s1'] ?? '') !== '' ? $match['s1'] : ($match['s2'] ?? ''));
            if (! in_array($service, ContentEmbedRenderer::SERVICES, true)) {
                return $match[0];
            }

            // An HTML author writes & as &amp; inside a URL.
            $url = html_entity_decode(($match['u1'] ?? '') !== '' ? $match['u1'] : ($match['u2'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $this->renderer->renderEmbed($service, $url);
        }, $html, flags: PREG_UNMATCHED_AS_NULL) ?? $html;
    }
}
