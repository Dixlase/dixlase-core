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

namespace App\Contracts\I18n;

/**
 * Tells which locales this site publishes content in, and which one is the default.
 *
 * A single-language site publishes in one locale, its primary locale. A
 * site that publishes in several gets that list from whichever extension
 * provides translations, which binds this contract in the container:
 *
 * ```php
 * $this->app->singleton(EnabledLocales::class, MyEnabledLocales::class);
 * ```
 *
 * A site has at most one translation provider, so this is a single
 * binding, not a tagged capability. Nothing is bound when no such
 * extension is installed.
 *
 * Plugins do not resolve the contract themselves; they call
 * `LocaleHelper::enabledLocales()`, `LocaleHelper::defaultEnabledLocale()`,
 * `LocaleHelper::isLocaleEnabled()` and
 * `LocaleHelper::defaultLocaleIsPinned()`, which answer for a
 * single-language site when nothing is bound.
 *
 * Whether front URLs carry the locale is a separate question, answered by
 * `LocaleUrlRouting`.
 *
 * @see \App\Helpers\LocaleHelper::enabledLocales()
 * @see LocaleUrlRouting
 */
interface EnabledLocales
{
    /**
     * The locale codes content is published in, in the order an operator
     * would list them (`['ja', 'en']`).
     *
     * The list includes the default locale. The helpers add it when an
     * implementation leaves it out.
     *
     * @return list<string>
     */
    public function locales(): array;

    /**
     * The locale used when a request names none, already resolved to a
     * locale code; never a mode name.
     */
    public function defaultLocale(): string;

    /**
     * Whether the operator fixed the default locale.
     *
     * True means every request without an explicit locale gets
     * `defaultLocale()`. False means the provider may pick a locale per
     * request (from the browser's language, for example), and
     * `defaultLocale()` is only the last resort.
     */
    public function defaultLocaleIsPinned(): bool;
}
