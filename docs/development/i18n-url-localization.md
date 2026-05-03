# URL Localization

Dixlase ships the **infrastructure** for path-prefix locale URLs (`/ja/about`, `/en/about`) but does **not activate** that routing in v0.1.0. Front URLs serve their content directly without a locale prefix; the admin UI (`/admin/...`) is unaffected.

The future multilingual plugin (DixlaseI18n) opts into locale URL routing by attaching the provided middleware to a `Route::prefix('{locale}')` group and registering its own `Route::fallback()` redirect. This document describes the locked-in design contract so that plugin can plug in without core changes.

## TL;DR

### v0.1.0 (default, no plugin)

- **Front URLs**: served at their declared paths (`/`, `/about`, `/posts/{slug}`) with no `/{locale}/` prefix
- **Admin URLs**: `/admin/<path>` — no locale prefix
- **`/` does not auto-redirect** to `/ja/` or `/en/`. Visitors stay where they were.
- **Locale-less unknown URLs** (e.g. `/about` with no route): standard Laravel 404
- **Admin locale resolution** (`SetAdminLocale`): `member.locale` > `Site.primary_locale` > Accept-Language > `en`
- **Supported locales**: `ja`, `en` only

### When the multilingual plugin is enabled (future)

- **Front URLs**: `/{locale}/<path>` (e.g. `/ja/about`, `/en/posts`)
- **Locale-less front URL** (e.g. `/about`): 302 redirect to `/{resolved_locale}/about`
- **Front locale resolution** (`SetFrontLocale`): URL > Cookie `dixlase_locale` > Accept-Language > `Site.primary_locale` > `en`
- **Untranslated content**: 302 redirect to `/{Site.primary_locale}/<path>` via `MissingTranslationHandler` contract (overridable by plugins)
- The plugin owns the redirect toggle. Operators that don't want auto-redirect can leave the plugin disabled or its setting off.

## Why path prefix (strategy A)

The candidates considered were:

| Strategy | URL example | Pros | Cons |
|---|---|---|---|
| **A. Path prefix** | `/ja/about` | SEO-friendly, single SSL cert, sharable | URL slightly longer |
| B. Subdomain | `ja.example.com` | Full isolation, separate CDN possible | DNS/SSL per locale |
| C. TLD | `example.jp` | Strong country targeting | Highest operational cost |

Strategy A is the Laravel community default, costs nothing operationally, and works for the typical CMS use case. Sites that need B or C can layer them on via plugins/themes once that demand emerges.

## Locale resolution

### Front-end (`SetFrontLocale` middleware)

Resolution order, first hit wins:

1. **URL prefix** — `/ja/...` → `ja`
2. **Cookie `dixlase_locale`** — written only when the user explicitly switches via the language switcher endpoint
3. **`Accept-Language` header** — first supported locale found in the ordered list
4. **`Site.primary_locale`** — the canonical per-site default, set in `/admin/settings/base/site`
5. **`config('app.fallback_locale')`** — `en` by default

The resolved locale is applied via `App::setLocale()` and registered as a `URL::defaults(['locale' => $resolved])` so subsequent `route()` calls preserve it automatically.

### Admin (`SetAdminLocale` middleware)

Admin pages have no locale prefix in the URL. The middleware resolves locale in this order:

1. **`member.locale`** — the operator's profile setting
2. **`Site.primary_locale`** — when the operator hasn't set a profile locale
3. **`Accept-Language`** — for unauthenticated screens (login, password reset)
4. **`config('app.fallback_locale')`** — `en`

### Cookie write policy

The `dixlase_locale` cookie is written **only** by the language switcher endpoint (`POST /locale/switch`), never by `SetFrontLocale`. This means:

- A user who lands on `/ja/about` once does not get pinned to Japanese for subsequent locale-less visits.
- Once the user explicitly chooses a language via the switcher, that choice persists for one year.

## URL strategy details

### Locale-less URLs are redirected

Visiting `/about` directly (no locale prefix) is treated as ambiguous. The catch-all route resolves the target locale via `Site.primary_locale` and issues a 302 redirect to `/{locale}/about`. We use 302 (not 301) so the strategy can change in v2 without poisoning caches.

### Untranslated pages

When a route exists but no translation is available for the requested locale, the `MissingTranslationHandler` contract decides what to do. The default core implementation redirects 302 to `/{Site.primary_locale}/<path>`.

Plugins (e.g., DixlaseI18n, DixlaseRedirects) can swap the binding to change behaviour:

```php
// In a plugin's service provider
$this->app->bind(
    \App\Contracts\I18n\MissingTranslationHandler::class,
    \Plugins\DixlaseI18n\Services\CustomMissingTranslationHandler::class,
);
```

Note: this only fires when **a route is matched but content is missing** for the current locale. URLs that don't match any route still return a regular 404.

### Routes that stay locale-neutral (even with the plugin enabled)

These routes never carry a locale prefix:

