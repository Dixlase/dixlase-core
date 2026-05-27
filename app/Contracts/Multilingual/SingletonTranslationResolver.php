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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Contracts\Multilingual;

/**
 * Storage operations for **singleton** translatable content.
 *
 * Counterpart to {@see \App\Contracts\TranslationResolver}, which is
 * model-anchored (one Eloquent row per translatable entity). The
 * singleton variant addresses translations by the plugin.json type
 * `key` plus a fixed sentinel id (typically 1) — there is no entity
 * row to anchor to.
 *
 * Plugins call this contract via the container after checking that an
 * implementation is bound:
 *
 * ```php
 * if (app()->bound(SingletonTranslationResolver::class)) {
 *     $value = app(SingletonTranslationResolver::class)
 *         ->resolve('dixlase-inquiry:settings', 'form_heading', 'ja');
 * }
 * ```
 *
 * Implementations write to the multilingual plugin's translations table
 * using `translatable_type = $typeKey` and `translatable_id = 1`. The
 * primary-locale fallback (when no translation exists for the locale)
 * is provided by a separately registered
 * {@see TranslatableContentProvider}; this resolver does **not** read
 * from it — that is the caller's responsibility.
 *
 * @see TranslatableContentProvider
 * @see \App\Contracts\TranslationResolver The model-anchored counterpart
 */
interface SingletonTranslationResolver
{
    /**
     * Resolve the translated value for a field/locale of a singleton type.
     *
     * @param  string  $typeKey  The `multilingual_content.types[].key` from plugin.json
     * @param  string  $field  The field name (one of the type's declared fields)
     * @param  string  $locale  The locale code (e.g. 'en', 'ja')
     * @return mixed The translated value, or null if no translation exists for the locale
     */
    public function resolve(string $typeKey, string $field, string $locale): mixed;

    /**
     * Store a translated value for a field/locale of a singleton type.
     */
    public function store(string $typeKey, string $field, mixed $value, string $locale): void;

    /**
     * Return all translations for a single field across all locales as
     * an associative array `locale => value`.
     *
     * @return array<string, mixed>
     */
    public function all(string $typeKey, string $field): array;

    /**
     * Whether a translation exists for the given field/locale.
     */
    public function exists(string $typeKey, string $field, string $locale): bool;

    /**
     * Delete a translation for a field. With `$locale = null`, deletes
     * the field from every locale that has it.
     */
    public function delete(string $typeKey, string $field, ?string $locale = null): void;

    /**
     * Return the list of locales that have at least one translated
     * field for the given singleton type.
     *
     * @return list<string>
     */
    public function getAvailableLocales(string $typeKey): array;
}
