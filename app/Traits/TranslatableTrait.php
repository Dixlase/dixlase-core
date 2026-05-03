<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use App\Contracts\TranslationResolver;
use Illuminate\Support\Facades\App;

/**
 * Translatable Trait
 *
 * Provides translation capability for Eloquent models.
 * This trait defines which fields are translatable and provides
 * a standard interface for translation plugins to hook into.
 *
 * Usage:
 * ```php
 * class Post extends Model
 * {
 *     use TranslatableTrait;
 *
 *     protected array $translatable = [
 *         'title',
 *         'body',
 *         'slug',
 *         'meta_description',
 *     ];
 * }
 * ```
 *
 * Translation plugins can register a custom resolver:
 * ```php
 * TranslationManager::resolveUsing(function ($model, $key, $locale) {
 *     return MyTranslationTable::get($model, $key, $locale);
 * });
 * ```
 *
 * @see docs/translation-api-spec.md
 */
trait TranslatableTrait
{
    /**
     * Boot the translatable trait
     */
    public static function bootTranslatableTrait(): void
    {
        // Fire event when model is retrieved
        static::retrieved(function ($model) {
            event('translation.model.retrieved', [$model]);
        });

        // Fire event when model is saving
        static::saving(function ($model) {
            event('translation.model.saving', [$model]);
        });

        // Fire event when model is saved
        static::saved(function ($model) {
            event('translation.model.saved', [$model]);
        });

        // Fire event when model is deleted
        static::deleted(function ($model) {
            event('translation.model.deleted', [$model]);
        });
    }

    /**
     * Get the translatable fields for this model
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Check if a field is translatable
     */
    public function isTranslatable(string $field): bool
    {
        return in_array($field, $this->getTranslatableFields(), true);
    }

    /**
     * Get translated value for a field
     *
     * @param  string|null  $locale  Locale code (defaults to current locale)
     * @param  bool  $fallback  Whether to fallback to default locale
     */
    public function getTranslation(string $field, ?string $locale = null, bool $fallback = true): mixed
    {
        $locale = $locale ?? App::getLocale();
        $defaultLocale = config('app.fallback_locale', 'en');

        // If translation resolver is registered, use it
        if (App::bound(TranslationResolver::class)) {
            $resolver = App::make(TranslationResolver::class);
            $value = $resolver->resolve($this, $field, $locale);

            // Fallback to default locale if no translation found
            if ($value === null && $fallback && $locale !== $defaultLocale) {
                $value = $resolver->resolve($this, $field, $defaultLocale);
            }

            // Fallback to original value if still no translation
            if ($value === null && $fallback) {
                return $this->getOriginalValue($field);
            }

            return $value;
        }

        // No resolver registered, return original value
        return $this->getOriginalValue($field);
    }

    /**
     * Set translated value for a field
     *
     * @return $this
     */
    public function setTranslation(string $field, mixed $value, ?string $locale = null): static
    {
        $locale = $locale ?? App::getLocale();

        if (App::bound(TranslationResolver::class)) {
            $resolver = App::make(TranslationResolver::class);
            $resolver->store($this, $field, $value, $locale);
        }

        // Fire event
        event('translation.field.updated', [$this, $field, $value, $locale]);

        return $this;
    }

    /**
     * Get all translations for a field
     *
     * @return array ['en' => 'value', 'ja' => 'value', ...]
     */
    public function getTranslations(string $field): array
    {
        if (App::bound(TranslationResolver::class)) {
            $resolver = App::make(TranslationResolver::class);

            return $resolver->all($this, $field);
        }

        return [
            config('app.fallback_locale', 'en') => $this->getOriginalValue($field),
        ];
    }

    /**
     * Check if translation exists for a field and locale
     */
    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $locale = $locale ?? App::getLocale();

        if (App::bound(TranslationResolver::class)) {
            $resolver = App::make(TranslationResolver::class);

            return $resolver->exists($this, $field, $locale);
        }

        return false;
    }

    /**
     * Delete translation for a field and locale
     *
     * @param  string|null  $locale  If null, deletes all translations for the field
     * @return $this
     */
    public function deleteTranslation(string $field, ?string $locale = null): static
    {
        if (App::bound(TranslationResolver::class)) {
            $resolver = App::make(TranslationResolver::class);
            $resolver->delete($this, $field, $locale);
        }

        event('translation.field.deleted', [$this, $field, $locale]);

        return $this;
    }

    /**
     * Get the original (non-translated) value of a field
     */
    protected function getOriginalValue(string $field): mixed
    {
        return $this->attributes[$field] ?? null;
    }

    /**
     * Override getAttribute to automatically return translated values
     *
     * @param  string  $key
     */
    public function getAttribute($key): mixed
    {
        // Check if this is a translatable field and resolver is available
        if ($this->isTranslatable($key) && App::bound(TranslationResolver::class)) {
            $value = $this->getTranslation($key);
            if ($value !== null) {
                return $value;
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Get model identifier for translation storage
     */
    public function getTranslatableType(): string
    {
        return get_class($this);
    }

    /**
     * Get model ID for translation storage
     */
    public function getTranslatableId(): int|string
    {
        return $this->getKey();
    }
}