- `/assets/{type}/{file}` — theme/admin/plugin static assets (cache-key-stable across locales)
- `/csp-report` — CSP violation report endpoint
- `/front/custom-script.js`, `/front/custom-style.css` — page custom JS/CSS
- `/locale/switch` — language switcher control endpoint
- `/install/...` — installer (uses `Accept-Language` for UI)
- `/api/v1/...` — JSON APIs (clients send `Accept-Language` if needed)
- `/admin/...` — admin UI

## Plugin / theme integration

### v0.1.0 (default)

Plugin web routes loaded by `PluginHelper::loadEnabledWebRoutes()` mount at their declared paths with no locale prefix:

```php
// In a plugin's routes/web.php
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');
// Reachable as /posts/hello — no /{locale}/ prefix
```

### When the multilingual plugin is enabled

The multilingual plugin is expected to wrap the front routes (and its own routes) inside a `Route::prefix('{locale}')` group with the `SetFrontLocale` middleware attached. Once that wrapping is in place, the same plugin code becomes reachable under both `/ja/posts/hello` and `/en/posts/hello`, and `route('posts.show', ['slug' => 'hello'])` automatically picks up the current locale via `URL::defaults`.

### Generating locale-aware URLs

```php
// Auto-uses the current locale
route('posts.show', ['slug' => 'hello']);

// Force a specific locale
route('posts.show', ['locale' => 'ja', 'slug' => 'hello']);

// Get the same URL but in a different locale (e.g., for a language switcher link)
\App\Helpers\LocaleHelper::switchLocaleUrl('ja');
```

### `LocalizedUrlProvider` contract

Plugins that surface multi-language content can implement `App\Contracts\I18n\LocalizedUrlProvider` to expose alternate-language URLs for the current request. DixlaseSEO consumes this contract to emit `<link rel="alternate" hreflang="...">` tags.

```php
interface LocalizedUrlProvider
{
    /** @return array<string, string>  e.g. ['ja' => '/ja/posts/hello', 'en' => '/en/posts/hello'] */
    public function getAlternateUrls(\Illuminate\Http\Request $request): array;
}
```

## Site.primary_locale

The canonical "site default language" lives on the `sites.primary_locale` column. Read it via `SiteContext`:

```php
use App\Facades\SiteContext;

$default = SiteContext::currentSite()->primary_locale;
```

The legacy `'locale'` key in `SiteSetting` shadows this column for backward compatibility (`CoreSettingDefinitions.php`), but new code should read `Site.primary_locale` directly. The shadow may be deprecated in a future minor release.

The admin UI at `/admin/settings/base/site` writes `Site.primary_locale`, the legacy `SiteSetting('locale')` shadow, and `.env` (`APP_LOCALE`, `APP_FALLBACK_LOCALE`) atomically.

## Multisite integration

In v0.1.0, only the primary site exists, so URLs are simply `/{locale}/<path>`. The `Site.path_prefix` column (used by v2 to identify sites by URL prefix, e.g. `/blog` → site_id 2) is `null` for the primary site, so there is no collision.

When v2 introduces multi-site path-prefix routing, the chosen URL ordering is:

```
/{locale}/{path_prefix}/{path}     ← strategy: locale outermost
```

Locale resolution happens before site resolution: `SetFrontLocale` strips the locale prefix, then `ResolveSiteContext` matches the remainder against `sites.path_prefix`. Sites that need full isolation can use the subdomain pattern instead (`acme.example.com/ja/...`), in which case locale and site are fully orthogonal.

See `docs/development/multisite.md` for the full multisite architecture.

## What v0.1.0 doesn't ship

These are explicit non-goals for v0.1.0 and land in v2 or in a multilingual plugin:

- Translated CMS content (`Pages`, `Menus`, `Inquiry` translations)
- Translation editor UI / translation memory / glossary
- `<link rel="alternate" hreflang>` tag generation (lives in DixlaseSEO)
- Language switcher Blade component (lives in themes or DixlaseI18n)
- Carbon locale auto-switching beyond defaults
- Number/currency formatting localization
- RTL CSS / logical properties
- Dynamic locale list (de, fr, zh, ...) — `ja|en` only in v0.1.0

The contracts and middleware infrastructure are in core so these features can be added without changing URL layout.

## Reference

- `App\Helpers\LocaleHelper` — locale list, helpers, switch URL builder
- `App\Http\Middleware\SetFrontLocale` — front-end locale resolution
- `App\Http\Middleware\SetAdminLocale` — admin locale resolution
- `App\Contracts\I18n\LocalizedUrlProvider` — alternate URL exposure (for SEO / hreflang)
- `App\Contracts\I18n\MissingTranslationHandler` — untranslated-content fallback policy
- `App\Services\I18n\DefaultMissingTranslationHandler` — core default implementation
- `App\Models\Site::primary_locale` — canonical per-site default locale
- `docs/development/multisite.md` — multisite architecture
- `PLUGIN-API.md` — public Plugin API entries
