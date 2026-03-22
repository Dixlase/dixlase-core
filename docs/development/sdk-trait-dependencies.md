# SDK Trait Dependencies Analysis

This document records the core Trait dependency analysis for future `dixlase/plugin-sdk` package extraction.

## Overview

| Trait | Core Dependencies | Extractability | Priority |
|-------|------------------|----------------|----------|
| ConfigLoaderTrait | None | Easy | 1st |
| ThemeLoaderTrait | Theme model, DB, Schema | Medium-Hard | 3rd |
| PluginLoaderTrait | Plugin model, Schema, ConfigLoaderTrait | Hard | 2nd |

## ConfigLoaderTrait

**File:** `app/Traits/ConfigLoaderTrait.php`

**Dependencies:** Zero external dependencies. Uses only `config()` helper and standard PHP functions.

**Capabilities:**
- `loadConfigFiles()` — reads PHP config files from a directory
- `reorderAllConfig()` — reorders config arrays using `_insert_before` / `_insert_after` markers
- Array manipulation helpers (`arrayInsertBeforeKey`, `arrayInsertAfterKey`)

**SDK Extraction:** Can be extracted as-is with zero modifications.

## PluginLoaderTrait

**File:** `app/Traits/PluginLoaderTrait.php`

**Dependencies:**
- `App\Models\Plugin` — queries the `plugins` table for enabled plugins
- `App\Helpers\AdminHelper::mergeAdminNavigation()` — merges plugin navigation into admin menu
- `ConfigLoaderTrait` — used internally for config loading
- Facades: `Schema` (table existence checks), `Route` (plugin route loading), `View` (view namespace registration), `Lang` (translation loading)
- Config keys: `app.file_types`

**SDK Extraction Challenges:**
- Deep coupling to the `Plugin` Eloquent model
- Direct dependency on `AdminHelper` for navigation merging
- `Schema::hasTable('plugins')` check ties it to a specific DB schema
- Plugin route registration requires Laravel's `Route` facade with specific middleware groups

**Recommended Approach:**
1. Extract a `PluginRepositoryInterface` to abstract model queries
2. Extract an `AdminNavigationManagerInterface` to abstract `AdminHelper` calls
3. Move `Schema::hasTable()` checks behind the repository interface
4. Keep route/view/lang registration as Laravel-specific adapter code

## ThemeLoaderTrait

**File:** `app/Traits/ThemeLoaderTrait.php`

**Dependencies:**
- `App\Models\Theme` — queries the `themes` table for enabled theme
- Facades: `DB` (direct query to `theme_settings` table), `Schema` (table existence checks), `View` (view namespace registration)
- Config keys: `themes.default_theme`

**SDK Extraction Challenges:**
- Direct `DB::table('theme_settings')` queries bypass the Eloquent layer
- `Schema::hasTable()` checks for both `theme_settings` and `themes` tables
- Coupling to specific table structure and key naming (`enabled_theme_id`)

**Recommended Approach:**
1. Extract a `ThemeRepositoryInterface` to abstract DB queries
2. Move table existence checks behind the repository
3. View namespace registration can remain as Laravel-specific adapter code

## Extraction Strategy

### Phase 1: Low-Hanging Fruit
- Extract `ConfigLoaderTrait` into the SDK package directly

### Phase 2: Interface Abstraction (Completed)

SDK interfaces and core implementations have been created:

| Interface (`@api`) | Implementation (`@internal`) | Purpose |
|---------------------|------------------------------|---------|
| `App\Contracts\Repositories\PluginRepositoryInterface` | `App\Repositories\PluginRepository` | Query enabled plugins via `EnabledPluginRecord` DTO |
| `App\Contracts\Repositories\ThemeRepositoryInterface` | `App\Repositories\ThemeRepository` | Get enabled theme ID and directory |
| `App\Contracts\Admin\AdminNavigationManagerInterface` | `App\Services\Admin\AdminNavigationManager` | Merge plugin navigation config |

