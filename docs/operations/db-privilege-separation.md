# DB Privilege Separation

> A Japanese mirror lives at [`docs/ja/operations/db-privilege-separation.md`](../ja/operations/db-privilege-separation.md).

This guide explains how to opt in to running Dixlase with **two** database
users — one for the application (DML only) and one for migrations (DDL
included) — so that a misrouted destructive query cannot drop production
tables. Background: see [2026-05-29 incident post-mortem](../incidents/2026-05-29-mysql-tables-dropped.md).

## What it does

| User                              | Used by                                                                  | Grants                                                  |
| --------------------------------- | ------------------------------------------------------------------------ | ------------------------------------------------------- |
| `DB_USERNAME` (default `dixlase`) | Application runtime, web requests, queue workers, Tinker, normal artisan | `SELECT, INSERT, UPDATE, DELETE` only                   |
| `DB_MIGRATE_USERNAME`             | `php artisan migrate --database=mysql_migrate`, deploy scripts           | `ALL PRIVILEGES` (CREATE, ALTER, DROP, TRUNCATE, …)     |

If the runtime account is ever used to issue `DROP TABLE` — whether by
a misrouted `RefreshDatabase`, a stray `migrate:fresh`, a SQL-injection
payload, or a typo — MySQL/MariaDB rejects the statement at the wire
protocol layer.

## How to enable (new install)

1. **Decide on the migrator credentials.**

   ```bash
   # in .env (alongside DB_USERNAME / DB_PASSWORD)
   DB_MIGRATE_USERNAME=dixlase_migrator
   DB_MIGRATE_PASSWORD=<a-strong-random-password>
   ```

2. **Initialise the MySQL data directory.** The docker installer's init
   hook (`mysql-initdb/01-permissions.sh`) only runs on a fresh data
   dir. For a brand-new install this is automatic. For an existing
   install see "How to enable (existing install)" below.

3. **Verify the split.** Connect as the runtime user and try a DDL:

   ```bash
   docker exec dixlase-dev-mysql mariadb -udixlase -pdixlase dixlase \
     -e "DROP TABLE dls_members"
   # → ERROR 1142 (42000): DROP command denied to user 'dixlase'@'%'
   ```

   Then verify migrations work as the migrator:

   ```bash
   docker exec dixlase-dev-app php artisan migrate --database=mysql_migrate
   # → migrations run as before
   ```

## How to enable (existing install)

The init hook does not re-fire on a data directory that already exists,
so you have to apply the privilege split manually. **This downs the DB
briefly**; do it during a planned maintenance window.

```bash
# 1. Take a backup first.
docker exec dixlase-dev-mysql mariadb-dump -uroot -p<root-pass> dixlase \
  > backup-pre-privilege-split.sql

# 2. Apply the split.
docker exec -i dixlase-dev-mysql mariadb -uroot -p<root-pass> <<'SQL'
-- Lock the existing app user down to DML only.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'dixlase'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, SHOW VIEW
  ON `dixlase`.* TO 'dixlase'@'%';

-- Create the migrator user.
CREATE USER 'dixlase_migrator'@'%' IDENTIFIED BY '<a-strong-random-password>';
GRANT ALL PRIVILEGES ON `dixlase`.* TO 'dixlase_migrator'@'%';

FLUSH PRIVILEGES;
SQL

# 3. Set DB_MIGRATE_USERNAME / DB_MIGRATE_PASSWORD in .env to match.

# 4. Re-cache config and verify (same DROP TABLE / migrate test as above).
docker exec dixlase-dev-app php artisan config:cache
```

## How to run migrations under the split

Always pass `--database=mysql_migrate` to migration commands:

```bash
php artisan migrate --database=mysql_migrate
php artisan migrate:rollback --database=mysql_migrate
```

Plugin / theme migrations triggered through `dls:plugin:install` /
`dls:theme:install` already use the configured connection; you only need
to set the connection at the `artisan migrate` boundary.

In deploy scripts, only the migration step should switch to the
migrator. The application boot, asset build, and cache warmup all stay
on the default DB user.

## How to roll back

If the split causes operational issues you can revert to single-user
mode at any time:

```bash
docker exec -i dixlase-dev-mysql mariadb -uroot -p<root-pass> <<'SQL'
GRANT ALL PRIVILEGES ON `dixlase`.* TO 'dixlase'@'%';
DROP USER IF EXISTS 'dixlase_migrator'@'%';
FLUSH PRIVILEGES;
SQL
```

Then unset `DB_MIGRATE_USERNAME` / `DB_MIGRATE_PASSWORD` in `.env`.

## Behaviour without the split

If `DB_MIGRATE_USERNAME` is unset, the `mysql_migrate` connection in
`config/database.php` falls back to `DB_USERNAME` / `DB_PASSWORD`. That
means existing installs keep working as before — the privilege split is
strictly opt-in, and enabling it requires both env vars and a fresh (or
manually re-graded) MySQL data directory.

## See also

- [2026-05-29 Brand MySQL DROP post-mortem](../incidents/2026-05-29-mysql-tables-dropped.md) — why this feature exists
- `config/database.php` — the `mysql_migrate` connection definition
- `dixlase-docker-installer` `mysql-initdb/01-permissions.sh` — the init-time hook for fresh data dirs
