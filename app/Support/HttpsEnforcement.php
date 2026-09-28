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

namespace App\Support;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Decides whether forcing the https scheme can possibly work for this site.
 *
 * `force_ssl` rewrites the scheme of every generated URL but keeps the host and
 * port of APP_URL. When APP_URL points at a plain-HTTP listener on a
 * non-standard port (`http://localhost:40080`, the Docker installer default),
 * the rewritten URL is `https://localhost:40080` — a port that speaks HTTP, so
 * every asset fails with a TLS error and every absolute form action stops
 * matching the document's origin, which CSP `form-action 'self'` then blocks.
 * That combination also locks the operator out: the screen that could turn
 * `force_ssl` off is itself a form whose submission is blocked.
 *
 * A plain-HTTP APP_URL *without* an explicit port is the reverse-proxy shape
 * (the proxy terminates TLS on 443 and forwards to this host), so forcing the
 * scheme there produces a URL that really is served. Only the explicit
 * non-standard port is treated as a contradiction.
 */
final class HttpsEnforcement
{
    /**
     * Whether the https scheme may be forced for URL generation and redirects.
     *
     * Returns false only for the self-contradicting combination described in
     * the class docblock, so that an operator who enables `force_ssl` on an
     * HTTP-only site keeps a usable site instead of a wedged one.
     */
    public static function isForceable(?string $appUrl): bool
    {
        $appUrl = trim((string) $appUrl);

        // No APP_URL to contradict, or the site already declares https.
        if ($appUrl === '' || str_starts_with($appUrl, 'https://')) {
            return true;
        }

        // Anything that is not a plain-HTTP absolute URL keeps the previous
        // behaviour: this guard only recognises one specific contradiction.
        if (! str_starts_with($appUrl, 'http://')) {
            return true;
        }

        $port = parse_url($appUrl, PHP_URL_PORT);

        return $port === null || $port === false || $port === 443;
    }
}
