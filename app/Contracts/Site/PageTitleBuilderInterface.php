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

namespace App\Contracts\Site;

/**
 * Composes the document title of a rendered page.
 *
 * Core owns this contract so that every theme produces the same
 * `<title>` shape without reinventing the rule, and so the baseline
 * survives uninstalling an SEO plugin. The default implementation is
 * {@see \App\Services\Site\PageTitleBuilder}; an SEO or multilingual
 * plugin may rebind the contract (or extend the default class) to add
 * per-page overrides or localised values.
 *
 * Themes should not call the implementation directly. They render the
 * `@pageTitle` Blade directive, which reads the `title` section of the
 * page being rendered and emits the whole `<title>` element.
 *
 * A theme that must also run on a core release without this API wraps
 * that one line in `@if (function_exists('dls_page_title'))` / `@else` /
 * `@endif` and falls back to its own concatenation. An unregistered
 * directive is left as literal text by the Blade compiler rather than
 * failing the compile, so the guarded branch is never reached.
 *
 * @api Stable API available for use from plugins/themes
 */
interface PageTitleBuilderInterface
{
    /**
     * Compose the full document title.
     *
     * The page name is the per-page part, typically the content of the
     * `title` Blade section. Implementations must tolerate a name that
     * still carries a leading separator (e.g. `' - About us'`), which
     * older views embedded by hand, and must never emit a repeated or
     * dangling separator.
     *
     * @param  string|null  $pageName  Page-specific part, or null/'' for the site root
     * @return string Plain text, not HTML escaped
     */
    public function build(?string $pageName = null): string;

    /**
     * The separator placed between the title parts (e.g. `' - '`).
     */
    public function separator(): string;
}
