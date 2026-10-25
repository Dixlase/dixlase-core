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

namespace App\Contracts;

/**
 * Viewer Timezone Resolver Contract
 *
 * Dixlase stores and compares datetimes in UTC and converts to
 * site_settings.display_timezone for display. That site-wide clock is the
 * right one for an admin screen, but not for a screen that shows a member
 * their own data: there, the member's own timezone is.
 *
 * Core holds no per-member timezone, and does not grow one for this. An
 * extension that owns a profile screen registers an implementation, and
 * core asks it whenever a datetime is resolved for the viewer rather than
 * for the site — <x-form-datetime context="viewer" /> and
 * DateTimeHelper::viewerTimezone(). With nothing registered, the site's
 * display timezone is used, so a site without such an extension behaves
 * exactly as before.
 *
 * Example implementation:
 * ```php
 * class MemberProfileTimezoneResolver implements ViewerTimezoneResolver
 * {
 *     public function resolve(): ?string
 *     {
 *         return Auth::guard('member')->user()?->profile?->timezone;
 *     }
 * }
 * ```
 *
 * Register in a ServiceProvider:
 * ```php
 * $this->app->singleton(ViewerTimezoneResolver::class, MemberProfileTimezoneResolver::class);
 * ```
 */
interface ViewerTimezoneResolver
{
    /**
     * The timezone to show the current viewer their datetimes in.
     *
     * Return null when there is no viewer, or the viewer has chosen
     * nothing: core then falls back to the site's display timezone. An
     * identifier PHP does not know is treated the same way, so a stale
     * stored value cannot break a page.
     *
     * @return string|null A PHP timezone identifier, e.g. 'Asia/Tokyo'
     */
    public function resolve(): ?string;
}
