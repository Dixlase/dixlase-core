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

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tests for the Plugin API compatibility surface in dls:plugin:audit output.
 *
 * The compat data should appear:
 *   - As a colored line in the human-readable report
 *   - As an `api_compatibility` key in the JSON output
 *
 * Both paths reuse ExtensionCompatibilityChecker, which has its own unit
 * tests; here we verify the integration into the audit command.
 */
class PluginAuditApiCompatTest extends TestCase
{
    use RefreshDatabase;

    private const PLUGIN_DIR_NAME = 'TestAuditApiCompat';

    private const PLUGIN_SLUG = 'test-audit-api-compat';

    private string $pluginDir;

    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function test_json_output_carries_compatible_status_for_declared_plugin(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => '^0.1'],
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
            '--json' => true,
        ]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('api_compatibility', $decoded);
        $this->assertSame('compatible', $decoded['api_compatibility']['status']);
        $this->assertSame('^0.1', $decoded['api_compatibility']['declared']);
        $this->assertSame('0.1.0', $decoded['api_compatibility']['core_version']);
    }

    public function test_json_output_carries_missing_declaration_status(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
            '--json' => true,
        ]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame('missing_declaration', $decoded['api_compatibility']['status']);
        $this->assertNull($decoded['api_compatibility']['declared']);
    }

    public function test_json_output_carries_incompatible_status(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => '^0.2'],
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
            '--json' => true,
        ]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame('incompatible', $decoded['api_compatibility']['status']);
        $this->assertSame('^0.2', $decoded['api_compatibility']['declared']);
    }

    public function test_json_output_carries_malformed_constraint_status(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => 'not-a-semver'],
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
            '--json' => true,
        ]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame('malformed_constraint', $decoded['api_compatibility']['status']);
        $this->assertSame('not-a-semver', $decoded['api_compatibility']['declared']);
    }

    public function test_human_report_renders_api_compatibility_line(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
            'requires' => ['dixlase_api' => '^0.1'],
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Plugin API:', $output);
        $this->assertStringContainsString('compatible', $output);
        $this->assertStringContainsString('^0.1', $output);
    }

    public function test_human_report_includes_message_for_problem_status(): void
    {
        $this->writeManifest([
            'name' => 'Test',
            'slug' => self::PLUGIN_SLUG,
        ]);

        Artisan::call('dls:plugin:audit', [
            'plugin' => self::PLUGIN_DIR_NAME,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Plugin API:', $output);
        $this->assertStringContainsString('missing_declaration', $output);
        $this->assertStringContainsString('Extension does not declare requires.dixlase_api', $output);
    }

    private function writeManifest(array $data): void
    {
        File::put(
            $this->pluginDir.'/plugin.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
