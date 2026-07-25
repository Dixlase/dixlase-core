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

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Trusted Proxies
|--------------------------------------------------------------------------
|
| This config controls which upstream proxy IPs Dixlase will believe when it
| reads `X-Forwarded-*` headers. The setting is security-critical: if an
| untrusted IP is allowed to set `X-Forwarded-For`, an attacker on the public
| internet can spoof their source IP and bypass admin IP allow-lists.
|
| The default is an empty list — no proxies are trusted, and `$request->ip()`
| returns the actual TCP source. Operators behind a CDN, reverse proxy, or
| Identity-Aware Proxy MUST set `TRUSTED_PROXIES` in `.env` to the list of
| upstream IPs / CIDR ranges so that forwarded headers are honored.
|
| See `SECURITY.md` "Reverse-proxy / IAP deployment" for guidance and
| worked examples (Cloudflare, Google IAP, Cloudfront, Traefik, …).
|
*/
$proxies = env('TRUSTED_PROXIES');

if ($proxies === null || $proxies === '') {
    // Default: no proxies trusted. Forwarded headers are ignored; $request->ip()
    // is the TCP source. Safe-by-default for operators who run Dixlase on a
    // public IP without a reverse proxy.
    $resolvedProxies = [];
} elseif (trim($proxies) === '*') {
    // '*' means trust ANY upstream as a proxy. Equivalent to disabling the
    // forwarded-header authenticity check. Acceptable in development and in
    // tightly controlled environments where every reachable upstream is the
    // operator's. NEVER use in a deployment where the application is exposed
    // to a network the operator does not control.
    $resolvedProxies = '*';
} else {
    // Comma-separated list of IPs and/or CIDR ranges. Whitespace around each
    // entry is tolerated. Empty entries are dropped.
    $resolvedProxies = array_values(array_filter(array_map('trim', explode(',', $proxies))));
}

return [

    /*
    |----------------------------------------------------------------------
    | Trusted upstream addresses
    |----------------------------------------------------------------------
    |
    | Resolved from the `TRUSTED_PROXIES` env var. One of:
    |   - `[]`   (default) — no proxies trusted
    |   - `'*'`  — all upstreams trusted (development only)
    |   - `array<int,string>` — specific IPs / CIDR ranges
    |
    */
    'proxies' => $resolvedProxies,

    /*
    |----------------------------------------------------------------------
    | Trusted forwarded headers
    |----------------------------------------------------------------------
    |
    | Bitmask of which `X-Forwarded-*` headers Dixlase will read when the
    | upstream is in the trusted-proxies list. The default set covers the
    | common Forwarded, X-Forwarded-For/Proto/Host/Port headers.
    |
    | Reference: Symfony\Component\HttpFoundation\Request::HEADER_*.
    |
    */
    'headers' => Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_AWS_ELB,

];
