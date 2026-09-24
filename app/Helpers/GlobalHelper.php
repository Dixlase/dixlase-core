<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
if (! function_exists('shortcode_parse')) {
    /**
     * @api Stable API available for use from plugins/themes
     *
     * Parse and execute shortcodes
     *
     * @param  string  $content  Content to be parsed
     * @return string Parsed content
     */
    function shortcode_parse($content)
    {
        if (! app()->bound('shortcode')) {
            return $content;
        }

        return app('shortcode')->parse($content);
    }
}

if (! function_exists('dls_page_title')) {
    /**
     * @api Stable API available for use from plugins/themes
     *
     * Compose the document title for the current page.
     *
     * Themes and plugins get one rule (and one separator) for the whole
     * document title instead of concatenating the site name by hand. The
     * `@pageTitle` directive wraps this helper and reads the `title`
     * section for you, so a Blade view normally uses the directive and
     * calls this helper only to guard it on an older core: the theme
     * wraps the directive in `@if (function_exists('dls_page_title'))` /
     * `@else` / `@endif` and falls back to its own concatenation. An
     * unregistered directive is left as literal text by the Blade
     * compiler rather than failing the compile.
     *
     * Called directly, pass plain text: the return value is plain text
     * too, so escape it at the call site. Content taken from a Blade
     * section is already HTML escaped and has to be decoded first.
     *
     * @param  string|null  $pageName  Page-specific part, or null for the site root
     * @return string Plain text; escape it at the call site
     */
    function dls_page_title(?string $pageName = null): string
    {
        $contract = \App\Contracts\Site\PageTitleBuilderInterface::class;

        if (! app()->bound($contract)) {
            return (new \App\Services\Site\PageTitleBuilder())->build($pageName);
        }

        return app($contract)->build($pageName);
    }
}
