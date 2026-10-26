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
use Illuminate\Support\Facades\Config;
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
     * Memoised result of extensionFactoryNamespaces(). The set of installed
     * extensions does not change inside a test run, and the lookup runs in
     * every setUp().
     *
     * @var list<string>|null
     */
    private static ?array $extensionFactoryNamespaces = null;

    /**
     * Namespaces searched for a model factory before core's own.
     *
     * `config('testing.factory_extensions')` pins the order when it decides
     * something; empty means every extension that ships a factory directory,
     * sorted by directory name. Core names no extension either way, because
     * this repository is public and some extensions are not distributed
     * (dixlase-core#619).
     *
     * @return list<string>
     */
    private static function extensionFactoryNamespaces(): array
    {
        if (self::$extensionFactoryNamespaces !== null) {
            return self::$extensionFactoryNamespaces;
        }

        $configured = config('testing.factory_extensions');
        $directories = is_array($configured) && $configured !== []
            ? array_values(array_filter(array_map('strval', $configured)))
            : self::discoverFactoryExtensions();

        return self::$extensionFactoryNamespaces = array_map(
            static fn (string $directory): string => 'Plugins\\'.$directory.'\\Database\\Factories\\',
            $directories
        );
    }

    /**
     * Extension directories that ship a factory directory, sorted.
     *
     * Read-only: it looks at plugins/ and writes nothing. The sort is what
     * makes the result independent of the order the filesystem happens to
     * return.
     *
     * @return list<string>
     */
    private static function discoverFactoryExtensions(): array
    {
        $directories = [];

        foreach (glob(base_path('plugins/*/database/factories'), GLOB_ONLYDIR) ?: [] as $path) {
            $directories[] = basename(dirname($path, 2));
        }

        sort($directories);

        return array_values(array_unique($directories));
    }

    /**
     * Drivers that hold real data and must never be migrate:fresh'd by a
     * test run. RefreshDatabase (used by the vast majority of suites)
     * runs `migrate:fresh` on the active connection — if a test process
     * inherits DB_CONNECTION pointing at one of these, every test would
     * drop and recreate the live database.
     *
     * Two checks consult this list, and both run before RefreshDatabase's
     * setUpTraits()-driven migration call: guardAgainstNonSqliteConnection()
     * reads the environment before the app exists, and
     * guardAgainstNonSqliteDefaultConnection() reads the booted
     * configuration. The second is the authoritative one -- see its
     * docblock for why an environment read is not enough (#541).
     *
     * Escape hatch: set DLS_TESTS_ALLOW_NON_SQLITE=1 when you really do
     * want to run against a non-sqlite driver (e.g. a dedicated
     * disposable MySQL test container, which is what CI uses). Use
     * sparingly.
     */
    private const FORBIDDEN_TEST_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlsrv', 'oci'];

    protected function setUp(): void
    {
        self::guardAgainstNonSqliteConnection();

        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $basename = class_basename($modelName);

            // An extension may ship a factory for a core model, and it takes
            // precedence over core's own when the extension is installed.
            foreach (self::extensionFactoryNamespaces() as $namespace) {
                $candidate = $namespace.$basename.'Factory';
                if (class_exists($candidate)) {
                    return $candidate;
                }
            }

            return 'Database\\Factories\\'.$basename.'Factory';
        });

        // The scheduled security checks keep their alerts in a file under
        // storage (ScheduledSecurityCheckMonitor). Give every test its own
        // throwaway file, so an alert raised by one test (e.g. a failing
        // `audit:integrity verify`) never shows up as an admin banner in
        // another, and the real storage/app is never written.
        $scheduledChecksState = storage_path('framework/testing/scheduled-checks-'.uniqid('', true).'.json');
        config(['security.scheduled_checks.state_file' => $scheduledChecksState]);
        $this->beforeApplicationDestroyed(static function () use ($scheduledChecksState): void {
            @unlink($scheduledChecksState);
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
     * `0001_01_01_000028_create_members_table`, so an inline foreign key
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

        self::guardAgainstNonSqliteDefaultConnection();

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
     * This is the first of two checks, and the cheap one: it reads the
     * environment before the app exists, so it can refuse the run without
     * booting anything. It cannot be the only one. The environment is not
     * what the application connects with -- a cached config, a connection
     * named in config rather than in the environment, or a `.env` value
     * phpunit did not override all reach the database and not this check.
     * guardAgainstNonSqliteDefaultConnection() reads the booted config and
     * still runs before RefreshDatabase (#541).
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
     * Refuse the run when the booted application's default connection
     * names a driver that holds real data.
     *
     * This is the authoritative check, because it reads what the
     * application will actually connect with rather than what the
     * environment suggests. The two differ more often than it looks: a
     * `bootstrap/cache/config.php` left behind by `config:cache` is read
     * instead of the environment entirely, a connection can be named in
     * `config/database.php` with no environment variable involved, and
     * phpunit's own `<env force="true">` cannot overwrite a real process
     * environment variable (#541).
     *
     * It runs from refreshApplication(), which Laravel calls from
     * setUpTheTestEnvironment() ahead of setUpTraits() -- so the app is
     * booted and RefreshDatabase has not migrated yet.
     *
     * Escape hatch: DLS_TESTS_ALLOW_NON_SQLITE=1, the same one the
     * pre-boot guard honours.
     */
    private static function guardAgainstNonSqliteDefaultConnection(): void
    {
        if (self::readEnv('DLS_TESTS_ALLOW_NON_SQLITE') === '1') {
            return;
        }

        $connection = (string) Config::get('database.default');
        if ($connection === '') {
            return;
        }

        $driver = strtolower((string) Config::get("database.connections.{$connection}.driver", $connection));
        if (! in_array($driver, self::FORBIDDEN_TEST_DRIVERS, true)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            "Refusing to run tests: the booted application's default connection \"%s\" uses the %s driver.\n\n".
            "Tests use RefreshDatabase, which runs `migrate:fresh` against that connection —\n".
            "every table it holds would be destroyed and rebuilt.\n\n".
            "This check reads config('database.default') after the application booted, so it\n".
            "sees what the application connects with. Where it can come from:\n".
            "  • a bootstrap/cache/config.php left behind by `config:cache` (clear it)\n".
            "  • DB_CONNECTION in the real process environment, which phpunit.xml cannot\n".
            "    override (a container's environment:, an exported shell variable, a CI step env)\n".
            "  • a default named in config/database.php itself\n\n".
            "Escape hatch (use sparingly, NEVER against shared/production data):\n".
            '  DLS_TESTS_ALLOW_NON_SQLITE=1 php artisan test …',
            $connection,
            $driver,
        ));
    }

    /**
     * Read an env var the way Laravel's env() helper does, without
     * requiring the Laravel app to be booted.
     *
     * The order matters, and it is not the obvious one.
     * `Illuminate\Support\Env` builds its repository from $_SERVER, then
     * $_ENV, then putenv — so a variable in the real process environment
     * wins over everything `phpunit.xml` sets, because phpunit writes
     * putenv() and $_ENV and never $_SERVER. Reading getenv() first made
     * this guard report the value phpunit asked for while the application
     * connected with the one from the process environment, which is how a
     * suite pinned to sqlite ran on MySQL in CI for months (#541).
     */
    private static function readEnv(string $key): ?string
    {
        foreach ([$_SERVER, $_ENV] as $bag) {
            if (array_key_exists($key, $bag) && $bag[$key] !== '') {
                return (string) $bag[$key];
            }
        }

        $value = getenv($key);

        return $value !== false && $value !== '' ? $value : null;
    }
}
