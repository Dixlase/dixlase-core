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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force browsers not to cache admin responses.
 *
 * Admin HTML is authenticated and often carries session-bound state
 * (CSRF tokens, per-request rendered menus, extension version
 * indicators, translated strings that depend on the acting member's
 * locale). Without an explicit Cache-Control header, browsers are
 * free to reuse an earlier response for the same URL — which caused
 * a real operator-visible bug: after updating an extension via the
 * admin panel, operators kept seeing the pre-update sidebar because
 * their browser served the previously-cached HTML from disk. This
 * middleware makes the caching decision explicit so that class of
 * bug cannot recur through browser-side caching.
 *
 * `no-store` — do not persist anywhere (memory or disk).
 * `no-cache` — even in-memory copies must revalidate on each use.
 * `must-revalidate` — the pair above is not enough for older
 *   proxies; belt-and-suspenders.
 * `private` — intermediaries (CDN, corporate proxy) may not cache
 *   even briefly.
 * `max-age=0` — legacy HTTP/1.0 caches read this rather than the
 *   above directives.
 *
 * Pragma / Expires are the HTTP/1.0-era equivalents kept for
 * ancient intermediaries. They cost nothing to send.
 *
 * Any downstream code that has already set a `Cache-Control`
 * (e.g. a public-download endpoint the operator hits from an
 * admin-prefixed URL) is intentionally overridden — an admin
 * URL that wants to serve cacheable bytes should be moved out
 * from under the admin route group.
 */
class PreventAdminResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
