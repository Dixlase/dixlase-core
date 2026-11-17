<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 * Tells whether a translatable content type is registered on this site.
 *
 * Extensions declare their translatable content in the
 * `multilingual_content.types` block of `plugin.json`, each type under a
 * key such as `my-plugin:settings`. Core does not read that block; the
 * translation provider does, and it binds this contract so that an
 * extension can ask whether its own type was taken up, for example before
 * offering a per-locale editor:
 *
 * ```php
 * $this->app->singleton(TranslatableContentTypes::class, MyTranslatableContentTypes::class);
 * ```
 *
 * A site has at most one translation provider, so this is a single
 * binding, not a tagged capability. Nothing is bound when no such
 * extension is installed.
 *
 * Plugins do not resolve the contract themselves; they call
 * `LocaleHelper::translatableTypeRegistered()`, which answers `false`
 * when nothing is bound.
 *
 * @see \App\Helpers\LocaleHelper::translatableTypeRegistered()
 * @see TranslatableContentProvider
 */
interface TranslatableContentTypes
{
    /**
     * Whether a translatable content type with this key is registered and
     * can take translations now.
     */
    public function isRegistered(string $key): bool;
}
