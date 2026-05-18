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

use App\Enums\PluginHealthStatus;
use App\Services\Licensing\LicenseValidator;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use Illuminate\Support\Facades\File;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class PluginHealthScorerApiCompatTest extends TestCase
{
    private const PLUGIN_SLUG = 'test-api-compat';

    private const PLUGIN_DIR_NAME = 'TestApiCompat';

    private PluginHealthScorer $scorer;

    private string $pluginDir;

    protected function setUp(): void
    {
        parent::setUp();

        $permissionService = Mockery::mock(PluginPermissionService::class);
        $this->scorer = new PluginHealthScorer($permissionService, new LicenseValidator());

        $this->pluginDir = base_path('plugins/'.self::PLUGIN_DIR_NAME);
        if (File::isDirectory($this->pluginDir)) {
            File::deleteDirectory($this->pluginDir);
        }
        File::makeDirectory($this->pluginDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->pluginDir)) {
            File::deleteDirectory($this->pluginDir);
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_no_issue_when_plugin_json_missing(): void
    {
        // Remove plugin.json before invoking; an absent manifest produces no issue
        // (other evaluators report missing manifests; this one stays silent).
        $this->assertSame([], $this->invokeEvaluator());
    }

    public function test_missing_api_version_issue_when_field_absent(): void
    {
        $this->writeManifest(['name' => 'Test', 'slug' => self::PLUGIN_SLUG]);

        $issues = $this->invokeEvaluator();

        $this->assertCount(1, $issues);
        $this->assertSame('missing_api_version', $issues[0]->type);
        $this->assertSame('warning', $issues[0]->severity);
        $this->assertSame(-5, $issues[0]->deduction);
    }

    public function test_no_issue_when_field_satisfies_core_version(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => '^0.1'],
        ]);

        $this->assertSame([], $this->invokeEvaluator());
    }

    public function test_incompatible_api_version_issue_when_range_excludes_core(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => '^0.2'],
        ]);

        $issues = $this->invokeEvaluator();

        $this->assertCount(1, $issues);
        $this->assertSame('incompatible_api_version', $issues[0]->type);
        $this->assertSame(-15, $issues[0]->deduction);
    }

    public function test_malformed_api_constraint_issue_when_value_is_not_semver(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => 'banana'],
        ]);

        $issues = $this->invokeEvaluator();

        $this->assertCount(1, $issues);
        $this->assertSame('malformed_api_constraint', $issues[0]->type);
        $this->assertSame(-10, $issues[0]->deduction);
    }

    private function writeManifest(array $data): void
    {
        File::put(
            "{$this->pluginDir}/plugin.json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function invokeEvaluator(): array
    {
        $method = new ReflectionMethod(PluginHealthScorer::class, 'evaluateApiCompatibility');
        $method->setAccessible(true);

        return $method->invoke(
            $this->scorer,
            self::PLUGIN_SLUG,
            PluginHealthStatus::getDeductionRules(),
        );
    }
}