Supporting DTO:
- `App\DTO\Plugin\EnabledPluginRecord` (`@api`) — immutable value object with `name`, `directory`, `slug`

All bindings registered in `App\Providers\RepositoryServiceProvider` via `bind()`.

Key design decisions:
- `EnabledPluginRecord` DTO decouples consumers from the `Plugin` Eloquent model
- Table existence checks (`Schema::hasTable()`) are encapsulated inside repository implementations
- `AdminNavigationManager` supports both new (`config/admin/navigation.php`) and legacy (`admin.php` nav key) structures
- All implementations are stateless — `bind()` used instead of `singleton()`

### Phase 3: Trait Refactoring (Completed)

Both `PluginLoaderTrait` and `ThemeLoaderTrait` have been refactored to depend on interfaces:

**PluginLoaderTrait changes:**
- Removed: `App\Models\Plugin`, `Illuminate\Support\Facades\Schema`
- Added: `PluginRepositoryInterface`, `AdminNavigationManagerInterface`
- `loadEnabledPlugins()` now uses `resolvePluginRepository()->getEnabled()` (returns `Collection<EnabledPluginRecord>`)
- `mergeAdminNavigationFile()` and `mergeAdminNavConfig()` delegate to `AdminNavigationManagerInterface`
- `insertOrderedConfig()` removed (logic moved to `AdminNavigationManager`)
- ~~`mergeAdminNavigation()` retained (legacy `admin.nav` path, still used by 4 plugins)~~ — removed in Phase 4

**ThemeLoaderTrait changes:**
- Removed: `App\Models\Theme`, `Illuminate\Support\Facades\DB`, `Illuminate\Support\Facades\Schema`
- Added: `ThemeRepositoryInterface`
- `getEnabledTheme()` delegates to `resolveThemeRepository()->getEnabledThemeId()`
- `getEnabledThemeDirectory()` delegates to `resolveThemeRepository()->getEnabledThemeDirectory()`

**DI pattern:** Lazy resolution via `app()` helper to support both ServiceProvider (`$this->app`) and Controller contexts.

All public method signatures remain unchanged — zero breaking changes for consuming classes.

### Phase 4: Legacy Navigation Removal & Contract Model References (Completed)

**Legacy `mergeAdminNavigation()` removed from `PluginLoaderTrait`:**

The `mergeAdminNavigation()` method was the last direct reference to `\App\Helpers\AdminHelper` in `PluginLoaderTrait`. It used the legacy `admin.nav` config path. All 6 plugin callers have been migrated:

| Plugin | Action |
|--------|--------|
| DixlaseInquiry | Removed manual call (auto-load via `config/admin/navigation.php`) |
| DixlasePages | Removed manual call (auto-load via `config/admin/navigation.php`) |
| DixlaseDocs | Removed manual call (auto-load via `config/admin/navigation.php`) |
| DixlaseMultilingual | Created `config/admin/navigation.php`, deleted `config/admin.php`, removed manual call |
| DixlaseUsers | Removed conditional fallback (auto-load via `config/admin/navigation.php`) |
| DixlaseMenus | Removed manual call (auto-load via `config/admin/navigation.php`) |

Navigation loading is now fully handled by `loadPluginConfigs()` → `mergeAdminNavigationFile()` (auto-load path).

**Note:** `DixlaseOnePage` still calls `AdminHelper::mergeAdminNavigation()` directly (not via the trait). This is a separate code path — the theme doesn't use `ThemeLoaderTrait` for navigation. The `AdminHelper` static method remains available; theme migration is out of scope for this phase.

