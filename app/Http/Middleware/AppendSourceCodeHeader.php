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
 * Middleware to notify source code location in response for AGPL §13 compliance
 *
 * Adds a URL where the source code of the running Dixlase CMS instance can be obtained
 * to the response via the `X-Source-Code` header. When running a modified version,
 * override the source location with `DIXLASE_SOURCE_URL`
 */
class AppendSourceCodeHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $sourceUrl = config('dixlase.source_url');

        if (! is_string($sourceUrl) || $sourceUrl === '') {
            return $response;
        }

        if (! $response->headers->has('X-Source-Code')) {
            $response->headers->set('X-Source-Code', $sourceUrl);
        }

        return $response;
    }
}
