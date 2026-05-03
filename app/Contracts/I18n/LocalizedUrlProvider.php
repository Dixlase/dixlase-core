<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Contracts\I18n;

use Illuminate\Http\Request;

/**
 * Exposes alternate-language URLs for the current request.
 *
 * Plugins that surface multi-language content implement this contract so
 * that consumers (notably the SEO plugin emitting <link rel="alternate"
 * hreflang="..."> tags, sitemap generators, language switchers) can list
 * the equivalent URLs in every supported locale.
 *
 * In v0.1.0 the core ships no implementation; this contract exists so
 * plugin authors can target a stable signature and the SEO plugin can
 * consume it once it lands.
 */
interface LocalizedUrlProvider
{
    /**
     * Return the equivalent URL of the current request in every supported
     * locale.
     *
     * The returned array is keyed by locale code (e.g. 'ja', 'en') and
     * the value is the absolute or root-relative URL for that locale. If
     * an alternate URL does not exist for a given locale, the
     * implementation should omit that key rather than emit a placeholder.
     *
     * @return array<string, string> Example: ['ja' => '/ja/posts/hello', 'en' => '/en/posts/hello']
     */
    public function getAlternateUrls(Request $request): array;
}
