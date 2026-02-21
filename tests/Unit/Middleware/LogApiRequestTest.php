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

use App\Http\Middleware\LogApiRequest;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Services\ApiRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class LogApiRequestTest extends TestCase
{
    use RefreshDatabase;

    protected LogApiRequest $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LogApiRequest(new ApiRateLimitService());
    }

    /**
     * リクエスト開始時刻がセットされること
     */
    public function test_sets_request_start_time(): void
    {
        $request = Request::create('/api/test', 'GET');

        $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertNotNull($request->attributes->get('api_request_start'));
    }

    /**
     * terminate後にリクエストがログに記録されること
     */
    public function test_logs_request_on_terminate(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->attributes->set('api_request_start', microtime(true));

        $response = new Response(json_encode(['ok' => true]), 200);

        $this->middleware->terminate($request, $response);

        $this->assertDatabaseHas('api_request_logs', [
            'method' => 'GET',
            'path' => 'api/test',
            'response_code' => 200,
        ]);
    }

    /**
     * APIキー認証済みリクエストがキーIDと共に記録されること
     */
    public function test_logs_with_api_key_when_authenticated(): void
    {
        $apiKeyData = ApiKey::generate('Log Test', ApiKey::ENV_TEST);
        $apiKey = $apiKeyData['model'];

        $request = Request::create('/api/test', 'POST');
        $request->attributes->set('api_request_start', microtime(true));
        $request->attributes->set('api_key', $apiKey);

        $response = new Response(json_encode(['ok' => true]), 201);

        $this->middleware->terminate($request, $response);

        $this->assertDatabaseHas('api_request_logs', [
            'api_key_id' => $apiKey->id,
            'method' => 'POST',
            'response_code' => 201,
        ]);
    }

    /**
     * エラーレスポンスのエラー情報が記録されること
     */
    public function test_logs_error_info_for_error_responses(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->attributes->set('api_request_start', microtime(true));

        $response = new Response(
            json_encode(['error' => 'not_found', 'message' => 'Resource not found']),
            404,
        );

        $this->middleware->terminate($request, $response);

        $log = ApiRequestLog::latest('id')->first();
        $this->assertEquals(404, $log->response_code);
        $this->assertEquals('not_found', $log->error_code);
        $this->assertEquals('Resource not found', $log->error_message);
    }

    /**
     * レスポンスタイムが記録されること
     */
    public function test_logs_response_time(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->attributes->set('api_request_start', microtime(true) - 0.05); // 50ms前

        $response = new Response('{}', 200);

        $this->middleware->terminate($request, $response);

        $log = ApiRequestLog::latest('id')->first();
        $this->assertNotNull($log->response_time_ms);
        $this->assertGreaterThanOrEqual(40, $log->response_time_ms);
    }
}
