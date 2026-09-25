# Upgrading Dixlase

Runbook for upgrading a Dixlase installation between released versions (e.g. v0.1.0 → v0.1.x, v0.1 → v0.2).

> Before v0.1.0 is released, schema churn is handled by `php artisan migrate:fresh` in development. This runbook applies once the first stable release is tagged and `database/migration-lock.json` is committed.

## 1. Prerequisites

- SSH or shell access to the production host with permission to run `docker exec` / `artisan` / `composer`
- Write access to the site's backup location
- A maintenance window during which request traffic can be suspended (`artisan down`)
- The target version's release notes and any plugin-specific upgrade notes

## 2. Migration Dependency Order

Plugin migrations must run in the order below because foreign keys reference tables owned by upstream plugins. Core always runs first.

1. **Core** (`database/migrations/`)
2. **DixlaseAuthority** — role/permission tables referenced by members-linked plugins
3. **DixlasePages** — page tree consumed by downstream content
4. **DixlaseLegal** — legal pages depend on Pages
5. **DixlaseMenus** — menu items may target Pages / Legal / Authority routes
6. **DixlaseInquiry** — form submissions; no downstream dependents
7. **DixlaseOfficialDocs** — document records; may reference Pages

Plugins without migrations (e.g. DixlaseSigner at the time of writing) are skipped. Run `php artisan dls:plugin:list` after deployment to confirm the enabled plugin set.

## 3. Backup

Run before touching anything on the production host.

### 3.1 Database dump

```bash
docker exec dixlase-mysql mariadb-dump \
  --single-transaction \
  --routines \
  --events \
  --triggers \
  -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  > "/backup/dixlase-$(date +%Y%m%d-%H%M%S).sql"
```

Verify the dump is non-empty and gzip it:

```bash
test -s /backup/dixlase-*.sql && gzip /backup/dixlase-*.sql
```

### 3.2 Filesystem snapshot

Back up `storage/`, `public/uploads/`, `.env`, any site-specific files under `custom/`, and the current `database/migration-lock.json`.

```bash
tar czf "/backup/dixlase-files-$(date +%Y%m%d-%H%M%S).tar.gz" \
  .env \
  storage \
  public/uploads \
  custom \
  database/migration-lock.json
```

## 4. Upgrade Procedure

Run each step from the project root inside the Docker host.

### 4.1 Enter maintenance mode

```bash
docker exec dixlase-php php artisan down --render="errors::503"
```

Users receive a 503 page. Keep the window short.

### 4.2 Pull the new release

Depends on your deployment model. Typical options:

- Git-based: `git fetch && git checkout v0.1.x && git submodule update --init --recursive`
- Tarball: extract the release tarball over the site root, preserving `.env` and `storage/`

### 4.3 Install PHP dependencies

```bash
docker exec dixlase-php composer install --no-dev --optimize-autoloader --no-interaction
```

`--no-dev` excludes dev-only packages (Pint, Larastan, etc.) from the production image.

### 4.4 Verify migration immutability

```bash
docker exec dixlase-php php artisan dls:migration:lint
```

This compares the current migration files on disk against `database/migration-lock.json`. A passing lint confirms no committed migration has been edited or deleted since the last release.

**If this fails**, stop the upgrade and investigate: a locked migration has been modified, which breaks audit trail guarantees. Either revert the change or accept the drift by running `dls:migration:lint --lock` — but only after reviewing what changed.

### 4.4.1 Beta series only: reconcile renamed migrations

Until GA, core migrations are edited and renumbered in place (see the Migration
Editing Policy). A renumbered file looks new to `migrate`, which would then try to
create a table that already exists. Before running migrations on an existing site,
point the ledger at the new filenames:

```bash
docker exec dixlase-php php artisan dls:migration:resync --prune            # dry-run preview
docker exec dixlase-php php artisan dls:migration:resync --prune --confirm
```

It only rewrites rows of the `migrations` ledger tables; no data table is touched.
When it reports nothing to do, continue.

### 4.5 Run migrations in dependency order

```bash
# Core
docker exec dixlase-php php artisan migrate --force

# Plugins (in dependency order)
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseAuthority --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlasePages --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseLegal --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseMenus --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseInquiry --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseOfficialDocs --force

# Themes (if any)
docker exec dixlase-php php artisan dls:theme:migrate --force
```

`--force` is required in production (Laravel aborts otherwise). Skip any plugin that is not installed on this site.

