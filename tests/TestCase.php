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
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Whether a plugin migration pass is currently executing. Guards
     * against unbounded recursion: the pass runs `migrate`, which emits
     * its own `MigrationsEnded` events that would otherwise re-trigger
     * the pass. It is intentionally NOT a once-per-process flag — see
     * migratePluginsUnderTest().
     */
    protected static bool $migratingPlugins = false;

    /**
     * Drivers that hold real data and must never be migrate:fresh'd by a
     * test run. RefreshDatabase (used by the vast majority of suites)
     * runs `migrate:fresh` on the active connection — if a test process
     * inherits DB_CONNECTION pointing at one of these, every test would
     * drop and recreate the live database.
     *
     * The guard runs BEFORE parent::setUp() so RefreshDatabase's
     * setUpTraits()-driven migration call never fires.
     *
     * Escape hatch: set DLS_TESTS_ALLOW_NON_SQLITE=1 when you really do
     * want to run against a non-sqlite driver (e.g. a dedicated
     * disposable MySQL test container). Use sparingly.
     */
    private const FORBIDDEN_TEST_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlsrv', 'oci'];

    protected function setUp(): void
    {
        self::guardAgainstNonSqliteConnection();

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
     * Refresh the application, then arrange for plugin migrations to be
     * applied once core's `migrate:fresh` has finished.
     *
     * Plugins are normally discovered from the `plugins` table (or the
     * enabled-plugins cache), neither of which exists in the freshly
     * migrated test database. A plugin's service provider therefore never
     * boots under test, and the `loadMigrationsFrom()` it would call is
     * never reached — so DB-backed plugin tests fail with "no such table".
     *
     * Plugin migrations cannot simply be folded into core's
     * `migrate:fresh` run: a plugin's create-table migration is named
     * `0001_01_01_000NNN_*` and would sort *before* core's
     * `0001_01_01_000027_create_members_table`, so an inline foreign key
     * to `dls_members` fails with "referenced table does not exist".
     *
     * Instead they run as a separate `migrate` pass driven off the
     * Migrator's `MigrationsEnded` event. Core's `migrate:fresh` emits it
     * synchronously once every core table (including the deferred foreign
     * keys) exists, and *before* RefreshDatabase opens the per-test
     * transaction — and, crucially for the in-memory SQLite driver,
     * before the migrated PDO is cached — so the plugin tables land on
     * the same connection every test will use.
     *
     * The pass re-runs on *every* `migrate:fresh`, not once per process:
     * RefreshDatabase re-runs `migrate:fresh` whenever a test leaves its
     * connection without an open transaction (RefreshDatabase.php sets
     * `RefreshDatabaseState::$migrated = false`), which happens on MySQL
     * after any test that runs DDL — MySQL implicitly commits on DDL.
     * Each such `migrate:fresh` wipes the plugin tables, so they must be
     * re-applied alongside it.
     *
     * The `CommandFinished` console event is unusable here: it is only
     * emitted once `Kernel::rerouteSymfonyCommandEvents()` has run, which
     * does not happen for the programmatic `call()` RefreshDatabase uses.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        Event::listen(MigrationsEnded::class, function (): void {
            static::migratePluginsUnderTest();
        });
    }

    /**
     * Run a `migrate` pass for every plugin whose suite is part of the
     * current run.
     *
     * Runs on every core `migrate:fresh` (see refreshApplication()). The
     * `$migratingPlugins` guard is a recursion guard, not a once-only
     * flag: it absorbs the `MigrationsEnded` events that the plugin
     * `migrate` calls below emit themselves, while still letting a later
     * core `migrate:fresh` re-apply the plugin tables it wiped.
     */
    protected static function migratePluginsUnderTest(): void
    {
        if (static::$migratingPlugins) {
            return;
        }
        static::$migratingPlugins = true;

        try {
            foreach (static::pluginsUnderTest() as $plugin) {
                $path = base_path("plugins/{$plugin}/database/migrations");

                if (! is_dir($path)) {
                    continue;
                }

                Artisan::call('migrate', [
                    '--path' => $path,
                    '--realpath' => true,
                    '--force' => true,
                ]);
            }
        } finally {
            static::$migratingPlugins = false;
        }
    }

    /**
     * Plugin directory names whose migrations should be applied for the
     * current test run.
     *
     * When the run selects explicit `--testsuite`s (the CI does this, and
     * each plugin suite is named after its directory), only the plugin
     * suites in that list are migrated. With no `--testsuite` the run
     * covers everything, so every plugin is migrated.
     *
     * @return array<int, string>
     */
    public static function pluginsUnderTest(): array
    {
        $suites = static::requestedTestsuites();

        if ($suites !== null) {
            return array_values(array_filter(
                $suites,
                fn (string $name): bool => is_dir(base_path("plugins/{$name}/database/migrations")),
            ));
        }

        $dirs = glob(base_path('plugins/*/database/migrations'), GLOB_ONLYDIR) ?: [];

        return array_map(
            fn (string $dir): string => basename(dirname(dirname($dir))),
            $dirs,
        );
    }

    /**
     * Parse the `--testsuite` option out of the PHPUnit invocation.
     * Returns null when no `--testsuite` was passed (whole-suite run).
     *
     * Note: the name deliberately avoids a `test` prefix — Pint's
     * `php_unit_method_casing` fixer would otherwise mistake it for a
     * PHPUnit test method and snake_case it.
     *
     * @return array<int, string>|null
     */
    protected static function requestedTestsuites(): ?array
    {
        $argv = $_SERVER['argv'] ?? [];

        foreach ($argv as $index => $arg) {
            if (str_starts_with((string) $arg, '--testsuite=')) {
                return explode(',', substr((string) $arg, strlen('--testsuite=')));
            }

            if ($arg === '--testsuite' && isset($argv[$index + 1])) {
                return explode(',', (string) $argv[$index + 1]);
            }
        }

        return null;
    }

    /**
     * Refuse to bring up a test that points the active connection at a
     * non-sqlite driver, unless the operator explicitly opts in.
     *
     * Rationale: RefreshDatabase (and a few sibling traits) run
     * `migrate:fresh` against the active connection in setUp(). If a
     * developer or AI session invokes `php artisan test` inside a
     * container whose `.env` carries `DB_CONNECTION=mysql` (and
     * phpunit.xml's `<env>` override fails to apply), every test would
     * DROP TABLE the live database. This guard fires before
     * `parent::setUp()` so the destructive migration call is never
     * reached.
     *
     * The check reads the env directly (getenv() / $_SERVER / $_ENV)
     * instead of `config('database.default')` because the Laravel app
     * is not yet booted at this point — that's precisely the order we
     * want, since `parent::setUp()` is what boots it AND triggers
     * RefreshDatabase.
     *
     * Escape hatch: `DLS_TESTS_ALLOW_NON_SQLITE=1`. Intended for a
     * dedicated disposable test database — never for shared/production
     * data.
     */
    private static function guardAgainstNonSqliteConnection(): void
    {
        if (self::readEnv('DLS_TESTS_ALLOW_NON_SQLITE') === '1') {
            return;
        }

        $connection = self::readEnv('DB_CONNECTION');
        if ($connection === null || $connection === '') {
            return;
        }

        $driver = strtolower($connection);
        if (! in_array($driver, self::FORBIDDEN_TEST_DRIVERS, true)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            "Refusing to run tests with DB_CONNECTION=%s.\n\n".
            "Tests use RefreshDatabase, which runs `migrate:fresh` against the active\n".
            "connection — running against %s would DROP TABLE the live database.\n\n".
            "Fixes:\n".
            "  • Run tests inside a container whose .env points at sqlite, or\n".
            "  • Ensure phpunit.xml's <env name=\"DB_CONNECTION\" value=\"sqlite\" force=\"true\"/>\n".
            "    actually applies (the `force=\"true\"` attribute is required to override .env), or\n".
            "  • Use a dedicated disposable test container with a non-production database.\n\n".
            "Escape hatch (use sparingly, NEVER against shared/production data):\n".
            '  DLS_TESTS_ALLOW_NON_SQLITE=1 php artisan test …',
            $connection,
            $driver,
        ));
    }

    /**
     * Read an env var from the same sources Laravel's env() helper uses,
     * without requiring the Laravel app to be booted.
     */
    private static function readEnv(string $key): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        foreach ([$_SERVER ?? [], $_ENV ?? []] as $bag) {
            if (array_key_exists($key, $bag) && $bag[$key] !== '') {
                return (string) $bag[$key];
            }
        }

        return null;
    }
}
