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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
 * Primary-locale value source for **singleton** translatable content.
 *
 * The multilingual translations table anchors per-locale values on a
 * polymorphic `(translatable_type, translatable_id)` pair. For per-row
 * entities (e.g. pages, posts) the entity row supplies the anchor *and*
 * the primary-locale value. For **singleton-shaped** content (e.g. plugin
 * settings stored in a key-value table, or theme settings) there is no
 * such entity row — the polymorphic anchor is a fixed sentinel
 * (`translatable_id = 1`) and the primary-locale value lives elsewhere
 * in the plugin's own storage.
 *
 * A plugin that declares
 * `multilingual_content.types[].cardinality = "singleton"` in its
 * `plugin.json` provides an implementation of this contract so that
 * the multilingual plugin can answer "what is the value for `<field>`
 * when no per-locale translation exists?" without owning the storage.
 *
 * The provider is **read-only**:
 * - Per-locale translations are written by the central translation
 *   manager UI through the singleton translation resolver, not by the
 *   provider.
 * - Primary-locale values are written by the plugin's own settings UI
 *   into the plugin's own storage, again not through the provider.
 *
 * Example implementation:
 * ```php
 * class InquirySettingsProvider implements TranslatableContentProvider
 * {
 *     public function getPrimaryValue(string $field): ?string
 *     {
 *         return DixlaseInquirySetting::get($field);
 *     }
 *
 *     public function getPrimaryLocale(): ?string
 *     {
 *         $lang = DixlaseInquirySetting::get('lang');
 *         return $lang === 'auto' ? null : $lang;
 *     }
 * }
 * ```
 *
 * Declared in `plugin.json`:
 * ```json
 * "multilingual_content": {
 *     "types": [
 *         {
 *             "key": "dixlase-inquiry:settings",
 *             "cardinality": "singleton",
 *             "provider": "Plugins\\DixlaseInquiry\\App\\Multilingual\\InquirySettingsProvider",
 *             ...
 *         }
 *     ]
 * }
 * ```
 *
 * @see \App\Contracts\Multilingual\SingletonTranslationResolver
 */
interface TranslatableContentProvider
{
    /**
     * Return the primary-locale value for a translatable field, or null
     * if no value has been set.
     *
     * Called by the singleton translation resolver as the fallback when
     * no per-locale translation exists for the requested locale.
     */
    public function getPrimaryValue(string $field): ?string;

    /**
     * Return the locale the provider's primary values are authored in,
     * or null if no specific locale is declared (in which case the
     * provider's values are treated as locale-neutral).
     *
     * The translation manager UI uses this to exclude the primary locale
     * from its locale selector for singleton-shaped content: per-locale
     * translations for the primary locale would be meaningless because
     * the provider already supplies those values directly. The runtime
     * helper short-circuits when the current locale matches this, so the
     * primary value is returned without a translation table lookup.
     *
     * Providers that read the locale from their own settings (e.g. an
     * "authoring language" config on the plugin/theme) should return it
     * here; providers with no such concept may return null.
     */
    public function getPrimaryLocale(): ?string;
}
