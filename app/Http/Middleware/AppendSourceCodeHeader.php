<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * AGPL §13 準拠のためにレスポンスへソースコード取得先を通知するミドルウェア
 *
 * 運用中の Dixlase CMS インスタンスのソースコードを取得できる URL を
 * `X-Source-Code` ヘッダーでレスポンスに付与する。改変版を運用する際は
 * `DIXLASE_SOURCE_URL` で取得先を上書きすること。
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
