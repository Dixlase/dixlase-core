<?php

namespace Tests\Unit\Services;

use App\Enums\PluginHealthStatus;
use App\Services\ExtensionOperationService;
use Tests\TestCase;

class ExtensionOperationServiceTest extends TestCase
{
    protected ExtensionOperationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExtensionOperationService();
    }

    /**
     * isHealthStatusHealthy()がPluginHealthStatus::Healthy値でtrueを返すテスト
     */
    public function test_is_health_status_healthy_with_healthy_enum_value(): void
    {
        $method = new \ReflectionMethod(ExtensionOperationService::class, 'isHealthStatusHealthy');

        $this->assertTrue($method->invoke($this->service, PluginHealthStatus::Healthy->value));
    }

    /**
     * isHealthStatusHealthy()が旧リスクレベル値'low'でtrueを返すテスト（後方互換性）
     */
    public function test_is_health_status_healthy_with_legacy_low_value(): void
    {
        $method = new \ReflectionMethod(ExtensionOperationService::class, 'isHealthStatusHealthy');

        $this->assertTrue($method->invoke($this->service, 'low'));
    }

    /**
     * isHealthStatusHealthy()が非Healthy値でfalseを返すテスト
     */
    public function test_is_health_status_healthy_with_unhealthy_values(): void
    {
        $method = new \ReflectionMethod(ExtensionOperationService::class, 'isHealthStatusHealthy');

        $this->assertFalse($method->invoke($this->service, PluginHealthStatus::Advisory->value));
        $this->assertFalse($method->invoke($this->service, PluginHealthStatus::NeedsAttention->value));
        $this->assertFalse($method->invoke($this->service, PluginHealthStatus::NotVerified->value));
        $this->assertFalse($method->invoke($this->service, 'high'));
        $this->assertFalse($method->invoke($this->service, 'medium'));
        $this->assertFalse($method->invoke($this->service, 'unknown'));
    }

    /**
     * 操作種別定数が正しく定義されているテスト
     */
    public function test_operation_constants_are_defined(): void
    {
        $this->assertEquals('installed', ExtensionOperationService::OPERATION_INSTALLED);
        $this->assertEquals('uninstalled', ExtensionOperationService::OPERATION_UNINSTALLED);
        $this->assertEquals('enabled', ExtensionOperationService::OPERATION_ENABLED);
        $this->assertEquals('disabled', ExtensionOperationService::OPERATION_DISABLED);
    }

    /**
     * 拡張機能種別定数が正しく定義されているテスト
     */
    public function test_type_constants_are_defined(): void
    {
        $this->assertEquals('plugin', ExtensionOperationService::TYPE_PLUGIN);
        $this->assertEquals('theme', ExtensionOperationService::TYPE_THEME);
    }
}
