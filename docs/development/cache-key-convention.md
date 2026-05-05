# Cache Key Naming Convention

Dixlase ships a small Builder helper (`App\Support\Cache\CacheKey`) so that core, plugins and themes share one consistent cache-key shape. Following this convention prevents key collisions between plugins, makes invalidation responsibilities clear, and lines up with the multisite roadmap.

## Format

```
dixlase:{scope}:{owner}:{domain}:{key}
```

Site-scoped (composite) form:

```
dixlase:site:{site_id}:{scope}:{owner}:{domain}:{key}
```

| Part | Meaning | Examples |
|---|---|---|
| `dixlase` | Project-wide prefix. Always literal. | `dixlase` |
| `scope` | Owning scope of the cache entry. | `core` / `plugin` / `theme` / `site` |
| `owner` | Identifier inside the scope. Plugin/theme slug, site id, or `core`. | `core` / `dixlase-pages` / `dixlase-onepage` / `1` |
| `domain` | Functional domain inside the owner. | `manifest` / `routes` / `view` / `query` / `permissions` |
| `key` | Individual key inside the domain. | `member-42` / `posts-list-page-1` / `v1` |

`core` scope drops the redundant owner and uses the 4-segment shape `dixlase:core:{domain}:{key}` because the owner is implied.

### Examples

| Use case | Key |
|---|---|
| Core route list | `dixlase:core:routes:list` |
| Plugin manifest cache (DixlasePages) | `dixlase:plugin:dixlase-pages:manifest:v1` |
| Theme view cache (DixlaseOnePage) | `dixlase:theme:dixlase-onepage:view:home` |
| Per-site nav cache | `dixlase:site:1:nav:public` |
| Per-site plugin cache | `dixlase:site:1:plugin:dixlase-pages:manifest:v1` |
| Per-site theme cache | `dixlase:site:1:theme:dixlase-onepage:view:home` |
| Per-site core cache | `dixlase:site:1:core:routes:list` |

## Tags

Cache tags follow a parallel format:

```
dixlase:tag:{scope}:{owner}
dixlase:tag:site:{site_id}:{scope}:{owner}
```

| Tag | Meaning |
|---|---|
| `dixlase:tag:plugin:dixlase-pages` | Flush every cache entry owned by the DixlasePages plugin. |
| `dixlase:tag:theme:dixlase-onepage` | Flush every cache entry owned by the DixlaseOnePage theme. |
| `dixlase:tag:site:1:plugin:dixlase-pages` | Flush per-site cache entries for site 1 owned by DixlasePages. |

> Tagged caches are only available on Redis / Memcached / array drivers. The
> `file` and `database` drivers do not support tags. Always guard tag use with
> `Cache::getStore() instanceof \Illuminate\Cache\TaggableStore`. The Builder
> only generates strings — it never enforces tag use.

## Builder Helper

Use `App\Support\Cache\CacheKey` (an `@api` class) instead of constructing keys by hand.

```php
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

// Core scope
$key = CacheKey::core('routes', 'list');
// dixlase:core:routes:list

// Plugin scope (slug from plugin.json)
$key = CacheKey::plugin('dixlase-pages', 'manifest', 'v1');
// dixlase:plugin:dixlase-pages:manifest:v1

// Theme scope (slug from theme.json)
$key = CacheKey::theme('dixlase-onepage', 'view', 'home');
// dixlase:theme:dixlase-onepage:view:home

// Site-scoped (composite) — fluent builder
$key = CacheKey::site(1)->plugin('dixlase-pages', 'manifest', 'v1');
// dixlase:site:1:plugin:dixlase-pages:manifest:v1

$key = CacheKey::site(1)->key('nav', 'public');
// dixlase:site:1:nav:public

// Tags
$tag      = CacheKey::tag('plugin', 'dixlase-pages');
$siteTag  = CacheKey::site(1)->tag('plugin', 'dixlase-pages');
```

## Conventions

- **Slug source**: pass the slug from `plugin.json` / `theme.json` directly. The
  Builder does not auto-resolve slugs, by design — it is a pure string builder
  with no I/O.
- **Site id**: pass the integer / string id resolved from `SiteContextInterface`
  when building site-scoped keys. Do not embed `site_id` inside `key` — let the
  Builder put it where it belongs.
- **Slashes / spaces in `key`**: avoid them. Stick to alphanumerics, hyphens,
  and dots. Cache drivers do not all sanitise the same way.
- **TTL**: out of scope for this document. Set TTLs in the calling code.
- **Migration**: do not bulk-rewrite existing `Cache::` call sites. Each plugin
  author migrates incrementally. Be aware that switching a key name produces a
  one-time cache miss for the affected entries.

## Multisite Posture

Builder methods on the bare `CacheKey` class produce **site-agnostic** keys.
That is correct for genuinely global state (e.g. a flag that depends on
`APP_ENV` only). For anything that varies per site — content lists, route
trees, permission caches, theme renders — wrap the call in
`CacheKey::site($id)->...` so the cache stays partitioned when v2+ enables
multiple visible sites.

In v0.1.0 there is exactly one site (`id=1`), so the Builder always works.
Plugin authors do not need to add multisite plumbing today — they just need
to use the site-scoped form for anything that is conceptually per-site.
