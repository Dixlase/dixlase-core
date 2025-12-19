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

namespace Tests\Unit;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Services\ApiRateLimitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiRateLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApiRateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiRateLimitService();
    }

    /**
     * APIキーのレートリミットチェック - 制限内
     */
    public function test_check_rate_limit_returns_not_limited_when_under_limit(): void
    {
        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST, [], null, ['rate_limit' => 10]);
        $apiKey = $apiKeyData['model'];

        $result = $this->service->checkRateLimit($apiKey);

        $this->assertFalse($result['is_limited']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(10, $result['remaining']);
        $this->assertEquals(0, $result['current']);
    }

    /**
     * APIキーのレートリミットチェック - 制限超過
     */
    public function test_check_rate_limit_returns_limited_when_over_limit(): void
    {
        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST, [], null, ['rate_limit' => 5]);
        $apiKey = $apiKeyData['model'];

        // 5件のリクエストを記録
        for ($i = 0; $i < 5; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'method' => 'GET',
                'endpoint' => '/api/test',
                'path' => 'api/test',
                'ip_address' => '127.0.0.1',
                'response_code' => 200,
                'requested_at' => Carbon::now(),
            ]);
        }

        $result = $this->service->checkRateLimit($apiKey);

        $this->assertTrue($result['is_limited']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertEquals(5, $result['current']);
    }

    /**
     * IP別レートリミットチェック - 制限内
     */
    public function test_check_ip_rate_limit_returns_not_limited_when_under_limit(): void
    {
        $result = $this->service->checkIpRateLimit('192.168.1.1', 100);

        $this->assertFalse($result['is_limited']);
        $this->assertEquals(100, $result['limit']);
        $this->assertEquals(100, $result['remaining']);
    }

    /**
     * IP別レートリミットチェック - 制限超過
     */
    public function test_check_ip_rate_limit_returns_limited_when_over_limit(): void
    {
        $ip = '192.168.1.100';
        $limit = 3;

        // 3件のリクエストを記録
        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'method' => 'GET',
                'endpoint' => '/api/test',
                'path' => 'api/test',
                'ip_address' => $ip,
                'response_code' => 200,
                'requested_at' => Carbon::now(),
            ]);
        }

        $result = $this->service->checkIpRateLimit($ip, $limit);

        $this->assertTrue($result['is_limited']);
        $this->assertEquals(0, $result['remaining']);
    }

    /**
     * エンドポイント別レートリミットチェック
     */
    public function test_check_endpoint_rate_limit(): void
    {
        $endpoint = '/api/users/{id}';
        $limit = 5;

        // 3件のリクエストを記録
        for ($i = 0; $i < 3; $i++) {
            ApiRequestLog::create([
                'method' => 'GET',
                'endpoint' => $endpoint,
                'path' => 'api/users/1',
                'ip_address' => '127.0.0.1',
                'response_code' => 200,
                'requested_at' => Carbon::now(),
            ]);
        }

        $result = $this->service->checkEndpointRateLimit($endpoint, null, $limit);

        $this->assertFalse($result['is_limited']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(2, $result['remaining']);
        $this->assertEquals(3, $result['current']);
    }

    /**
     * レートリミットヘッダー生成
     */
    public function test_get_rate_limit_headers(): void
    {
        $resetAt = Carbon::now()->addSeconds(60);
        $rateLimitInfo = [
            'limit' => 100,
            'remaining' => 95,
            'reset_at' => $resetAt,
        ];

        $headers = $this->service->getRateLimitHeaders($rateLimitInfo);

        $this->assertEquals(100, $headers['X-RateLimit-Limit']);
        $this->assertEquals(95, $headers['X-RateLimit-Remaining']);
        $this->assertEquals($resetAt->timestamp, $headers['X-RateLimit-Reset']);
    }

    /**
     * レートリミット超過レスポンス生成
     */
    public function test_get_rate_limit_exceeded_response(): void
    {
        $rateLimitInfo = [
            'limit' => 60,
            'current' => 65,
        ];

        $response = $this->service->getRateLimitExceededResponse($rateLimitInfo);

        $this->assertEquals('rate_limit_exceeded', $response['error']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('retry_after', $response);
        $this->assertEquals(60, $response['limit']);
        $this->assertEquals(65, $response['current']);
    }

    /**
     * デフォルトレートリミット設定の変更
     */
    public function test_set_default_rate_limit(): void
    {
        $this->service->setDefaultRateLimit(200);

        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST);
        $apiKey = $apiKeyData['model'];
        // rate_limitがnullの場合、デフォルト値が使用される
        $apiKey->rate_limit = null;
        $apiKey->save();

        $result = $this->service->checkRateLimit($apiKey);

        $this->assertEquals(200, $result['limit']);
    }

    /**
     * ウィンドウサイズ設定の変更
     */
    public function test_set_window_seconds(): void
    {
        $this->service->setWindowSeconds(120);

        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST, [], null, ['rate_limit' => 10]);
        $apiKey = $apiKeyData['model'];

        $result = $this->service->checkRateLimit($apiKey);

        $this->assertEquals(120, $result['window_seconds']);
    }

    /**
     * 古いログのクリーンアップ
     */
    public function test_cleanup_old_logs(): void
    {
        // 古いログを作成
        ApiRequestLog::create([
            'method' => 'GET',
            'endpoint' => '/api/old',
            'path' => 'api/old',
            'ip_address' => '127.0.0.1',
            'response_code' => 200,
            'requested_at' => Carbon::now()->subDays(35),
        ]);

        // 新しいログを作成
        ApiRequestLog::create([
            'method' => 'GET',
            'endpoint' => '/api/new',
            'path' => 'api/new',
            'ip_address' => '127.0.0.1',
            'response_code' => 200,
            'requested_at' => Carbon::now(),
        ]);

        $deleted = $this->service->cleanupOldLogs(30);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, ApiRequestLog::count());
    }

    /**
     * 全体統計の取得
     */
    public function test_get_overall_stats(): void
    {
        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST);
        $apiKey = $apiKeyData['model'];

        // 成功リクエスト
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'method' => 'GET',
            'endpoint' => '/api/users',
            'path' => 'api/users',
            'ip_address' => '127.0.0.1',
            'response_code' => 200,
            'response_time_ms' => 50,
            'requested_at' => Carbon::now(),
        ]);

        // エラーリクエスト
        ApiRequestLog::create([
            'api_key_id' => $apiKey->id,
            'method' => 'POST',
            'endpoint' => '/api/users',
            'path' => 'api/users',
            'ip_address' => '192.168.1.1',
            'response_code' => 400,
            'response_time_ms' => 30,
            'requested_at' => Carbon::now(),
        ]);

        $stats = $this->service->getOverallStats(7);

        $this->assertEquals(7, $stats['period_days']);
        $this->assertEquals(2, $stats['total_requests']);
        $this->assertEquals(1, $stats['successful_requests']);
        $this->assertEquals(1, $stats['error_requests']);
        $this->assertEquals(2, $stats['unique_ips']);
        $this->assertEquals(50, $stats['error_rate']);
    }

    /**
     * ウィンドウ外のリクエストはカウントされない
     */
    public function test_old_requests_not_counted_in_rate_limit(): void
    {
        $apiKeyData = ApiKey::generate('Test Key', ApiKey::ENV_TEST, [], null, ['rate_limit' => 5]);
        $apiKey = $apiKeyData['model'];

        // ウィンドウ外（2分前）のリクエストを記録
        for ($i = 0; $i < 5; $i++) {
            ApiRequestLog::create([
                'api_key_id' => $apiKey->id,
                'method' => 'GET',
                'endpoint' => '/api/test',
                'path' => 'api/test',
                'ip_address' => '127.0.0.1',
                'response_code' => 200,
                'requested_at' => Carbon::now()->subMinutes(2),
            ]);
        }

        $result = $this->service->checkRateLimit($apiKey);

        $this->assertFalse($result['is_limited']);
        $this->assertEquals(0, $result['current']);
    }
}
