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
 * Redirect HTTP to HTTPS when FORCE_SSL is enabled, and emit the
 * Strict-Transport-Security header when HSTS is configured.
 *
 * HSTS is intentionally OFF by default — see config/security.php "hsts"
 * block for the rationale and the recommended escalation path. The header
 * is only attached to responses that are themselves served over HTTPS;
 * sending it on plain HTTP is pointless (the browser ignores it).
 */
class ForceHttps
{
    /**
     * Paths excluded from redirect
     */
    protected array $excludedPaths = [
        'install',
        'install/*',
        'csp-report',
        '_boost/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $this->handleHttpsRedirect($request, $next);

        return $this->applyHstsHeader($request, $response);
    }

    /**
     * Apply the HTTP→HTTPS redirect logic, returning the next response if
     * the request is already secure or excluded.
     */
    protected function handleHttpsRedirect(Request $request, Closure $next): Response
    {
        if (! config('app.force_ssl')) {
            return $next($request);
        }

        // Skip during installation
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // Skip if already HTTPS or HTTPS via reverse proxy
        if ($this->requestIsSecure($request)) {
            return $next($request);
        }

        // Redirect HTTP request to HTTPS (301 Permanent Redirect)
        return redirect()->secure($request->getRequestUri(), 301);
    }

    /**
     * Attach the Strict-Transport-Security header when HSTS is configured
     * and the request was served over HTTPS. No-op otherwise.
     */
    protected function applyHstsHeader(Request $request, Response $response): Response
    {
        $maxAge = (int) config('security.hsts.max_age', 0);

        if ($maxAge <= 0) {
            return $response;
        }

        // HSTS is meaningful only over HTTPS. We deliberately skip
        // attaching the header on plain-HTTP responses — browsers ignore
        // it there, and emitting it would mask misconfigurations.
        if (! $this->requestIsSecure($request)) {
            return $response;
        }

        $directives = ['max-age='.$maxAge];

        if ((bool) config('security.hsts.include_subdomains', false)) {
            $directives[] = 'includeSubDomains';
        }

        if ((bool) config('security.hsts.preload', false)) {
            $directives[] = 'preload';
        }

        $response->headers->set('Strict-Transport-Security', implode('; ', $directives));

        return $response;
    }

    /**
     * Detect HTTPS, including the reverse-proxy / IAP case where
     * X-Forwarded-Proto carries the original scheme.
     *
     * Note: X-Forwarded-Proto is only honoured when the upstream proxy is
     * declared in TRUSTED_PROXIES. See config/trustedproxy.php.
     */
    protected function requestIsSecure(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        return $request->header('X-Forwarded-Proto') === 'https';
    }

    protected function isExcludedPath(Request $request): bool
    {
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
