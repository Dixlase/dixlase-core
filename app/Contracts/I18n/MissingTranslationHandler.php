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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides what to do when a route exists but no translation is available
 * for the requested locale.
 *
 * The core default implementation issues a 302 redirect to the same path
 * under the site's primary locale. Any multilingual plugin (first-party,
 * third-party, or a custom in-house implementation) — or an unrelated
 * plugin such as one that handles redirects — can rebind this contract
 * to return 404, render a fallback locale, or apply a custom redirect
 * rule.
 *
 * This contract only fires when a route is matched but content is missing
 * for the current locale. Unmatched URLs continue to return a normal 404.
 */
interface MissingTranslationHandler
{
    /**
     * Produce the response for a request whose translation is missing.
     *
     * @param  Request  $request  The current request, with locale already resolved
     * @param  string  $requestedLocale  The locale the visitor asked for (e.g. 'ja')
     */
    public function handle(Request $request, string $requestedLocale): Response;
}
