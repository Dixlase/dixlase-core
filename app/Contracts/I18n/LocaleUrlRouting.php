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

declare(strict_types=1);

namespace App\Contracts\I18n;

/**
 * Tells whether front URLs on this site carry a leading locale segment.
 *
 * When locale URL routing is on, front URLs start with the locale code
 * (`/en/posts/hello`, `/ja/posts/hello`); when it is off, the site serves
 * one URL space and the locale is not in the path. Core owns that first
 * segment — the locale codes are reserved route slugs — but the switch
 * belongs to whichever extension implements locale routing, so it binds
 * this contract in the container:
 *
 * ```php
 * $this->app->singleton(LocaleUrlRouting::class, MyLocaleUrlRouting::class);
 * ```
 *
 * A site has at most one locale router, so this is a single binding, not
 * a tagged capability. Nothing is bound when no such extension is
 * installed.
 *
 * Plugins that build their own front URLs do not resolve the contract
 * themselves; they call `LocaleHelper::localeUrlRoutingEnabled()`, which
 * answers `false` when nothing is bound.
 *
 * @see \App\Helpers\LocaleHelper::localeUrlRoutingEnabled()
 */
interface LocaleUrlRouting
{
    /**
     * Whether front URLs are prefixed with the locale code on this site.
     */
    public function isEnabled(): bool;
}
