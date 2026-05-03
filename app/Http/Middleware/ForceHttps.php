<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to redirect HTTP requests to HTTPS when FORCE_SSL is enabled
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
        if (! config('app.force_ssl')) {
            return $next($request);
        }

        // Skip during installation
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // Skip if already HTTPS or HTTPS via reverse proxy
        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https') {
            return $next($request);
        }

        // Redirect HTTP request to HTTPS (301 Permanent Redirect)
        return redirect()->secure($request->getRequestUri(), 301);
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
