<?php

namespace Tests\Unit\DTO\Plugin;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\Plugin\CapabilityResolutionResult;
use Mockery;
use Tests\TestCase;

class CapabilityResolutionResultTest extends TestCase
{
    /**
     * 成功結果の生成テスト
     */
    public function test_success_creates_resolved_result(): void
    {
        $instance = Mockery::mock(PluginCapabilityInterface::class);
        $instance->shouldReceive('getPluginSlug')->andReturn('test-plugin');

        $result = CapabilityResolutionResult::success($instance);

        $this->assertTrue($result->isResolved());
        $this->assertSame($instance, $result->instance);
        $this->assertEquals('test-plugin', $result->pluginSlug);
        $this->assertNull($result->failureReason);
        $this->assertNull($result->deniedPermission);
    }

    /**
     * 権限拒否結果の生成テスト
     */
    public function test_permission_denied_result(): void
    {
        $result = CapabilityResolutionResult::permissionDenied('test-plugin', 'mail.send');

        $this->assertFalse($result->isResolved());
        $this->assertTrue($result->isPermissionDenied());
        $this->assertEquals('test-plugin', $result->pluginSlug);
        $this->assertEquals('permission_denied', $result->failureReason);
        $this->assertEquals('mail.send', $result->deniedPermission);
        $this->assertNull($result->instance);
    }

    /**
     * 利用不可結果の生成テスト
     */
    public function test_unavailable_result(): void
    {
        $result = CapabilityResolutionResult::unavailable('test-plugin');

        $this->assertFalse($result->isResolved());
        $this->assertFalse($result->isPermissionDenied());
        $this->assertEquals('capability_unavailable', $result->failureReason);
    }

    /**
     * 未発見結果の生成テスト
     */
    public function test_not_found_result(): void
    {
        $result = CapabilityResolutionResult::notFound('test-plugin');

        $this->assertFalse($result->isResolved());
        $this->assertEquals('not_found', $result->failureReason);
    }

    /**
     * 未発見結果のプラグインスラッグnullテスト
     */
    public function test_not_found_without_plugin_slug(): void
    {
        $result = CapabilityResolutionResult::notFound();

        $this->assertFalse($result->isResolved());
        $this->assertNull($result->pluginSlug);
    }

    /**
     * JSON直列化テスト
     */
    public function test_json_serialization(): void
    {
        $result = CapabilityResolutionResult::permissionDenied('test-plugin', 'mail.send');
        $json = $result->jsonSerialize();

        $this->assertFalse($json['resolved']);
        $this->assertEquals('test-plugin', $json['plugin_slug']);
        $this->assertEquals('permission_denied', $json['failure_reason']);
        $this->assertEquals('mail.send', $json['denied_permission']);
    }

    /**
     * 成功結果のJSON直列化テスト
     */
    public function test_success_json_serialization(): void
    {
        $instance = Mockery::mock(PluginCapabilityInterface::class);
        $instance->shouldReceive('getPluginSlug')->andReturn('test-plugin');

        $result = CapabilityResolutionResult::success($instance);
        $json = $result->jsonSerialize();

        $this->assertTrue($json['resolved']);
        $this->assertEquals('test-plugin', $json['plugin_slug']);
        $this->assertNull($json['failure_reason']);
        $this->assertNull($json['denied_permission']);
    }
}
