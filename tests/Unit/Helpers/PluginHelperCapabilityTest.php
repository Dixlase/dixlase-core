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
 */

namespace Tests\Unit\Helpers;

use App\Helpers\PluginHelper;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginHelperCapabilityTest extends TestCase
{
    /**
     * テスト用に作成した一時プラグインディレクトリ
     *
     * @var array<int, string>
     */
    private array $tempPluginPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        PluginHelper::clearCapabilityCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempPluginPaths as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            }
        }
        PluginHelper::clearCapabilityCache();
        parent::tearDown();
    }

    public function test_has_capability_in_any_installed_returns_false_for_unknown_capability(): void
    {
        $this->assertFalse(
            PluginHelper::hasCapabilityInAnyInstalled('dls-test-nonexistent-capability-xyz-'.uniqid())
        );
    }

    public function test_has_capability_in_any_installed_detects_plugin_with_matching_capability(): void
    {
        $capability = 'test-cap-'.uniqid();
        $this->createTempPlugin('SandboxCapabilityTestPlugin', [
            'capabilities' => [$capability],
        ]);

        $this->assertTrue(PluginHelper::hasCapabilityInAnyInstalled($capability));
    }

    public function test_has_capability_in_any_installed_ignores_plugins_without_capabilities_field(): void
    {
        $uniqueCap = 'dls-test-no-caps-'.uniqid();
        $this->createTempPlugin('SandboxNoCapabilityPlugin', [
            // capabilities キーなし
        ]);

        $this->assertFalse(PluginHelper::hasCapabilityInAnyInstalled($uniqueCap));
    }

    public function test_has_capability_handles_invalid_capabilities_value(): void
    {
        $this->createTempPlugin('SandboxInvalidCapabilityPlugin', [
            'capabilities' => 'not-an-array',
        ]);

        $this->assertFalse(
            PluginHelper::hasCapabilityInAnyInstalled('not-an-array')
        );
    }

    /**
     * テスト用プラグインディレクトリを作成
     *
     * @param  array<string, mixed>  $jsonOverride
     */
    private function createTempPlugin(string $directoryName, array $jsonOverride = []): string
    {
        $path = base_path("plugins/{$directoryName}");
        File::ensureDirectoryExists($path);

        $json = array_merge([
            'name' => $directoryName,
            'slug' => strtolower($directoryName),
            'version' => '1.0.0',
        ], $jsonOverride);

        File::put($path.'/plugin.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->tempPluginPaths[] = $path;
        PluginHelper::clearCapabilityCache();

        return $path;
    }
}