**`PluginLoaderTrait` is now free of all `\App\` concrete class dependencies:**
- Uses `PluginRepositoryInterface` (not `App\Models\Plugin`)
- Uses `AdminNavigationManagerInterface` (not `App\Helpers\AdminHelper`)
- Only depends on: Laravel facades (`Route`, `View`, `Lang`), `ConfigLoaderTrait`, and the two interfaces above

**Contract `App\Models\*` type references — `@api` annotation added:**

Four models referenced by `@api` Contracts now carry `@api` annotations, marking them as stable API for the SDK package:

| Model | Contract that references it |
|-------|-----------------------------|
| `App\Models\BaseSetting` | `BaseSettingRepositoryInterface` |
| `App\Models\FrontSetting` | `FrontSettingRepositoryInterface` |
| `App\Models\Media` | `MediaRepositoryInterface` |
| `App\Models\Member` | `TwoFaPasskeyServiceInterface` |

This approach keeps Contract signatures unchanged (no DTO wrapping) while declaring the models as host-application-provided dependencies for the SDK package.

### Phase 5a: Theme Legacy Navigation Migration (Completed)

**DixlaseOnePage migrated from `AdminHelper::mergeAdminNavigation()` to auto-load path:**

The theme was the last caller of `AdminHelper::mergeAdminNavigation()` outside of AdminHelper itself.

| Change | Detail |
|--------|--------|
| New file | `themes/DixlaseOnePage/config/admin/navigation.php` (new structure) |
| Removed | `AdminHelper::mergeAdminNavigation()` call from `DixlaseOnePageServiceProvider::register()` |
| Removed | `use App\Helpers\AdminHelper;` import from `DixlaseOnePageServiceProvider` |
| Updated | `ThemeServiceProvider::loadThemeConfigs()` — now auto-detects `config/admin/navigation.php` and merges via `AdminNavigationManagerInterface` |
| Cleaned | `themes/DixlaseOnePage/config/admin.php` — removed `nav` key (now empty) |

Theme navigation auto-loading now mirrors the plugin pattern: `ThemeServiceProvider` checks for `config/admin/navigation.php` in the theme directory and delegates to `AdminNavigationManagerInterface::mergeNavigationFile()`.

### Phase 5b: Plugin Concrete Dependency Audit (Completed)

Audited all `App\` class imports across 13 plugins. Out of 12 target classes:

- **11 already had `@api`**: `PluginHelper`, `CaptchaHelper`, `ConfigHelper`, `MemberRole`, `ContentEditorType`, `ContentStatus`, `ContentStorageType`, `AppearanceMode`, `AuthContextRegistryService`, `BaseSetting`, `Media`
- **1 added**: `SecuritySetting` — used by DixlaseBlog and DixlaseUsers in route files. Added `@api` alongside existing `@deprecated` notice.

All core classes referenced by plugins now carry the `@api` annotation, ensuring SDK package consumers can depend on these as stable host-application APIs.

### Phase 5c: TwoFaInterface Enum Inline Reference Removal (Completed)

`TwoFaInterface::getTwoFaMode()` had a hardcoded inline reference to `\App\Enums\AuthenticationMode`:

```php
// Before
public function getTwoFaMode(): \App\Enums\AuthenticationMode|int;

// After
public function getTwoFaMode(): int;
```

Since `AuthenticationMode` is an `int`-backed enum, returning the raw `int` value eliminates the core namespace dependency from the `@api` Contract without losing information.

| File | Change |
|------|--------|
| `app/Contracts/TwoFaInterface.php` | Return type `\App\Enums\AuthenticationMode\|int` → `int` |
| `app/Models/Member.php` | `getTwoFaMode()` now extracts `->value` from the Eloquent-cast enum |
| `app/Traits/PasskeyLoginTrait.php` | Removed defensive `is_int()` check (no longer needed) |
| `plugins/DixlaseUsers/app/Models/DixlaseUsersUser.php` | Already returned `int` — no change |

All `@api` Contracts in `app/Contracts/` are now free of `\App\Enums\*`, `\App\Models\*`, and `\App\Helpers\*` inline references.
