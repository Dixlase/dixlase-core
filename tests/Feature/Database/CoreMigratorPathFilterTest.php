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

namespace Tests\Feature\Database;

use App\Services\CoreMigrator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

/**
 * Pins the contract that the stock migrator never takes on plugin or
 * theme migration paths, no matter who registers them.
 *
 * PluginLoaderTraitMigrationsTest already pins the core-owned half of
 * this invariant (the trait must not register the paths). That is not
 * enough on its own: an extension service provider can call Laravel's
 * `loadMigrationsFrom()` directly and bypass the trait entirely, and
 * third-party extensions are outside core's reach. CoreMigrator enforces
 * the same invariant at the boundary, and these tests fail if that
 * enforcement is removed or weakened.
 *
 * The failure being prevented: a bare `php artisan migrate` walks the
 * extension migrations, finds no matching row in the core ledger (they
 * live in plugin_migrations / theme_migrations), and tries to re-create
 * tables the installer already created — SQLSTATE[42S01].
 */
class CoreMigratorPathFilterTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureDir = sys_get_temp_dir().'/dls-core-migrator-'.uniqid('', true);
        File::ensureDirectoryExists($this->fixtureDir.'/database/migrations');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureDir)) {
            File::deleteDirectory($this->fixtureDir);
        }

        parent::tearDown();
    }

    private function migrator(): Migrator
    {
        return $this->app['migrator'];
    }

    public function test_the_container_migrator_is_the_filtering_core_migrator(): void
    {
        $this->assertInstanceOf(
            CoreMigrator::class,
            $this->migrator(),
            'The stock migrator binding must be extended by CoreMigrator, otherwise extension migration paths reach `php artisan migrate`.',
        );

        $this->assertInstanceOf(
            CoreMigrator::class,
            $this->app->make(Migrator::class),
            'Type-hinted Migrator resolutions must get the filtering migrator too.',
        );
    }

    /**
     * @return list<array{0: string}>
     */
    public static function extensionPathProvider(): array
    {
        return [
            'plugin' => ['plugins/FixtureExtension/database/migrations'],
            'theme' => ['themes/FixtureExtension/database/migrations'],
        ];
    }

    /**
     * @dataProvider extensionPathProvider
     */
    public function test_extension_migration_paths_are_not_registered(string $relativePath): void
    {
        $path = base_path($relativePath);

        $this->migrator()->path($path);

        $this->assertNotContains(
            $path,
            $this->migrator()->paths(),
            "Extension migration path leaked into the stock migrator: {$relativePath}",
        );
    }

    public function test_load_migrations_from_in_an_extension_provider_is_neutralised(): void
    {
        $path = base_path('plugins/FixtureExtension/database/migrations');

        // The exact call every generated extension provider used to make.
        $provider = new class($this->app) extends ServiceProvider
        {
            public function registerMigrations(string $path): void
            {
                $this->loadMigrationsFrom($path);
            }
        };

        $provider->registerMigrations($path);

        $this->assertNotContains(
            $path,
            $this->migrator()->paths(),
            'An extension provider calling loadMigrationsFrom() directly must not reach the stock migrator.',
        );
    }

    public function test_non_extension_paths_are_still_registered(): void
    {
        $path = $this->fixtureDir.'/database/migrations';

        $this->migrator()->path($path);

        $this->assertContains(
            $path,
            $this->migrator()->paths(),
            'The filter must only reject extension paths — vendor packages and core still register migrations normally.',
        );
    }

    public function test_a_sibling_directory_is_not_mistaken_for_an_extension_root(): void
    {
        // `plugins-archive` shares a prefix with `plugins` but is not an
        // extension root; a naive str_starts_with() without a trailing
        // separator would swallow it.
        $path = base_path('plugins-archive/database/migrations');

        $this->migrator()->path($path);

        $this->assertContains(
            $path,
            $this->migrator()->paths(),
            'Only paths under the extension roots themselves may be filtered.',
        );
    }
}
