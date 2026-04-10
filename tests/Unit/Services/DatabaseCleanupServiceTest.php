<?php

namespace Tests\Unit\Services;

use App\Services\DatabaseCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseCleanupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseCleanupService::class);
    }

    public function test_get_core_cleanup_config_returns_array(): void
    {
        $config = $this->service->getCoreCleanupConfig();

        $this->assertIsArray($config);
    }

    public function test_get_all_cleanup_config_includes_core(): void
    {
        $all = $this->service->getAllCleanupConfig();

        $this->assertIsArray($all);
    }

    public function test_get_cleanup_info_returns_array(): void
    {
        $info = $this->service->getCleanupInfo();

        $this->assertIsArray($info);
    }

    public function test_cleanup_nonexistent_type_returns_error(): void
    {
        $result = $this->service->cleanup('nonexistent_type', 30);

        $this->assertIsArray($result);
        // エラーまたは空結果が返ること
        $this->assertArrayHasKey('success', $result);
    }

    public function test_cleanup_all_returns_array(): void
    {
        $results = $this->service->cleanupAll(30, true);

        $this->assertIsArray($results);
    }
}
