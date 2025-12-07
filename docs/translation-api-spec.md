# Dixlase Translation API Specification

## Overview

This document defines the Translation API for Dixlase. The API provides a standardized way for plugins to implement multilingual functionality without modifying the core system.

## Version

- **Specification Version**: 1.0
- **Status**: Stable

## 1. Architecture

### 1.1 Design Philosophy

- **Core provides the interface, plugins provide the implementation**
- Models define which fields are translatable
- Translation storage and retrieval is delegated to plugins
- Events allow plugins to hook into translation lifecycle

### 1.2 Components

```
┌─────────────────────────────────────────────────────────────┐
│                      Your Model                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  use TranslatableTrait;                              │    │
│  │  protected $translatable = ['title', 'body'];        │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  TranslationManager                          │
│  - Manages locale settings                                   │
│  - Registers translation resolvers                           │
│  - Fires locale change events                                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              TranslationResolver (Interface)                 │
│  - resolve(): Get translated value                           │
│  - store(): Save translation                                 │
│  - all(): Get all translations                               │
│  - exists(): Check if translation exists                     │
│  - delete(): Remove translation                              │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│           Plugin's TranslationResolver Implementation        │
│  (e.g., Database tables, JSON files, external API)          │
└─────────────────────────────────────────────────────────────┘
```

## 2. Translatable Trait

### 2.1 Basic Usage

```php
use App\Traits\TranslatableTrait;

class Post extends Model
{
    use TranslatableTrait;

    /**
     * Fields that can be translated
     */
    protected array $translatable = [
        'title',
        'body',
        'slug',
        'meta_description',
    ];
}
```

### 2.2 Available Methods

| Method | Description |
|--------|-------------|
| `getTranslatableFields()` | Get list of translatable field names |
| `isTranslatable($field)` | Check if a field is translatable |
| `getTranslation($field, $locale, $fallback)` | Get translated value |
| `setTranslation($field, $value, $locale)` | Set translated value |
| `getTranslations($field)` | Get all translations for a field |
| `hasTranslation($field, $locale)` | Check if translation exists |
| `deleteTranslation($field, $locale)` | Delete a translation |

### 2.3 Automatic Translation

When a `TranslationResolver` is registered, the `getAttribute()` method automatically returns translated values:

```php
$post = Post::find(1);

// Automatically returns Japanese translation if locale is 'ja'
// and a resolver is registered
echo $post->title;

// Explicitly get a specific locale
echo $post->getTranslation('title', 'en');
```

## 3. Translation Resolver

### 3.1 Interface

```php
interface TranslationResolver
{
    public function resolve(Model $model, string $field, string $locale): mixed;
    public function store(Model $model, string $field, mixed $value, string $locale): void;
    public function all(Model $model, string $field): array;
    public function exists(Model $model, string $field, string $locale): bool;
    public function delete(Model $model, string $field, ?string $locale = null): void;
    public function getAvailableLocales(Model $model): array;
    public function copy(Model $source, Model $target, ?array $fields = null, ?array $locales = null): void;
}
```

### 3.2 Example Implementation

```php
namespace MyPlugin\Translation;

use App\Contracts\TranslationResolver;
use Illuminate\Database\Eloquent\Model;

class DatabaseTranslationResolver implements TranslationResolver
{
    public function resolve(Model $model, string $field, string $locale): mixed
    {
        return Translation::where([
            'translatable_type' => get_class($model),
            'translatable_id' => $model->getKey(),
            'field' => $field,
            'locale' => $locale,
        ])->value('value');
    }

    public function store(Model $model, string $field, mixed $value, string $locale): void
    {
        Translation::updateOrCreate(
            [
                'translatable_type' => get_class($model),
                'translatable_id' => $model->getKey(),
                'field' => $field,
                'locale' => $locale,
            ],
            ['value' => $value]
        );
    }

    // ... other methods
}
```

### 3.3 Registering a Resolver

In your plugin's ServiceProvider:

```php
use App\Support\TranslationManager;

public function register()
{
    TranslationManager::resolveUsing(DatabaseTranslationResolver::class);
}
```

Or with a closure for simple cases:

```php
TranslationManager::resolveUsing(function ($model, $field, $locale) {
    return Cache::get("translation.{$model->id}.{$field}.{$locale}");
});
```

