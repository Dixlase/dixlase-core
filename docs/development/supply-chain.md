# Supply-Chain Defense Data Layer

> **Status (v0.1):** the version-history schema and retention policy are
> frozen for `^0.1`. The records here power supply-chain attack detection
> (signing-key changes, owner-id changes, file-integrity drift) and forensic
> investigation, so they must survive plugin / theme / core lifecycle events
> without silent data loss.

## Why this exists

A signed plugin or theme can be replaced in-place by an upstream repo that
silently swaps the publisher's key or the author identity. Without a tamper
log we cannot answer the basic forensic question after an incident:

> "When did the publisher of plugin X change, and who applied the update?"

The version-history tables exist to answer that question. They are append-only
in practice — there is no admin UI to edit or purge rows — and rows survive
their parent record's uninstall to preserve the audit trail.

## Tables

| Table                                | Purpose                                                   | Status     |
|--------------------------------------|-----------------------------------------------------------|------------|
| `dls_plugin_version_history`         | Per-plugin install / update / rollback timeline.          | v0.1.0     |
| `dls_theme_version_history`          | Per-theme install / update / rollback timeline.           | v0.1.0 (A-5-A1) |
| `dls_core_version_history`           | Per-core install / upgrade timeline.                      | v0.1.0 (A-5-A2) |

Each row captures:

- The subject identifier (plugin slug, theme slug, or "core").
- `old_*` and `new_*` snapshots of `version`, `signing_key_id`, `author_id`.
- Boolean change flags (`signing_key_changed`, `author_id_changed`) indexed
  for "show me everything where the owner changed".
- File-integrity counters (`files_changed_count`, `lines_added`,
  `lines_removed`).
- `installation_method` (`install` / `update` / `rollback`).
- `installed_from_url`, `applied_by_id` (member who applied), `applied_at`.

## Retention policy (frozen for `^0.1`)

**Decision:** version-history rows are keyed by the subject's **slug** (a
string column), not by foreign key to the parent table. Rows are **never
deleted on parent uninstall** and there is **no `ON DELETE CASCADE`** on the
subject column.

Concretely:

- `plugin_version_history.plugin_slug` (string) — preserved when the plugin
  row is deleted from `plugins`.
- `theme_version_history.theme_slug` (string) — same.
- `core_version_history` — never deleted; core is never "uninstalled" in
  practice, but the same append-only rule applies.

### Why no foreign keys

A foreign key with `ON DELETE CASCADE` would purge forensic data the
moment an attacker (or panicked operator) clicks "uninstall plugin X".
A foreign key with `ON DELETE RESTRICT` would block legitimate uninstalls
when history exists, which would push operators toward DB-level workarounds.

Storing the slug as a free string sidesteps both problems and keeps the
records useful when a plugin is uninstalled and later re-installed (same
slug, same supply-chain origin → continuous history).

### Operator implications

- A re-installed plugin with the same slug inherits the prior history.
  This is intentional — supply-chain timeline continuity is more valuable
  than starting from a clean slate.
- A re-installed plugin with the **same slug but a different `author_id` /
  `publisher_key_id`** will surface as a `signing_key_changed` /
  `author_id_changed` row at the next update. This is the same signal as
  if the plugin had been continuously installed and the upstream had been
  hijacked.
- To "really" forget a plugin (e.g. GDPR right-to-be-forgotten of an admin
  who installed it), the operator runs the Privacy data deletion flow on
  the `applied_by_id` column, which is opt-in and audit-logged. The slug
  itself is not personal data.

### What we explicitly will NOT do within `^0.1`

- Add `ON DELETE CASCADE` to any version-history table.
- Add a soft-delete / `uninstalled_at` column on version-history rows
  (an orphaned-row query against `plugins` covers the same need without
  schema churn).
- Provide an admin UI to purge selected history rows.

Loosening any of these post-release would be a breaking forensic policy
change and is therefore subject to the deprecation policy in
`PLUGIN-API.md`.

## See also

- `.backlog/supply-chain-defense-deferred.md` — Phase 1 design notes.
- `app/Models/PluginVersionHistory.php` — Eloquent model.
- `app/Services/Plugin/PluginHealthScorer::evaluateSupplyChainMetadata()`
  — health check for missing `author_id` / `publisher_key_id`.
- `docs/development/plugins/` — plugin-author guidance on declaring
  `author_id` and `publisher_key_id` in `plugin.json`.
