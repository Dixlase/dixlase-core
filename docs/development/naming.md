# Public Identifier Naming Conventions

> **Audience**: core contributors, plugin authors, theme authors, integrators issuing API keys.
>
> **Purpose**: Dixlase exposes several kinds of string identifiers (API scopes, permission keys, event names, webhook event types, audit log action types, plugin capabilities, …) that downstream consumers hard-code. Once a name is in the wild it cannot be safely renamed — see [PLUGIN-API.md](../../PLUGIN-API.md) for the compatibility policy. This document fixes the naming conventions for **new** identifiers so the public surface stays coherent as it grows.
>
> **Status**: Stable. Changes to this document follow the same semver policy as the plugin API surface.

## Why this matters

A renamed identifier silently breaks every consumer that hard-coded the old name. Because consumers include:

- **Plugins** referencing `permission` keys in `plugin.json`
- **Plugins** subscribing to events / webhooks by name
- **External systems** matching webhook deliveries by `event` field
- **Issued API keys** carrying frozen scope strings until revoked
- **Stored audit log rows** carrying fixed `action` strings

…we treat identifier names as **part of the public API**, not as implementation details. New identifiers must follow the conventions below; existing identifiers are grandfathered (see [Grandfathered exceptions](#grandfathered-exceptions)).

## Quick reference

| Identifier kind | Format | Example |
|---|---|---|
| [API scope](#api-scopes) | `<verb>:<resource>` | `read:content`, `write:members` |
| [Permission key (menu)](#permission-keys) | `<area>[.<subarea>].<action>` | `front.index`, `settings.base.admin` |
| [Event name](#event-names) | `dixlase.<category>.<action>` (past tense) | `dixlase.backup.completed` |
| [Webhook event type](#webhook-event-types) | same as event name | `dixlase.backup.completed` |
| [Audit log action](#audit-log-actions) | flat `<resource>_<action>` snake_case | `password_changed`, `login_failed` |
| [Plugin capability](#plugin-capabilities) | kebab-case feature label | `seo-meta`, `linkable` |
| [Plugin permission category](#plugin-permission-categories) | single lower-case word | `database`, `storage`, `members` |
| [Cache key](#cache-keys) | see [cache-key-convention.md](cache-key-convention.md) | `dixlase:core:routes:list` |
| [Config key (env)](#config-keys) | `SCREAMING_SNAKE_CASE` | `TRUSTED_PROXIES`, `AUDIT_LOG_RETENTION_DAYS` |
| [Config key (PHP path)](#config-keys) | `lower.dot.path` | `security.audit_log.retention_days` |

---

## API scopes

OAuth-style permission tokens carried by API keys. Stored frozen on the key, so renames invalidate every issued key.

**Format**: `<verb>:<resource>`

- `<verb>` is one of `read`, `write` (write implies read for the same resource).
- `<resource>` is the noun, lower_snake_case if multi-word, plural for collection resources.
- Constants live on `App\Models\ApiKey` as `SCOPE_<VERB>_<RESOURCE>`.

**Examples**

```php
ApiKey::SCOPE_READ_CONTENT       // 'read:content'
ApiKey::SCOPE_WRITE_TRANSLATIONS // 'write:translations'
ApiKey::SCOPE_READ_AUDIT_LOG     // 'read:audit_log'  (hypothetical)
```

**Future verbs**: if a stronger verb than `write` is needed (e.g. `delete`, `admin`), introduce it as a new top-level verb rather than overloading `write`.

---

## Permission keys

Hierarchical permissions backing `PermissionRegistry::getEffective()` and the admin role-permission UI. Plugins reference these in `plugin.json` and `permissions` declarations.

**Format**: `<area>[.<subarea>][.<deeper>].<action>`

- Lower_snake_case for each segment.
- The path mirrors the admin nav structure (see `config/admin.php`).
- The trailing segment is the action or sub-page (`index`, `edit`, `settings`, `password`, …).
- Multi-word actions use snake_case, not nested dots: `members.create_edit`, **not** `members.create.edit`.
- Plugin permission keys are namespaced under their slug: `<plugin_slug>.<area>.<action>`.

**Examples**

```
front.index
front.edit
front.settings
media.upload
members.create_edit
members.roles
settings.base.admin
settings.security.password
```

**Why snake_case in segments and not nested dots**: the dot in this scheme is reserved for the *menu hierarchy*. Using a dot inside an action would conflate "this is a sub-area" with "this is an action with a multi-word name", and we lose the ability to reason about depth.

---

## Event names

Names dispatched by `event(...)` and stored on `App\Events\DixlaseEvents` as constants.

**Format**: `dixlase.<category>[.<subcategory>].<action>`

- All lower-case, dot-separated.
- The `dixlase.` prefix is **mandatory** for all core- and plugin-fired events. Prefix-less names are reserved for Laravel framework internals.
- `<action>` is past tense for completed states (`created`, `installed`, `failed`) and present-progressive (`-ing`) for "before" states (`installing`, `creating`).
- Multi-word actions use further dots, **not** snake_case: `dixlase.url.slug.changed`, **not** `dixlase.url.slug_changed`.
- Plugin events are namespaced: `dixlase.<plugin_slug>.<category>.<action>` (e.g. `dixlase.dixlase_pages.page.published`).

**Lifecycle pairs**

When firing both a "before" and an "after" event, use this verb pairing:

| Stage | Verb form | Example |
|---|---|---|
| About to start | `-ing` | `dixlase.plugin.installing` |
| Completed successfully | past tense | `dixlase.plugin.installed` |
| Failed | `failed` suffix | `dixlase.plugin.installation.failed` |

**Examples**

```php
DixlaseEvents::BACKUP_COMPLETED  // 'dixlase.backup.completed'
DixlaseEvents::PLUGIN_INSTALLING // 'dixlase.plugin.installing'
DixlaseEvents::AUDIT_LOG_CREATED // 'dixlase.audit.log.created'
```

See [api-reference/events.md](api-reference/events.md) for the catalog of currently-defined events.

---

## Webhook event types

External webhook deliveries carry an `event` field whose value is the **same string** as the dispatched event name. There is no separate webhook namespace.

```json
{
  "event": "dixlase.backup.completed",
  "data": { ... },
  "timestamp": 1730000000
}
```

When designing a new event, consider whether external integrators will subscribe to it via webhook. If so, the payload must be JSON-serialisable and stable per the schema-version policy (see [api-reference/events.md](api-reference/events.md)).

---

## Audit log actions

Strings stored in `dls_audit_logs.action` and matched by SIEM / audit UI filters. Constants live on `App\Models\AuditLog` as `ACTION_<NAME>`.

**Format**: flat `<resource_or_event>` or `<resource>_<action>`, lower_snake_case.

- Audit log actions are **not** dot-separated. They are a flat keyspace optimised for SQL `LIKE` filtering and CSV export.
- Single concept = single token: `login`, `logout`.
- Resource + action: `password_changed`, `two_fa_enabled`, `passkey_revoked`.
- Outcome modifier as suffix: `login_failed`, `passkey_auth_failed`.

**Why a different style from event names**: audit actions are persisted database values that operators read and grep. Dot separators make SQL filtering awkward (`action LIKE 'login.%'` vs `action LIKE 'login_%'` — both work, but operators are used to underscore tokens in audit fields). Mixing styles in one column also breaks index locality.

**Examples**

```php
AuditLog::ACTION_LOGIN              // 'login'
AuditLog::ACTION_PASSWORD_CHANGED   // 'password_changed'
AuditLog::ACTION_TWO_FA_ENABLED     // 'two_fa_enabled'
AuditLog::ACTION_PASSKEY_REGISTERED // 'passkey_registered'
```

---

## Plugin capabilities

Feature flags declared in `plugin.json` `capabilities` array, used by core to discover plugin-provided features (SEO meta, links, etc.).

**Format**: kebab-case feature label (no namespace).

- Lower-case, hyphen-separated.
- Names describe the **feature contract** the plugin participates in, not the plugin itself.
- Defined by core; plugins opt in by listing the capability.
- New capability strings are introduced through core, not by individual plugins.

**Examples**

```json
{
  "capabilities": [
    "seo-meta",
    "linkable"
  ]
}
```

---

## Plugin permission categories

Top-level keys of the `permissions` object in `plugin.json`. They classify what kind of resource the plugin is requesting access to.

**Format**: single lower-case word.

- One word, no separators.
- Closed set defined by core: `database`, `storage`, `settings`, `members`, `mail`, `content`, `system`.
- Adding a new category is a core change, not a plugin change.

**Example**

```json
{
  "permissions": {
    "database": { "own_tables": true, "core_tables": [] },
    "storage":  { "own_directory": true, "public_uploads": false },
    "settings": { "read_core": true, "write_own": false }
  }
}
```

---

## Cache keys

Use `App\Support\Cache\CacheKey` rather than constructing strings by hand.

See the dedicated document: **[cache-key-convention.md](cache-key-convention.md)**.

Summary: `dixlase:{scope}:{owner}:{domain}:{key}` with site-scoped form `dixlase:site:{site_id}:{scope}:…`. The conventions in this document and the cache-key conventions are intentionally kept separate because cache keys have additional concerns (TTL, site scoping, tag invalidation).

---

## Config keys

Configuration values exist in two equivalent forms: the `.env` form (read by `env()`) and the PHP array path (read by `config()`).

**`.env` form**: `SCREAMING_SNAKE_CASE`

```
TRUSTED_PROXIES=10.0.0.0/8
AUDIT_LOG_RETENTION_DAYS=365
HSTS_MAX_AGE=300
```

**PHP path form**: `lower.dot.path`

```php
config('security.audit_log.retention_days')
config('trustedproxy.proxies')
```

**Pairing convention**: an `.env` key conventionally maps to a single `config()` path, declared in the relevant `config/<file>.php`:

```php
// config/security.php
return [
    'audit_log' => [
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],
];
```

---

## Grandfathered exceptions

The following identifiers exist in v0.1.0 and deviate from the conventions above. They are kept as-is for backward compatibility; any rename would be a breaking change.

| Identifier | Kind | Deviation | Status |
|---|---|---|---|
| `translation.model.retrieved` (et al.) | Event | Missing `dixlase.` prefix | Grandfathered. New translation events should use `dixlase.translation.<action>`. |
| `dixlase.url.slug_changed` | Event | Snake_case in action segment instead of further dots | Grandfathered. New equivalents would be `dixlase.url.slug.changed`. |
| `dixlase.url.admin_url_changed` | Event | Same as above | Grandfathered. |

The "new equivalents" listed above are **not** added as aliases — they would split the keyspace. The deviation is documented here only so it does not propagate by example.

---

## When to add to this document

Adding a new identifier kind:

1. Decide the naming format **before** the first PR that introduces the identifier.
2. Open a PR that updates this document first (or in the same commit).
3. Reference this section from the PR description so the reviewer can verify.

Adding a new identifier of an existing kind:

1. Follow the conventions in this document.
2. If a deviation is unavoidable (legacy integration, third-party schema, …), add a row to [Grandfathered exceptions](#grandfathered-exceptions) explaining why.

Renaming an existing identifier:

1. Almost never permitted — it is a breaking change for every downstream consumer.
2. If a rename is required (e.g. for security reasons), follow the deprecation policy in [PLUGIN-API.md](../../PLUGIN-API.md): ship both old and new names for one major version, then remove the old name.

---

## See also

- [PLUGIN-API.md](../../PLUGIN-API.md) — public API compatibility policy
- [api-reference/events.md](api-reference/events.md) — catalog of defined events
- [api-reference/webhooks.md](api-reference/webhooks.md) — webhook delivery contract
- [api-reference/versioning.md](api-reference/versioning.md) — REST API versioning policy
- [cache-key-convention.md](cache-key-convention.md) — cache key format
