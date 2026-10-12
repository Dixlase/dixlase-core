# Multisite Architecture

Dixlase ships v0.1.0 as a single visible site, but the entire data and service layer is multisite-aware underneath. This document describes the architecture so plugin and theme authors can write code that works for both v0.1.0 (single site) and v2+ (multiple sites) without changes.

## TL;DR

- Every per-site database table has a `site_id` column.
- A request-scoped `SiteContext` resolves the current site at the start of each request.
- Eloquent models that use the `BelongsToSite` trait are automatically scoped to the current site (Global Scope).
- Settings are split into three scopes: `Global`, `PerSite`, `Overridable`. `SettingResolver` picks the right table based on the registered scope.
- File storage goes through `SiteStorage` so each site has an isolated tree under `storage/app/private/sites/{site_id}/`.

If you build a plugin that follows the conventions below, it works on a single-site install today and on a multi-site install in v2 with no changes.

---

## Components

### `App\Models\Site`

The canonical per-site record. Columns: `id`, `slug`, `name`, `description`, `host`, `path_prefix`, `primary_locale`, `timezone`, `is_primary`, `is_active`, plus standard timestamps and soft delete.

In v0.1.0 there is exactly one row (`id=1`, slug `main`, `is_primary=true`).

### `App\Contracts\Site\SiteContextInterface`

Resolves the current site for the request. Public methods:

```php
public function currentSiteId(): int;
public function currentSite(): Site;
public function setCurrent(int $siteId): void;
public function isPluginActive(string $slug): bool;
public function isThemeActive(string $slug): bool;
public function connection(): \Illuminate\Database\ConnectionInterface;
```

Bound as a singleton. Resolved at request start by `App\Http\Middleware\ResolveSiteContext`. Plugins can use the `App\Facades\SiteContext` facade or inject the interface.

Core gates every route a plugin contributes on `isPluginActive()` for the resolved site, through `App\Http\Middleware\EnsurePluginActiveOnSite`: `routes/api/v1.php` and the deprecated `routes/api.php` answer with the 404 JSON envelope, `routes/web.php` with the site's HTML 404 page. A plugin enabled globally but not active on the current site therefore exposes none of its front routes there (#494). The activation row for the primary site is written when the plugin is enabled through `plugin:enable` or the admin panel (the `Plugin` model's `saved()` hook, also when a plugin is created already enabled); migration `0001_01_01_000053_backfill_primary_site_plugin_activations` adds the row for enabled plugins on existing sites that lack one, without changing existing rows.

```php
use App\Facades\SiteContext;

$siteId = SiteContext::currentSiteId();
$site = SiteContext::currentSite();
```

### `App\Models\Traits\BelongsToSite`

Apply this trait to any Eloquent model whose table has a `site_id` column. The trait:

1. Adds a `site()` `belongsTo` relation
2. Registers a Global Scope that filters every query by the current site
3. Auto-populates `site_id` on creation with the current site

```php
use App\Models\Traits\BelongsToSite;

class Post extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'title', 'content'];
}

// Now:
Post::all();              // returns only the current site's posts
Post::create([...]);      // automatically tagged with current site_id
Post::allSites()->get();  // bypass scope (network admin only)
Post::forSite(2)->get();  // explicit site
```

### Settings: 3-mode scope model

Every setting key is registered with one of three scopes:

| Scope | Storage | Use case |
|-------|---------|----------|
| `Global` | `global_settings` only | Network-wide policy: license keys, default theme, security policies |
| `PerSite` | `site_settings` only (filtered by `site_id`) | Site-specific values: site name, maintenance message, OGP image |
| `Overridable` | Either: `site_settings` row overrides `global_settings` row | Network default with per-site override: SMTP, default locale |

Plugins register their settings via a `SettingDefinitionRegistry` call, typically in their service provider's `boot()`:

```php
use App\Enums\SettingScope;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;

public function boot(SettingDefinitionRegistry $registry): void
{
    $registry->register(new SettingDefinition(
        name: 'myplugin_api_endpoint',
        scope: SettingScope::Overridable,
        default: 'https://api.example.com',
        type: 'string',
    ));
}
```

Then read/write through `SettingResolver`:

```php
use App\Services\Site\SettingResolver;

$resolver = app(SettingResolver::class);

// Read with scope-aware fallback (site -> global -> default)
$endpoint = $resolver->get('myplugin_api_endpoint');

// Write to global (network default)
$resolver->setGlobal('myplugin_api_endpoint', 'https://prod.api.example.com');

// Write to current site (per-site override)
$resolver->setForSite('myplugin_api_endpoint', 'https://eu.api.example.com', $siteId);
```

Unregistered keys raise `UnknownSettingException`. Register first, then access.

#### Strict-mode policy (frozen for `^0.1`)

`SettingResolver::get()` is **strict by design**: an unregistered key raises
`UnknownSettingException` rather than silently returning `null` / `$default`.
This is part of the Plugin API contract within `^0.1` and will not be loosened
in a patch release. The rationale:

- **Catches typos at the call site.** `site_namr` instead of `site_name` fails
  immediately rather than masking a stale `null` for the lifetime of the
  request.
- **Prevents accidental key shadowing.** A plugin can never write to a
  near-name of a core key by accident; the registry mediates ownership.
- **Audit-friendly.** The list of canonical setting keys is the registry,
  not "whatever showed up in `site_settings`".
- **Easier to loosen later than tighten.** Plugin authors who depend on
  strict behaviour today would be broken by a later switch to lenient
  fallback; the reverse migration (lenient → strict) is the one that
  silently rots data, so we lock in strict from v0.1.0.

If you need to probe whether a setting exists without raising, use
`SettingDefinitionRegistry::has($key)` first:

```php
if (app(SettingDefinitionRegistry::class)->has($key)) {
    $value = $resolver->get($key);
}
```

Plugin and theme authors must register every setting they read or write in a
service provider (typically `boot()`), before the first request that touches
the value.

### File storage

`App\Services\Site\SiteStorage` returns Filesystem instances rooted at the multisite-aware tree:

```
storage/app/
├── public/
│   └── sites/{site_id}/      <- SiteStorage::public()
├── private/
│   ├── global/                <- SiteStorage::global()
│   └── sites/{site_id}/       <- SiteStorage::private()
└── temp/
```

```php
use App\Services\Site\SiteStorage;

SiteStorage::private()->put('reports/q4.csv', $csv);  // current site
SiteStorage::global()->put('plugin-vault/foo.zip', $zip);  // network-wide
SiteStorage::public()->put('uploads/image.jpg', $bytes);  // current site, public
```

Existing code that uses `Storage::disk('public')` or `storage_path('app/...')` continues to work. `SiteStorage` is the forward-looking API for new per-site code.

---

## Plugin / theme authoring guidance

Follow these conventions and your code works on any number of sites:

1. **Use `BelongsToSite` on per-site models** — The trait handles `site_id` injection and scoping. Add `site_id` to the table migration and the model's `$fillable`.

2. **Register settings up front** — Don't call `SiteSetting::getValue('foo')` for an unregistered key. Define every setting in your service provider's `boot()` so admins (and the resolver) know its scope.

3. **Read settings via the appropriate API:**
   - Plugin code: `app(SettingResolver::class)->get('your_key')`
   - Legacy callsites: `SiteSetting::getValue('your_key')` (still works; routes through the resolver internally)

4. **Use `SiteStorage` for new file paths** — Don't hardcode `storage_path('app/private/myplugin/...')`. Use `SiteStorage::private()` so each site keeps its own files.

5. **Don't issue cross-site queries by default** — Use `BelongsToSite` and let the scope filter. If you genuinely need cross-site (network admin reports), use the `allSites()` or `forSite($id)` scopes explicitly.

6. **Don't assume v0.1.0 single-site** — Even though only one site exists today, write code that handles multiple sites cleanly. The whole point of the foundation is that the v2 transition costs you nothing.

---

## Database strategy

v0.1.0 uses a **shared database with `site_id` columns** (logical isolation). All sites live in one MySQL database; queries filter by `site_id`.

This works seamlessly with all Laravel features (relations, transactions, queues, eager loading). The trade-off is logical isolation only, not physical.

A future option for v2+ is a hybrid where specific sites can opt into a dedicated database via `Site.db_connection`. Most sites stay on the default DB; tenants with stricter compliance requirements get physical isolation. The `SiteContext::connection()` method exists today so this transition is non-breaking when it lands.

See `.backlog/multisite-database-strategy.md` for the full trade-off discussion.

---

## What v0.1.0 doesn't ship

These are explicit non-goals for v0.1.0 and land in v2+:

- Network admin UI (site list / create / switch dropdown)
- Hostname-based site resolution (`acme.example.com` → site X)
- Path-prefix-based site resolution (`/blog` → site Y)
- DB-per-site routing
- Per-site theme activation UI (the data model exists; the UI doesn't)

The middleware `ResolveSiteContext` always selects the primary site in v0.1.0; v2 swaps the resolution logic without touching callers.

---

## Reference

- `App\Models\Site` — Site model
- `App\Contracts\Site\SiteContextInterface` — Site context contract
- `App\Facades\SiteContext` — Convenience facade
- `App\Models\Traits\BelongsToSite` — Per-site model trait
- `App\Enums\SettingScope` — Global / PerSite / Overridable
- `App\Services\Site\SettingDefinition` — Setting definition value object
- `App\Services\Site\SettingDefinitionRegistry` — Central setting registry
- `App\Services\Site\SettingResolver` — Scope-aware setting reader/writer
- `App\Services\Site\SiteStorage` — Site-aware filesystem helper
- `App\Services\Site\Exceptions\UnknownSettingException` — Raised for unregistered keys
- `App\Models\SitePluginActivation`, `App\Models\SiteThemeActivation` — Per-site plugin/theme activation rows
- `App\Models\GlobalSetting` — Network-wide setting model
- `PLUGIN-API.md` — All public Plugin API entries
