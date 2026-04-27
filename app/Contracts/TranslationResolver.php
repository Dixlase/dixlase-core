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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Translation Resolver Contract
 *
 * This interface defines how translation plugins should implement
 * translation storage and retrieval. Plugins can register their
 * own implementation to handle translations.
 *
 * Example implementation:
 * ```php
 * class MyTranslationResolver implements TranslationResolver
 * {
 *     public function resolve(Model $model, string $field, string $locale): mixed
 *     {
 *         return Translation::where([
 *             'translatable_type' => get_class($model),
 *             'translatable_id' => $model->getKey(),
 *             'field' => $field,
 *             'locale' => $locale,
 *         ])->value('value');
 *     }
 *     // ... other methods
 * }
 * ```
 *
 * Register in ServiceProvider:
 * ```php
 * $this->app->singleton(TranslationResolver::class, MyTranslationResolver::class);
 * ```
 *
 * @see docs/translation-api-spec.md
 */
interface TranslationResolver
{
    /**
     * Resolve a translated value for a model field
     *
     * @param  Model  $model  The model instance
     * @param  string  $field  The field name
     * @param  string  $locale  The locale code (e.g., 'en', 'ja')
     * @return mixed The translated value or null if not found
     */
    public function resolve(Model $model, string $field, string $locale): mixed;

    /**
     * Store a translation for a model field
     *
     * @param  Model  $model  The model instance
     * @param  string  $field  The field name
     * @param  mixed  $value  The translated value
     * @param  string  $locale  The locale code
     */
    public function store(Model $model, string $field, mixed $value, string $locale): void;

    /**
     * Get all translations for a model field
     *
     * @param  Model  $model  The model instance
     * @param  string  $field  The field name
     * @return array Associative array of locale => value
     */
    public function all(Model $model, string $field): array;

    /**
     * Check if a translation exists
     *
     * @param  Model  $model  The model instance
     * @param  string  $field  The field name
     * @param  string  $locale  The locale code
     */
    public function exists(Model $model, string $field, string $locale): bool;

    /**
     * Delete a translation
     *
     * @param  Model  $model  The model instance
     * @param  string  $field  The field name
     * @param  string|null  $locale  The locale code, or null to delete all locales
     */
    public function delete(Model $model, string $field, ?string $locale = null): void;

    /**
     * Get all available locales for a model
     *
     * @param  Model  $model  The model instance
     * @return array List of locale codes
     */
    public function getAvailableLocales(Model $model): array;

    /**
     * Copy translations from one model to another
     *
     * @param  Model  $source  Source model
     * @param  Model  $target  Target model
     * @param  array|null  $fields  Specific fields to copy, or null for all
     * @param  array|null  $locales  Specific locales to copy, or null for all
     */
    public function copy(Model $source, Model $target, ?array $fields = null, ?array $locales = null): void;
}
