# Dixlase REST API Versioning Specification

## Overview

This document defines the versioning contract, response envelope, authentication model, and multisite/i18n boundaries for the Dixlase REST API. It is the canonical reference for both core developers and plugin authors.

The API is an architectural layer, not a feature: AI-operable flows, integration hubs, headless usage, mobile apps, and external integrations all depend on it. Decisions made here are intentionally conservative because they are hard to reverse after release.

## Version

- **Specification Version**: 1.0
- **Status**: Stable contract, partial implementation in v0.1.0
- **Implemented endpoints**: `GET /api/v1/health`
- **Reserved namespaces**: see Section 2

## 1. URL Versioning

### 1.1 Strategy

All API endpoints live under a versioned URL prefix:

```
/api/v1/...
```

A future `v2` API will live under `/api/v2/...` while `v1` remains available for at least one major version cycle.

This mirrors WordPress REST API (`/wp-json/wp/v2/`), GitHub API (`/v3/`, then header-based), and Stripe API (`/v1/`). The trade-off chosen here is **explicit, debuggable, cache-friendly URLs** over the cleaner `Accept: application/vnd.dixlase.v1+json` style.

### 1.2 i18n boundary

`/api/*` is **not** subject to the path-prefix locale routing (`/{locale}/...`) used by the front site. The API is a language-neutral control surface.

- `routes/front.php`'s `Route::fallback()` explicitly bypasses `/api/*` paths so unmatched API URLs do **not** get 302-redirected to `/{locale}/api/...`.
- `SetFrontLocale` and `SetAdminLocale` middleware do not run on API requests.
- API error messages are always in English (see Section 5).

If a future endpoint genuinely needs to return localized content (e.g. a published page rendered for a given locale), the **endpoint** is responsible for accepting an explicit `?locale=` query parameter or `Accept-Language` header and resolving content accordingly. The URL prefix never embeds the locale.

## 2. URL Namespace Reservation

The following namespaces under `/api/v1/` are reserved. Plugins **must not** register routes that collide with reserved paths.

| Path | Status | Owner | Notes |
|---|---|---|---|
| `/api/v1/health` | Implemented | Core | No auth, returns site/version metadata |
| `/api/v1/resources/{type}/{slug}` | Reserved | Future DixlaseApi plugin | Auto-discovered via `ApiResourceProviderInterface` |
| `/api/v1/privacy/...` | Reserved | Future Privacy API | Built on `PrivacyDataProviderInterface` aggregator |
| `/api/v1/{plugin-slug}/...` | Open to plugins | Plugin authors | Registered via `routes/api/v1.php` |

`{plugin-slug}` corresponds to the slug declared in `plugin.json` (kebab-case). Reserved top-level segments (`health`, `resources`, `privacy`) are off-limits for plugin slugs.

## 3. Response Envelope

All API responses use a uniform JSON envelope built on top of Laravel API Resources. The base class is `App\Http\Resources\BaseApiResource` (and `BaseApiCollection` for lists).

### 3.1 Single resource

```json
{
  "data": {
    "id": "01J9...",
    "type": "page",
    "attributes": { "title": "Hello" }
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  },
  "links": {
    "self": "https://example.com/api/v1/pages/01J9..."
  }
}
```

### 3.2 Collection

```json
{
  "data": [ ... ],
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z",
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 130,
      "last_page": 7
    }
  },
  "links": {
    "self": "https://example.com/api/v1/pages?page=1",
    "next": "https://example.com/api/v1/pages?page=2",
    "prev": null
  }
}
```

