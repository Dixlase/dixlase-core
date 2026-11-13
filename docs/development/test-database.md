# Which Database the Test Suite Runs On

There are two answers, and both are deliberate.

| Where | Driver | Decided by |
| --- | --- | --- |
| A local run (`php artisan test`) | sqlite, in memory | `phpunit.xml` — `<env name="DB_CONNECTION" value="sqlite" force="true"/>` |
| CI, `Core Tests` and `Plugin Tests` | each leg's database: SQLite, or MySQL / MariaDB in a service container (see below) | the matrix and the step `env:` in `.github/workflows/test.yml`; a MySQL or MariaDB leg also sets `DLS_TESTS_ALLOW_NON_SQLITE: 1` |
| CI, `Migration Test` | MySQL 8.4 in a service container | it runs `migrate`, not the suite |

**A test therefore has to pass on both.** Nothing in a test may depend on the
driver. A test that reached for `PRAGMA defer_foreign_keys` passed locally every
time and failed only in CI (#515), which is exactly the shape this costs.

## Which versions CI runs (#981)

| Legs | Databases | On failure |
| --- | --- | --- |
| Every PHP version, 8.3 up | SQLite, MySQL 9.7, MariaDB 12.3 (the newest LTS) | fail |
| PHP 8.3 only — the supported floor | MySQL 8.4, MariaDB 10.11 | fail |
| PHP 8.3 only — widely run, outside the supported range | MySQL 8.0, MariaDB 11.8 | warn only |

The required check `Core Tests (PHP 8.3)` is the PHP 8.3 / MySQL 9.7 leg. When a vendor
support window ends, the floor legs move with the documented floor in
[requirements](../requirements.md).

## Why CI uses MySQL and MariaDB

Production runs MySQL or MariaDB. The CI job is the only place the suite is
exercised against it, so a difference between the two engines — collation,
strict mode, an implicit commit on DDL, the behaviour of a deferred foreign
key — shows up there rather than on a site.

Its database is a service container that exists for the length of the job. That
is what makes `RefreshDatabase` acceptable there: it rebuilds the schema from
scratch in every `setUp()`, and there is nothing in that container to lose.

## Why `phpunit.xml` cannot settle it on its own

`force="true"` only beats `.env`. Dotenv is immutable: it skips a key that is
already set, so without `force` a `DB_CONNECTION=mysql` in `.env` would survive
into the run.

It does **not** beat the real process environment. phpunit writes its `<env>`
values with `putenv()` and into `$_ENV`; Laravel's `Illuminate\Support\Env`
reads `$_SERVER` first, and the CLI SAPI fills `$_SERVER` from the process
environment. So an exported `DB_CONNECTION`, a container's `environment:` block
or a workflow step's `env:` wins over every line in `phpunit.xml` — silently.
That is how the suite ran on MySQL in CI for months while every file in the
repository said sqlite (#541).

## The two guards in `tests/TestCase.php`

Both consult the same list of drivers that hold real data
(`FORBIDDEN_TEST_DRIVERS`), and both run before `RefreshDatabase` migrates
anything.

1. **Before the app boots** — `guardAgainstNonSqliteConnection()` reads the
   environment, in the same order `env()` does, so it cannot report one driver
   while the application uses another.
2. **On the booted configuration** — `guardAgainstNonSqliteDefaultConnection()`
   reads `config('database.default')` from `refreshApplication()`, which Laravel
   calls before `setUpTraits()`. This one is authoritative: a
   `bootstrap/cache/config.php` left behind by `config:cache`, or a default
   named in `config/database.php` with no environment variable involved, reaches
   the database and not the environment.

`tests/Unit/TestSuiteDatabaseDriverTest.php` asserts the invariant from the
other side: a run that has not opted in is on sqlite, and the guard sees what
`env()` sees.

## Running locally against something other than sqlite

Set `DLS_TESTS_ALLOW_NON_SQLITE=1`, and only ever against a disposable database
you own:

```bash
DLS_TESTS_ALLOW_NON_SQLITE=1 php artisan test --testsuite=Feature
```

Never against a container that holds data you want to keep. `RefreshDatabase`
rebuilds the active connection's schema in every `setUp()`.

Related: [Issue Guidelines](issues.md) · `phpunit.xml` · `.github/workflows/test.yml`
