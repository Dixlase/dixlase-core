# SDK Trait Dependencies Analysis

This document records the core Trait dependency analysis for future `dixlase/plugin-sdk` package extraction.

## Overview

| Trait | Core Dependencies | Extractability | Priority |
|-------|------------------|----------------|----------|
| ConfigLoaderTrait | None | Easy | 1st |
| ThemeLoaderTrait | Theme model, DB, Schema | Medium-Hard | 3rd |
| PluginLoaderTrait | Plugin model, Schema, AdminHelper, ConfigLoaderTrait | Hard | 2nd |

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

### Phase 2: Interface Abstraction
- Define SDK interfaces:
  - `PluginRepositoryInterface` (query enabled plugins, check plugin existence)
  - `ThemeRepositoryInterface` (get enabled theme, query theme settings)
  - `AdminNavigationManagerInterface` (merge navigation items)
- Core provides concrete implementations of these interfaces

### Phase 3: Trait Refactoring
- Refactor `PluginLoaderTrait` and `ThemeLoaderTrait` to depend on interfaces rather than concrete classes
- Move refactored traits into the SDK package
- Core binds its implementations via the service container
