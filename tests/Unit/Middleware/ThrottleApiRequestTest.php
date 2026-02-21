<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ThrottleApiRequest;
use App\Models\ApiKey;
use App\Services\ApiRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThrottleApiRequestTest extends TestCase
{
    use RefreshDatabase;

    protected ThrottleApiRequest $middleware;

    protected ApiRateLimitService $rateLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitService = new ApiRateLimitService();
        $this->middleware = new ThrottleApiRequest($this->rateLimitService);
    }

    /**
     * レートリミット内でリクエストが成功すること
     */
    public function test_passes_when_within_rate_limit(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * X-RateLimit-* ヘッダーが付与されること
     */
    public function test_adds_rate_limit_headers(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
    }

    /**
     * APIキー認証済みリクエストにキー別リミットが適用されること
     */
    public function test_uses_api_key_rate_limit_when_authenticated(): void
    {
        $apiKeyData = ApiKey::generate('Rate Test', ApiKey::ENV_TEST, [], null, [
            'rate_limit' => 30,
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->attributes->set('api_key', $apiKeyData['model']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('30', $response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * リミット超過で429を返すこと
     */
    public function test_returns_429_when_rate_limit_exceeded(): void
    {
        $apiKeyData = ApiKey::generate('Exceeded Key', ApiKey::ENV_TEST, [], null, [
            'rate_limit' => 2,
        ]);
        $apiKey = $apiKeyData['model'];

        // レートリミットを超えるまでログを記録
        for ($i = 0; $i < 2; $i++) {
            $this->rateLimitService->logRequest(
                Request::create('/api/test', 'GET'),
                $apiKey,
                200,
            );
        }

        $request = Request::create('/api/test', 'GET');
        $request->attributes->set('api_key', $apiKey);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(429, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals('rate_limit_exceeded', $decoded['error']);
    }

    /**
     * 未認証リクエストにIP別リミットが適用されること
     */
    public function test_uses_ip_rate_limit_when_unauthenticated(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        // デフォルトIPリミット（100）が適用される
        $this->assertEquals('100', $response->headers->get('X-RateLimit-Limit'));
    }
}
