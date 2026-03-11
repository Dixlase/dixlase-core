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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiRateLimitService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIレートリミットミドルウェア
 *
 * APIキー認証済みリクエストにはキー別レートリミット、
 * 未認証リクエストにはIP別レートリミットを適用します。
 * レスポンスに X-RateLimit-* ヘッダーを付与します。
 */
class ThrottleApiRequest
{
    public function __construct(
        protected ApiRateLimitService $rateLimitService,
    ) {}

    /**
     * リクエストを処理
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $rateLimitInfo = $apiKey
            ? $this->rateLimitService->checkRateLimit($apiKey)
            : $this->rateLimitService->checkIpRateLimit($request->ip());

        // レートリミット超過
        if ($rateLimitInfo['is_limited']) {
            return $this->tooManyRequestsResponse($rateLimitInfo);
        }

        /** @var Response $response */
        $response = $next($request);

        // レートリミットヘッダーを付与
        $headers = $this->rateLimitService->getRateLimitHeaders($rateLimitInfo);
        foreach ($headers as $name => $value) {
            $response->headers->set($name, (string) $value);
        }

        return $response;
    }

    /**
     * 429 Too Many Requests レスポンスを生成
     */
    protected function tooManyRequestsResponse(array $rateLimitInfo): JsonResponse
    {
        $body = $this->rateLimitService->getRateLimitExceededResponse($rateLimitInfo);
        $headers = $this->rateLimitService->getRateLimitHeaders($rateLimitInfo);

        return response()->json($body, 429, array_map('strval', $headers));
    }
}
