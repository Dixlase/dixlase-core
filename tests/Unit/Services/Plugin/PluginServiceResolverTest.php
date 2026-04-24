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

namespace Tests\Unit\Services\Plugin;

use App\Contracts\Plugin\MailCapableInterface;
use App\Contracts\Plugin\PluginCapabilityInterface;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Mockery;
use Tests\TestCase;

class PluginServiceResolverTest extends TestCase
{
    protected PluginPermissionService|Mockery\MockInterface $permissionService;

    protected PluginServiceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PluginPermissionService::class);
        $this->resolver = new PluginServiceResolver($this->permissionService);
    }

    /**
     * 手動登録した機能を解決できるテスト
     */
    public function test_resolve_registered_capability(): void
    {
        $instance = $this->createCapabilityMock('test-plugin');

        $this->permissionService
            ->shouldReceive('check')
            ->never();

        $this->resolver->register(PluginCapabilityInterface::class, $instance);

        $result = $this->resolver->resolve(PluginCapabilityInterface::class);

        $this->assertTrue($result->isResolved());
        $this->assertSame($instance, $result->instance);
    }

    /**
     * 権限チェック付き解決のテスト
     */
    public function test_resolve_with_permission_check(): void
    {
        $instance = $this->createMailCapableMock('inquiry-plugin');

        $this->permissionService
            ->shouldReceive('check')
            ->with('inquiry-plugin', 'mail.send')
            ->andReturn(true);

        $this->resolver->register(MailCapableInterface::class, $instance);

        $result = $this->resolver->resolve(MailCapableInterface::class);

        $this->assertTrue($result->isResolved());
        $this->assertSame($instance, $result->instance);
    }

    /**
     * 権限不足で解決失敗するテスト
     */
    public function test_resolve_fails_when_permission_denied(): void
    {
        $instance = $this->createMailCapableMock('inquiry-plugin');

        $this->permissionService
            ->shouldReceive('check')
            ->with('inquiry-plugin', 'mail.send')
            ->andReturn(false);

        $this->resolver->register(MailCapableInterface::class, $instance);

        $result = $this->resolver->resolve(MailCapableInterface::class);

        $this->assertFalse($result->isResolved());
        $this->assertTrue($result->isPermissionDenied());
        $this->assertEquals('mail.send', $result->deniedPermission);
    }

    /**
     * 機能が利用不可の場合の解決失敗テスト
     */
    public function test_resolve_fails_when_capability_unavailable(): void
    {
        $instance = $this->createCapabilityMock('test-plugin', available: false);

        $this->resolver->register(PluginCapabilityInterface::class, $instance);

        $result = $this->resolver->resolve(PluginCapabilityInterface::class);

        $this->assertFalse($result->isResolved());
        $this->assertEquals('capability_unavailable', $result->failureReason);
    }

    /**
     * 未登録の場合の解決失敗テスト
     */
    public function test_resolve_returns_not_found_when_no_implementations(): void
    {
        $result = $this->resolver->resolve(PluginCapabilityInterface::class);

        $this->assertFalse($result->isResolved());
        $this->assertEquals('not_found', $result->failureReason);
    }

    /**
     * 特定プラグインに限定した解決テスト
     */
    public function test_resolve_with_specific_plugin_slug(): void
    {
        $instance1 = $this->createCapabilityMock('plugin-a');
        $instance2 = $this->createCapabilityMock('plugin-b');

        $this->resolver->register(PluginCapabilityInterface::class, $instance1);
        $this->resolver->register(PluginCapabilityInterface::class, $instance2);

        $result = $this->resolver->resolve(PluginCapabilityInterface::class, 'plugin-b');

        $this->assertTrue($result->isResolved());
        $this->assertEquals('plugin-b', $result->pluginSlug);
    }

    /**
     * 存在しないプラグインスラッグでの解決テスト
     */
    public function test_resolve_with_nonexistent_plugin_slug(): void
    {
        $instance = $this->createCapabilityMock('plugin-a');
        $this->resolver->register(PluginCapabilityInterface::class, $instance);

        $result = $this->resolver->resolve(PluginCapabilityInterface::class, 'plugin-x');

        $this->assertFalse($result->isResolved());
        $this->assertEquals('not_found', $result->failureReason);
    }

    /**
     * 全実装を権限チェック付きで解決するテスト
     */
    public function test_resolve_all_returns_all_results(): void
    {
        $instance1 = $this->createCapabilityMock('plugin-a');
        $instance2 = $this->createCapabilityMock('plugin-b', available: false);

        $this->resolver->register(PluginCapabilityInterface::class, $instance1);
        $this->resolver->register(PluginCapabilityInterface::class, $instance2);

        $results = $this->resolver->resolveAll(PluginCapabilityInterface::class);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isResolved());
        $this->assertFalse($results[1]->isResolved());
    }

    /**
     * has()で存在確認できるテスト
     */
    public function test_has_returns_true_when_resolvable(): void
    {
        $instance = $this->createCapabilityMock('test-plugin');
        $this->resolver->register(PluginCapabilityInterface::class, $instance);

        $this->assertTrue($this->resolver->has(PluginCapabilityInterface::class));
    }

    /**
     * has()で存在しない場合falseを返すテスト
     */
    public function test_has_returns_false_when_not_resolvable(): void
    {
        $this->assertFalse($this->resolver->has(PluginCapabilityInterface::class));
    }

    /**
     * registerPermission()で権限マッピングを登録するテスト
     */
    public function test_register_permission_mapping(): void
    {
        $instance = $this->createCapabilityMock('test-plugin');

        $this->permissionService
            ->shouldReceive('check')
            ->with('test-plugin', 'custom.permission')
            ->andReturn(false);

        $this->resolver->registerPermission(PluginCapabilityInterface::class, 'custom.permission');
        $this->resolver->register(PluginCapabilityInterface::class, $instance);

        $result = $this->resolver->resolve(PluginCapabilityInterface::class);

        $this->assertFalse($result->isResolved());
        $this->assertEquals('custom.permission', $result->deniedPermission);
    }

    /**
     * getRegisteredInterfaces()テスト
     */
    public function test_get_registered_interfaces(): void
    {
        $instance = $this->createCapabilityMock('test-plugin');
        $this->resolver->register(PluginCapabilityInterface::class, $instance);
        $this->resolver->registerPermission(MailCapableInterface::class, 'mail.send');

        $interfaces = $this->resolver->getRegisteredInterfaces();

        $this->assertContains(PluginCapabilityInterface::class, $interfaces);
        $this->assertContains(MailCapableInterface::class, $interfaces);
    }

    /**
     * 権限不足の最初のプラグインをスキップし次を解決するテスト
     */
    public function test_resolve_skips_denied_and_finds_next(): void
    {
        $instance1 = $this->createMailCapableMock('plugin-a');
        $instance2 = $this->createMailCapableMock('plugin-b');

        $this->permissionService
            ->shouldReceive('check')
            ->with('plugin-a', 'mail.send')
            ->andReturn(false);

        $this->permissionService
            ->shouldReceive('check')
            ->with('plugin-b', 'mail.send')
            ->andReturn(true);

        $this->resolver->register(MailCapableInterface::class, $instance1);
        $this->resolver->register(MailCapableInterface::class, $instance2);

        $result = $this->resolver->resolve(MailCapableInterface::class);

        $this->assertTrue($result->isResolved());
        $this->assertEquals('plugin-b', $result->pluginSlug);
    }

    /**
     * 重複インスタンスが登録されないテスト
     */
    public function test_duplicate_instances_are_not_added(): void
    {
        $instance = $this->createCapabilityMock('test-plugin');

        $this->resolver->register(PluginCapabilityInterface::class, $instance);
        // タグからの重複はgetInstances内で処理される

        $results = $this->resolver->resolveAll(PluginCapabilityInterface::class);

        $this->assertCount(1, $results);
    }

    /**
     * PluginCapabilityInterface のモックを作成
     */
    protected function createCapabilityMock(string $pluginSlug, bool $available = true): PluginCapabilityInterface|Mockery\MockInterface
    {
        $mock = Mockery::mock(PluginCapabilityInterface::class);
        $mock->shouldReceive('getPluginSlug')->andReturn($pluginSlug);
        $mock->shouldReceive('isCapabilityAvailable')->andReturn($available);

        return $mock;
    }

    /**
     * MailCapableInterface のモックを作成
     */
    protected function createMailCapableMock(string $pluginSlug, bool $available = true): MailCapableInterface|Mockery\MockInterface
    {
        $mock = Mockery::mock(MailCapableInterface::class);
        $mock->shouldReceive('getPluginSlug')->andReturn($pluginSlug);
        $mock->shouldReceive('isCapabilityAvailable')->andReturn($available);

        return $mock;
    }
}
