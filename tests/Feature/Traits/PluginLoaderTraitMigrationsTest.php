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

namespace Tests\Feature\Traits;

use App\Traits\PluginLoaderTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

/**
 * Pins the contract that PluginLoaderTrait MUST NOT hand plugin/theme
 * migration paths to Laravel's stock migrator.
 *
 * Plugin/theme migrations are applied through PluginMigrator /
 * ThemeMigrator (PluginInstall and ThemeInstall commands) and are
 * recorded in dedicated dls_plugin_migrations / dls_theme_migrations
 * ledgers. Letting them through the stock migrator as well caused two
 * known classes of breakage on live sites:
 *
 *   1. Duplicate ledger tracking — the same migration row in both
 *      dls_migrations and dls_plugin_migrations (or dls_theme_migrations).
 *   2. `php artisan migrate` re-running already-applied plugin/theme
 *      migrations after a rename, hitting "table already exists" because
 *      the stock ledger had no record of them.
 *
 * If a future refactor reintroduces stock-migrator registration here,
 * this test fails so the regression is caught at PR time.
 */
class PluginLoaderTraitMigrationsTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureDir = sys_get_temp_dir().'/dls-plugin-loader-trait-'.uniqid('', true);
        File::ensureDirectoryExists($this->fixtureDir.'/database/migrations');
        File::put(
            $this->fixtureDir.'/database/migrations/0001_01_01_000001_fixture_migration.php',
            "<?php\n",
        );
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureDir)) {
            File::deleteDirectory($this->fixtureDir);
        }

        parent::tearDown();
    }

    /**
     * Build a fixture ServiceProvider so the trait's protected methods
     * resolve against a real Application instance (loadMigrationsFrom
     * lives on ServiceProvider and is the call path we want to avoid
     * triggering).
     */
    private function fixtureProvider(): ServiceProvider
    {
        return new class($this->app) extends ServiceProvider
        {
            use PluginLoaderTrait;

            public function callLoadPluginMigrations(string $custom, string $core): void
            {
                $this->loadPluginMigrations($custom, $core);
            }

            public function callLoadFilesByType(string $type, string $a, string $b, string $slug): void
            {
                $this->loadFilesByType($type, $a, $b, $slug);
            }
        };
    }

    public function test_load_plugin_migrations_does_not_register_the_path_with_stock_migrator(): void
    {
        $migrationsDir = $this->fixtureDir.'/database/migrations';
        $before = $this->app['migrator']->paths();

        $this->fixtureProvider()->callLoadPluginMigrations($migrationsDir, '/nonexistent/path');

        $after = $this->app['migrator']->paths();
        $this->assertEquals(
            $before,
            $after,
            'PluginLoaderTrait::loadPluginMigrations must not register paths with Laravel stock migrator — plugin/theme migrations have dedicated ledgers.',
        );
        $this->assertNotContains(
            $migrationsDir,
            $after,
            'Fixture migration directory leaked into the stock migrator paths.',
        );
    }

    public function test_load_files_by_type_migrations_case_is_a_no_op_for_stock_migrator(): void
    {
        $migrationsDir = $this->fixtureDir.'/database/migrations';
        $before = $this->app['migrator']->paths();

        $this->fixtureProvider()->callLoadFilesByType('migrations', $migrationsDir, $migrationsDir, 'fixture-slug');

        $after = $this->app['migrator']->paths();
        $this->assertEquals(
            $before,
            $after,
            'The "migrations" branch of loadFilesByType() must stay a no-op for the stock migrator.',
        );
    }
}