### 4.5.1 Beta series only: retire leftover schema objects

Editing a migration in place changes what a fresh install creates, but an existing
site keeps whatever the old file created. `dls:schema:retire` drops the leftovers
that a later beta release no longer uses — for example the `webauthn_credentials`
table that `members_passkeys` replaced. It only touches objects listed in the
command itself:

```bash
docker exec dixlase-php php artisan dls:schema:retire            # dry-run preview
docker exec dixlase-php php artisan dls:schema:retire --confirm
```

Read the preview before confirming. When it warns that legacy passkey rows will be
deleted, the members concerned have to register their passkeys again (the rows are
not read any more, but the dry-run tells you who is affected). The command writes an
audit log entry and is safe to re-run; a second run reports `Nothing to retire`.

### 4.6 Backfill supply-chain metadata (first-time only)

If this is the first upgrade onto a release that introduced supply-chain
columns (`signing_key_id`, `author_id`, `authority_key_id`) — typically v0.1.0
for environments seeded against a pre-v0.1.0 development branch — populate
the columns from the on-disk `plugin.json` / `theme.json` files:

```bash
docker exec dixlase-php php artisan dls:plugin:backfill-supply-chain --dry-run
docker exec dixlase-php php artisan dls:plugin:backfill-supply-chain
```

The command is idempotent: it only writes to columns that are currently
`null`, so it is safe to re-run. On subsequent upgrades this step is a
no-op. Skip it if every plugin / theme was installed through the v0.1.0+
admin flow (those rows already have the columns populated). See
`docs/development/supply-chain.md` for the rationale.

### 4.7 Refresh caches

Caches must be rebuilt **inside the container** so paths match the container's filesystem, not the host's.

```bash
docker exec dixlase-php php artisan config:cache
docker exec dixlase-php php artisan route:cache
docker exec dixlase-php php artisan view:cache
docker exec dixlase-php php artisan event:cache
```

### 4.8 Rebuild frontend assets (if shipped via source)

If the release does not include pre-built assets:

```bash
docker exec dixlase-vite npm ci
docker exec dixlase-vite npm run build
```

### 4.9 Smoke check before opening traffic

```bash
# Status: should report zero pending migrations
docker exec dixlase-php php artisan migrate:status | tail -20

# Health ping (internal, pre-traffic)
docker exec dixlase-php php artisan dls:health:check || true
```

### 4.10 Exit maintenance mode

```bash
docker exec dixlase-php php artisan up
```

Verify the public site responds 200 and the admin login works.

## 5. Rollback

If any step after 4.3 fails and recovery is not possible forward, roll back.

### 5.1 Restore the database

```bash
gunzip -c /backup/dixlase-YYYYMMDD-HHMMSS.sql.gz | \
  docker exec -i dixlase-mysql mariadb -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
```

### 5.2 Restore files

```bash
tar xzf /backup/dixlase-files-YYYYMMDD-HHMMSS.tar.gz -C /srv/dixlase/
```

### 5.3 Check out the previous release

Git-based:

```bash
git checkout <previous-tag>
git submodule update --init --recursive
docker exec dixlase-php composer install --no-dev --optimize-autoloader --no-interaction
```

### 5.4 Re-run the cache refresh (step 4.6) and exit maintenance mode (step 4.9).

## 6. Staging Dry Run

The first time this runbook is executed, do it against a staging environment that mirrors production in data volume and plugin set. Record results below and commit the update.

| Step | Staging duration | Notes |
| --- | --- | --- |
| 3.1 Database dump | _fill on first staging run_ | |
| 3.2 Filesystem snapshot | _fill on first staging run_ | |
| 4.3 composer install | _fill on first staging run_ | |
| 4.4 dls:migration:lint | _fill on first staging run_ | |
| 4.5 Migrations (total) | _fill on first staging run_ | |
| 4.6 Cache refresh | _fill on first staging run_ | |
| 4.7 npm build | _fill on first staging run_ | |
| **End-to-end (down → up)** | _fill on first staging run_ | |

Troubleshooting notes collected during the staging run belong in [operations/emergency/](emergency/index.md).

## 7. See Also

- [Maintenance](maintenance/index.md) — routine DB cleanup
- [Emergency Response](emergency/index.md) — recovery when a live upgrade goes wrong
- Migration immutability policy: core `CLAUDE.md` → "マイグレーションの編集方針"
