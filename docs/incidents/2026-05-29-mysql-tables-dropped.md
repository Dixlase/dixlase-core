# 2026-05-29 — Brand production MySQL tables fully DROPped

> A Japanese mirror of this post-mortem lives at
> [`docs/ja/incidents/2026-05-29-mysql-tables-dropped.md`](../ja/incidents/2026-05-29-mysql-tables-dropped.md).

## Summary

On 2026-05-29, a Claude Code session ran `php artisan test` against
the Brand site's **production** PHP container. The test boot path
loaded `RefreshDatabase`, which connected to the live MySQL instance
and issued `migrate:fresh` — `DROP TABLE` against every Brand
production table. The data was recovered from Time Machine; no
permanent data loss, but operations were interrupted for several
hours.

This document records the timeline, the chain of factors that let the
command land on production, why none of the existing safeguards
caught it, what has shipped to prevent recurrence, and what remains.

## Timeline (UTC)

| Time     | Event                                                                                                                                                          |
| -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 16:30:04 | Session `4eabe326` invokes `docker exec dixlase-brand-app php artisan test --compact themes/DixlaseOnePage/tests/Unit/DixlaseOnePageSettingsLocalizationTest.php` |
| 16:30:24 | Same session re-runs the test with `--filter` for a single case                                                                                                |
| 16:30:41 | Same session runs an Inquiry plugin test via the same `path-direct` form                                                                                       |
| 16:30:48 | `dls_migrations.created_at` reset — DROP cycle has completed                                                                                                   |
| (later)  | Operator notices the empty schema, restores raw MySQL files from Time Machine, brings the site back                                                            |

## Mechanism

1. **`php artisan test <path>` was invoked as path-direct.** The argument was a relative test file path, not a `--testsuite` name.
2. **`phpunit.xml`'s `<env name="DB_CONNECTION" value="sqlite"/>` did not take effect.** The `<env>` element without `force="true"` only sets a value if the environment variable is not already present; the production container's `.env` had already exported `DB_CONNECTION=mysql`, so the existing value won.
3. **`RefreshDatabase` resolved the active connection at runtime as `mysql`.** With the `phpunit.xml` override silently ignored, the trait connected to the production database exactly as the application normally would.
4. **`migrate:fresh` ran on production.** `RefreshDatabase::setUp()` calls `migrate:fresh` whenever the in-memory marker is missing — which it was, because this was the first test in the process.
5. **Every Brand production table was DROPped and recreated empty.**

## Why existing safeguards did not catch this

- **`<env>` semantics in PHPUnit defaults to "set if unset", not "force".** This is the literal root cause: a single missing `force="true"` attribute on the relevant `<env>` lines made the entire override silent.
- **The Themes test path was not part of `phpunit.xml`'s `<testsuites>`.** `themes/DixlaseOnePage/tests/...` is not listed in the suite definitions, so the test could only be reached via path-direct invocation. Path-direct invocation evaluates the testsuite-level `<php>`/`<env>` differently from suite-named runs, which further weakened the override.
- **Production containers ship the full Composer dependency graph including `phpunit`.** There is no physical separation between "code that can run tests" and "code that runs against production data".
- **No DB-side privilege separation.** The Brand application's runtime MySQL user had the same `DROP TABLE` capability as a migration user would. A single misrouted artisan call had nothing to push back against it.
- **No telemetry on table counts.** The DROP itself was silent. Detection relied on the operator noticing the symptom in the UI.

## Completed countermeasures