## 4. Translation Manager

### 4.1 Locale Management

```php
use App\Support\TranslationManager;

// Set locale (fires LOCALE_CHANGED event)
TranslationManager::setLocale('ja');

// Get current locale
$locale = TranslationManager::getLocale();

// Get fallback locale
$fallback = TranslationManager::getFallbackLocale();
```

### 4.2 Available Locales

```php
// Register available locales (typically in plugin boot)
TranslationManager::registerLocales([
    'en' => 'English',
    'ja' => '日本語',
    'zh' => '中文',
]);

// Get available locales
$locales = TranslationManager::getAvailableLocales();

// Get locale display name
$name = TranslationManager::getLocaleName('ja'); // '日本語'

// Check if locale is available
if (TranslationManager::isLocaleAvailable('fr')) {
    // ...
}
```

## 5. Events

### 5.1 Translation Events

| Event | Payload | Description |
|-------|---------|-------------|
| `translation.model.retrieved` | `[Model]` | Model with translatable trait was retrieved |
| `translation.model.saving` | `[Model]` | Model is being saved |
| `translation.model.saved` | `[Model]` | Model was saved |
| `translation.model.deleted` | `[Model]` | Model was deleted |
| `translation.field.updated` | `[Model, field, value, locale]` | Translation was updated |
| `translation.field.deleted` | `[Model, field, locale]` | Translation was deleted |

### 5.2 Locale Events

| Event | Payload | Description |
|-------|---------|-------------|
| `dixlase.locale.changed` | `['locale' => string, 'previous' => string]` | Locale was changed |

### 5.3 Listening to Events

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

// In your plugin's ServiceProvider
Event::listen(DixlaseEvents::LOCALE_CHANGED, function ($data) {
    // Clear translation cache for new locale
    Cache::tags(['translations', $data['locale']])->flush();
});

Event::listen('translation.model.deleted', function ($model) {
    // Clean up translations when model is deleted
    Translation::where([
        'translatable_type' => get_class($model),
        'translatable_id' => $model->getKey(),
    ])->delete();
});
```

## 6. Best Practices

### 6.1 For Plugin Developers

1. **Always check if resolver exists** before performing translation operations
2. **Use events** to sync translations with your storage
3. **Implement caching** in your resolver for performance
4. **Handle fallbacks** gracefully when translations don't exist

### 6.2 For Theme Developers

1. **Use `getTranslation()`** for explicit locale control
2. **Check `hasTranslation()`** before displaying locale switchers
3. **Use `getTranslatableFields()`** to build translation forms

### 6.3 Database Schema Recommendation

If implementing database-based translations:

```sql
CREATE TABLE translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translatable_type VARCHAR(255) NOT NULL,
    translatable_id BIGINT UNSIGNED NOT NULL,
    field VARCHAR(255) NOT NULL,
    locale VARCHAR(10) NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_translation (translatable_type, translatable_id, field, locale),
    INDEX idx_translatable (translatable_type, translatable_id),
    INDEX idx_locale (locale)
);
```

## 7. Migration Guide

### 7.1 Adding Translation Support to Existing Models

1. Add the `TranslatableTrait` trait
2. Define `$translatable` array
3. Install a translation plugin
4. Migrate existing content to translations table

### 7.2 Example Migration Script

```php
// Migrate existing content to translations
$posts = Post::all();
$resolver = TranslationManager::getResolver();

foreach ($posts as $post) {
    foreach ($post->getTranslatableFields() as $field) {
        $resolver->store($post, $field, $post->$field, 'en');
    }
}
```

## Appendix A: Complete Example

### Plugin: Simple Translation

```php
// app/Providers/SimpleTranslationServiceProvider.php
namespace MyPlugin\Providers;

use App\Contracts\TranslationResolver;
use App\Support\TranslationManager;
use Illuminate\Support\ServiceProvider;

class SimpleTranslationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TranslationResolver::class, SimpleTranslationResolver::class);
    }

    public function boot()
    {
        TranslationManager::registerLocales([
            'en' => 'English',
            'ja' => '日本語',
        ]);
    }
}
```

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-01-01  
**Author**: Dixlase Development Team
