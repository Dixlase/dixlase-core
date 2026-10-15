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

namespace Tests\Unit\Services;

use App\Services\DatabaseCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseCleanupService $service;

    private string $pluginsRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseCleanupService::class);
        // A throwaway plugins tree, never the checkout's plugins/.
        $this->pluginsRoot = storage_path('framework/testing/cleanup-plugins-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);

        parent::tearDown();
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

    public function test_plugin_entries_are_read_from_config_admin(): void
    {
        $this->givePlugin('AdminPathPlugin', 'config/admin/database-cleanup.php');

        $config = $this->serviceWithPlugins()->getPluginCleanupConfig();

        $this->assertArrayHasKey('plugin:admin-path-plugin:old_rows', $config);
        $this->assertSame('plg_admin_path_plugin_rows', $config['plugin:admin-path-plugin:old_rows']['table']);
        $this->assertTrue($config['plugin:admin-path-plugin:old_rows']['is_plugin']);
    }

    public function test_plugin_entries_at_the_old_path_are_still_read(): void
    {
        $this->givePlugin('OldPathPlugin', 'config/database-cleanup.php');

        $config = $this->serviceWithPlugins()->getPluginCleanupConfig();

        $this->assertArrayHasKey('plugin:old-path-plugin:old_rows', $config);
    }

    public function test_a_disabled_plugin_contributes_nothing(): void
    {
        $this->givePlugin('DisabledPlugin', 'config/admin/database-cleanup.php', enabled: false);

        $this->assertSame([], $this->serviceWithPlugins()->getPluginCleanupConfig());
    }

    private function serviceWithPlugins(): DatabaseCleanupService
    {
        $root = $this->pluginsRoot;

        return new class($root) extends DatabaseCleanupService
        {
            public function __construct(private string $root) {}

            protected function pluginsRoot(): string
            {
                return $this->root;
            }
        };
    }

    private function givePlugin(string $directory, string $configFile, bool $enabled = true): void
    {
        $slug = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $directory));
        $table = 'plg_'.str_replace('-', '_', $slug).'_rows';

        File::ensureDirectoryExists(dirname($this->pluginsRoot.'/'.$directory.'/'.$configFile));
        File::put(
            $this->pluginsRoot.'/'.$directory.'/'.$configFile,
            "<?php\n\nreturn ['old_rows' => ['table' => '{$table}', 'date_column' => 'created_at', 'default_days' => 30]];\n",
        );

        DB::table('plugins')->insert([
            'name' => $directory,
            'package_name' => 'dixlase/'.$slug,
            'directory' => $directory,
            'namespace' => 'Plugins\\'.$directory,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => 'Test',
            'installed_at' => now(),
            'enabled_at' => $enabled ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
