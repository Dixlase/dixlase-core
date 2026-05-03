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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiRateLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIリクエストログミドルウェア（Terminable）
 *
 * レスポンス送信後にAPIリクエストをログに記録します。
 * terminate() メソッドを使用し、レスポンスの遅延を防ぎます。
 */
class LogApiRequest
{
    public function __construct(
        protected ApiRateLimitService $rateLimitService,
    ) {}

    /**
     * リクエスト開始時刻を記録してリクエストを通過させる
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('api_request_start', microtime(true));

        return $next($request);
    }

    /**
     * レスポンス送信後にログを記録
     */
    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('api_request_start');
        $responseTimeMs = $startTime
            ? (int) round((microtime(true) - $startTime) * 1000)
            : null;

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $responseCode = $response->getStatusCode();
        $content = $response->getContent();
        $responseSize = $content !== false ? strlen($content) : null;

        // エラー情報を抽出
        $errorCode = null;
        $errorMessage = null;
        if ($responseCode >= 400) {
            $decoded = json_decode($content ?: '', true);
            if (is_array($decoded)) {
                $errorCode = $decoded['error'] ?? null;
                $errorMessage = $decoded['message'] ?? null;
            }
        }

        $this->rateLimitService->logRequest(
            request: $request,
            apiKey: $apiKey,
            responseCode: $responseCode,
            responseTimeMs: $responseTimeMs,
            responseSize: $responseSize,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