| # | Where                                                                                              | What                                                                                                                                                                                                                                                       | Status                                                                                                                |
| - | -------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 1 | `dixlase-core` `tests/TestCase.php` + `phpunit.xml`                                                | Static `setUp()` guard that throws `RuntimeException` if `DB_CONNECTION` is `mysql`/`mariadb`/`pgsql`/`sqlsrv`/`oci` (escape hatch: `DLS_TESTS_ALLOW_NON_SQLITE=1`); `<env force="true">` on every test env line; `Themes` testsuite registered for `themes/*/tests`. | Shipped on `main` and `v0.1.0` as commit [`c809f073`](https://github.com/Dixlase/dixlase-core/commit/c809f073). PR [#44](https://github.com/Dixlase/dixlase-core/pull/44) was closed because the content was already on `main` via direct push. |
| 2 | `dixlase-docker-installer` `.claude/settings.json` + `.claude/hooks/block-destructive-container-ops.sh` | `PreToolUse:Bash` hook that denies any Bash invocation matching `docker (compose) exec <non-dev-container> ... (php artisan test|migrate:fresh|migrate:reset|migrate:refresh|db:wipe|vendor/bin/phpunit|composer test)`. Dev/sandbox containers are allow-listed. | Shipped on `main` as commit [`77f776b`](https://github.com/Dixlase/dixlase-docker-installer/commit/77f776b). 18/18 verification cases pass.                                                            |
| 3 | `plugin-dixlase-core-devkit` / `plugin-dixlase-devkit` shared rules                                | `Test Execution Safety` section added to the shared coding rules; surfaces in every plugin's regenerated `CLAUDE.md` via `dls:claude:setup`.                                                                                                                                | Open PRs: [plugin-dixlase-core-devkit#1](https://github.com/Dixlase/plugin-dixlase-core-devkit/pull/1), [plugin-dixlase-devkit#3](https://github.com/Dixlase/plugin-dixlase-devkit/pull/3). Pending merge.            |

## Outstanding countermeasures

Tracked in detail under [`.claude/plans/test-incident-followup.md`](../../../.claude/plans/test-incident-followup.md) (gitignored handoff). Summary by recommended order:

| Order | Task                                                            | Rationale                                                          |
| ----- | --------------------------------------------------------------- | ------------------------------------------------------------------ |
| 1     | ~~**G**: Claude Code PreToolUse hook~~                          | Completed (see countermeasure 2 above)                             |
| 2     | ~~**J**: This document~~                                        | Completed                                                          |
| 3     | **B**: DB user privilege separation                             | Deepest defense; protects against any future misrouted SQL/artisan |
| 4     | **I**: Application-level scheduled backups                      | Recovery insurance independent of host-level Time Machine          |
| 5     | **C**: Strip phpunit/test runner from production container image | Physical separation — nothing to misfire                           |
| 6     | **H**: Table-count drop alert                                   | Early-detection telemetry                                          |

## Lessons learned

- **No single safeguard is enough.** The five layers of defense the project already had (CLAUDE.md rules, `phpunit.xml` `<env>`, `RefreshDatabase` convention, separate dev/prod containers, host-level Time Machine) all individually failed to stop this incident. The recovery happened because of a *sixth* layer (Time Machine), not because any of the project's own layers worked.
- **AI sessions execute plausible-looking commands without hesitation.** A human operator might have paused at `docker exec dixlase-brand-app php artisan test` and asked "wait, am I sure this is dev?" — but the AI's mental model of "which container is production" is not load-bearing. The fix must be at the harness layer (G) or below, not at the AI's discretion.
- **Test runners do not belong on the same image as production data.** As long as `vendor/bin/phpunit` exists in the production container, *any* command that boots Laravel from a path under `tests/` can re-trigger this class of incident. Countermeasure C is the only architectural cut that eliminates the category.
- **Privilege separation at the DB layer is independent of any application bug.** No matter what the application or test framework decides to do, `GRANT SELECT, INSERT, UPDATE, DELETE` for the runtime user means `DROP TABLE` is rejected at the wire protocol. Countermeasure B is the most general defense.

## References

- `phpunit.xml` `<env force="true">` change: commit [`c809f073`](https://github.com/Dixlase/dixlase-core/commit/c809f073)
- Installer hook: commit [`77f776b`](https://github.com/Dixlase/dixlase-docker-installer/commit/77f776b)
- Follow-up plan: [`.claude/plans/test-incident-followup.md`](../../../.claude/plans/test-incident-followup.md)
- Migration editing policy (related background): `CLAUDE.md` "Migration Editing Policy"