### 3.3 Error

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": {
      "title": ["The title field is required."]
    }
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  }
}
```

`code` is a stable, machine-readable string snake_case identifier. `message` is a human-readable English sentence. `details` is optional and shaped per error type (validation errors carry field-keyed arrays; other errors may omit it).

### 3.4 Naming distinction

`App\DTO\Api\ApiResourceDTO` and `ApiResourceCollection` (in `app/DTO/Api/`) are a **different layer** from `BaseApiResource`. They are content abstractions used by plugins implementing `ApiResourceProviderInterface` to hand data to core. `BaseApiResource` is the Laravel HTTP Resource base used to render outgoing JSON. Do not confuse the two.

## 4. HTTP Status Codes

| Code | Meaning | When |
|---|---|---|
| 200 | OK | Successful read |
| 201 | Created | Resource created |
| 204 | No Content | Successful delete |
| 400 | Bad Request | Malformed JSON, unparseable body |
| 401 | Unauthorized | Missing or invalid credentials |
| 403 | Forbidden | Authenticated but not allowed (scope, IP, site mismatch) |
| 404 | Not Found | Resource or route does not exist |
| 405 | Method Not Allowed | Wrong HTTP verb |
| 422 | Unprocessable Entity | Validation failure |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unhandled server-side failure |

3xx redirects must not be returned to API clients. The locale fallback in `routes/front.php` explicitly bypasses `/api/*`.

## 5. Localization Policy

### 5.1 Error messages are English-only

API error `message` fields are always in English. The API does not honor `Accept-Language` for system-generated messages. Clients that need localized human-readable strings should map the stable `code` to a client-side translation table.

This matches the convention of GitHub, Stripe, and most public REST APIs. It avoids:
- Inconsistent error catalogues that drift between locales
- Test brittleness (snapshot tests, contract tests)
- Edge cases where a server cannot resolve a locale (e.g. internal scheduled jobs)

### 5.2 Localized content

For endpoints that serve user-facing content (pages, posts, etc.), the **endpoint** is responsible for accepting:
- An explicit `?locale=en` / `?locale=ja` query parameter, or
- The `Accept-Language` header (with documented fallback rules)

The URL prefix never contains the locale. `/api/v1/pages/about?locale=ja` is correct; `/api/v1/ja/pages/about` is wrong.

## 6. Authentication

### 6.1 v0.1: API key (Bearer token)

```
Authorization: Bearer dxl_live_AbCdEf...
```

Keys are sha256-hashed at rest and carry: scope list, optional IP allowlist, optional expiration, environment tag (`live` / `test`), per-key rate-limit override.

### 6.2 v0.2 and later: Sanctum

Laravel Sanctum is anticipated to provide Personal Access Tokens and SPA cookie auth. Sanctum is not bundled in v0.1.0 because adding a dependency requires explicit user approval per project rules.

### 6.3 Multisite key binding

Every API key has a `site_id`:

| `site_id` | Type | Where it works | How it is created |
|---|---|---|---|
| `1`, `2`, ... (non-null) | Site key | Only for the bound site | Admin UI on that site (default) |
| `null` | Network key | Any site | CLI only: `dls:api:create-network-key` |

Network keys are powerful and dangerous. They:
- Cannot be created through any web UI
- Require explicit confirmation in the CLI flow
- Are recorded in `audit_logs` whenever they are used (`network_api_key_used` event)
- Should carry the minimum scopes necessary

Site keys are validated via the `BelongsToSite` trait's global scope: a request resolved to site A cannot authenticate with a key bound to site B.

## 7. Multisite Boundary

`ResolveSiteContext` middleware runs in the global stack and resolves the current site **before** API authentication.

- v0.1.0: Always selects the primary site (`is_primary=true`).
- v2+: Hostname-based / path-prefix-based resolution without changes to API callers.

API callers do not put the site in the URL; the API resolves it from the request host (and optionally header). Once `SiteContext::currentSiteId()` is set, every Eloquent query that uses `BelongsToSite` filters automatically.

## 8. Rate Limiting

- Default global ceiling: `api_rate_limit` setting (registered as `Global` scope in `ApiSettingDefinitions`)
- Per-key override: `api_keys.rate_limit` column
- Headers returned on every API response:
  - `X-RateLimit-Limit`
  - `X-RateLimit-Remaining`
  - `X-RateLimit-Reset` (Unix timestamp)
- 429 response includes `Retry-After` in seconds

A future revision may promote `api_rate_limit` to `Overridable` scope so per-site administrators can tighten or relax it.

## 9. Plugin API Route Convention

Plugins that expose API endpoints place them in:

```
plugins/{Name}/routes/api/v1.php
```

The core auto-loader wraps the file's contents in:

```php
Route::prefix('api/v1')
    ->middleware(\App\Http\Middleware\EnsurePluginActiveOnSite::class.':'.$slug)
    ->group(function () {
        require $apiV1RoutePath;
    });
```

Plugin authors therefore write only the slug-relative portion:

```php
// plugins/DixlaseAuthority/routes/api/v1.php
use Illuminate\Support\Facades\Route;
use Plugins\DixlaseAuthority\App\Http\Controllers\Api\PublicKeyController;

Route::prefix('authority')
    ->middleware('plugin.api.public')
    ->name('dixlase-authority::api.v1.')
    ->group(function () {
        Route::get('/keys', [PublicKeyController::class, 'index'])->name('keys.index');
    });
```

Result: `GET /api/v1/authority/keys`.

If the plugin is not active on the resolved site, `EnsurePluginActiveOnSite` returns a 404 JSON response before the controller runs.

The legacy convention of placing routes in `routes/api.php` (without the `v1.php` filename) remains supported for backward compatibility but is deprecated. The auto-loader will emit a log warning on load.

## 10. Deprecation Policy

- A major API version is supported for at least one full major version cycle after the next major ships (e.g. v1 lives until v3 ships).
- Individual endpoints inside a stable version may be deprecated with at least 6 months' notice via:
  - Release notes
  - `Sunset: <RFC 8594 date>` response header
  - `Deprecation: true` response header
- Breaking changes that cannot be deprecated cleanly trigger a major version bump rather than a silent change.

## 11. Health Check

The single endpoint implemented in v0.1.0 is the canonical example of the contract above:

```
GET /api/v1/health
```

No authentication. Always returns 200 unless the application is genuinely unable to bootstrap.

```json
{
  "data": {
    "status": "ok",
    "version": "0.1.0"
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  }
}
```

The `meta.site_id` is included so operators can verify which site the request resolved to in v2+ multi-site deployments. Site `slug`, `host`, and other identifying information are intentionally **not** exposed on this public endpoint.

## 12. Reference

### Code
- `routes/api.php` — v1 route group entry point
- `bootstrap/app.php` — `withRouting(api: ...)` and `withExceptions(...)` configuration
- `app/Http/Resources/BaseApiResource.php` — response envelope base class
- `app/Http/Resources/BaseApiCollection.php` — collection response envelope base class
- `app/Http/Controllers/Api/V1/HealthController.php` — health check
- `app/Http/Middleware/AuthenticateApiKey.php` — Bearer token auth
- `app/Http/Middleware/EnsurePluginActiveOnSite.php` — plugin activation gate
- `app/Models/ApiKey.php` — API key model with `BelongsToSite` trait
- `app/Console/Commands/Api/CreateNetworkApiKeyCommand.php` — CLI for network keys

### Documents
- [Multisite Architecture](../multisite.md)
- [URL Localization Decision](../i18n-url-localization.md)
- [Events API](events.md)
- [Webhooks](webhooks.md)
