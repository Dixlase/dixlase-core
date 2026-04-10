<?php

namespace Tests\Unit\Services;

use App\Models\ApiKey;
use App\Services\ApiRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRateLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApiRateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiRateLimitService();
    }

    public function test_check_rate_limit_returns_array(): void
    {
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [], null, [
            'rate_limit' => 100,
        ]);

        $check = $this->service->checkRateLimit($result['model']);

        $this->assertIsArray($check);
    }

    public function test_check_ip_rate_limit_returns_array(): void
    {
        $check = $this->service->checkIpRateLimit('192.168.1.1', 100);

        $this->assertIsArray($check);
    }

    public function test_get_rate_limit_headers_format(): void
    {
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [], null, [
            'rate_limit' => 100,
        ]);

        $check = $this->service->checkRateLimit($result['model']);
        $headers = $this->service->getRateLimitHeaders($check);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
    }

    public function test_set_default_rate_limit(): void
    {
        $this->service->setDefaultRateLimit(500);

        // チェーンメソッドが self を返すこと
        $result = $this->service->setDefaultRateLimit(500);
        $this->assertInstanceOf(ApiRateLimitService::class, $result);
    }

    public function test_set_window_seconds(): void
    {
        $result = $this->service->setWindowSeconds(3600);

        $this->assertInstanceOf(ApiRateLimitService::class, $result);
    }

    public function test_cleanup_old_logs(): void
    {
        $deleted = $this->service->cleanupOldLogs(30);

        $this->assertIsInt($deleted);
        $this->assertEquals(0, $deleted);
    }
}
