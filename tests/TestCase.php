<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests;

use App\Contracts\Site\SiteContextInterface;
use Database\Seeders\SitesSeeder;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Plugin directory names whose migrations have already been applied
     * in this process. Guards against re-running a plugin's migration
     * pass when several of its test classes execute in one run.
     *
     * @var array<int, string>
     */
    protected static array $appliedPluginMigrations = [];

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $basename = class_basename($modelName);

            // Check DixlaseCoreDevKit factories first (core model factories for testing)
            $coreDevFactory = 'Plugins\\DixlaseCoreDevKit\\Database\\Factories\\'.$basename.'Factory';
            if (class_exists($coreDevFactory)) {
                return $coreDevFactory;
            }

            // Check DixlaseDevKit factories
            $devKitFactory = 'Plugins\\DixlaseDevKit\\Database\\Factories\\'.$basename.'Factory';
            if (class_exists($devKitFactory)) {
                return $devKitFactory;
            }

            return 'Database\\Factories\\'.$basename.'Factory';
        });

        $this->ensurePrimarySiteSeeded();
    }

    /**
     * Ensure the primary site exists and SiteContext is primed.
     *
     * Multi-site foundation (v0.1.0) adds NOT NULL site_id to most tables and
     * BelongsToSite auto-fills it from SiteContext. Tests need a primary site
     * seeded so that model creates do not violate the NOT NULL constraint.
     */
    protected function ensurePrimarySiteSeeded(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        (new SitesSeeder())->run();

        try {
            app(SiteContextInterface::class)->setCurrent(1);
        } catch (\Throwable) {
            // SiteContext binding may be unavailable in narrow Unit tests; ignore.
        }
    }

    /**
     * Refresh the application, then arrange for the plugin-under-test's
     * own migrations to be applied.
     *
     * Plugins are normally discovered from the `plugins` table (or the
     * enabled-plugins cache), neither of which exists in the freshly
     * migrated test database. A plugin's service provider therefore never
     * boots under test, and the `loadMigrationsFrom()` it would call is
     * never reached — so DB-backed plugin tests fail with "no such table".
     *
     * The plugin's migrations cannot simply be folded into core's
     * `migrate:fresh` run: a plugin's create-table migration is named
     * `0001_01_01_000NNN_*` and would sort *before* core's
     * `0001_01_01_000027_create_members_table`, so an inline foreign key
     * to `dls_members` fails with "referenced table does not exist".
     *
     * Instead the plugin's migrations run as a separate `migrate` pass
     * *after* all core tables exist:
     *  - first test in the process: deferred until `migrate:fresh` emits
     *    `CommandFinished` (fired before the per-test transaction begins);
     *  - later tests (a different plugin in the same process): core is
     *    already migrated, so the pass runs immediately here — still
     *    before this test's transaction starts.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $plugin = static::pluginUnderTest(static::class);

        if ($plugin === null) {
            return;
        }

        if (RefreshDatabaseState::$migrated) {
            // Core schema already exists; migrate this plugin now, before
            // RefreshDatabase opens this test's transaction.
            static::ensurePluginMigrated($plugin);

            return;
        }

        // Core's migrate:fresh has not run yet. Defer the plugin pass
        // until it finishes so inline foreign keys to core tables resolve.
        Event::listen(CommandFinished::class, function (CommandFinished $event) use ($plugin): void {
            if ($event->command === 'migrate:fresh') {
                static::ensurePluginMigrated($plugin);
            }
        });
    }

    /**
     * Run a plugin's migrations as a standalone pass, once per process.
     *
     * Safe to call outside a transaction only: it is invoked either from
     * the `migrate:fresh` CommandFinished hook (before the first
     * transaction opens) or from refreshApplication() (after the previous
     * test's transaction has rolled back).
     */
    protected static function ensurePluginMigrated(string $plugin): void
    {
        if (in_array($plugin, static::$appliedPluginMigrations, true)) {
            return;
        }

        $path = base_path("plugins/{$plugin}/database/migrations");

        if (! is_dir($path)) {
            return;
        }

        static::$appliedPluginMigrations[] = $plugin;

        Artisan::call('migrate', [
            '--path' => $path,
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    /**
     * Derive the plugin directory name from a test class living in the
     * `Plugins\<Name>\Tests\…` namespace. Returns null for core tests
     * (namespace `Tests\…`).
     */
    public static function pluginUnderTest(string $testClass): ?string
    {
        $segments = explode('\\', $testClass);

        return ($segments[0] ?? null) === 'Plugins' && isset($segments[1])
            ? $segments[1]
            : null;
    }
}
