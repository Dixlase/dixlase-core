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

namespace App\Enums;

/**
 * Standard cookie consent categories.
 *
 * These four cases document the industry-standard categorisation
 * derived from IAB TCF and major CMP vendors. Implementations of
 * {@see \App\Contracts\Cookie\ConsentStateProviderInterface} accept
 * any string, so plugin-defined categories (e.g. a future newsletter
 * plugin's 'marketing-email') are allowed — but cross-plugin
 * consumers should expect these four to be the lingua franca and
 * should not assume any other category is present.
 *
 * Backed-string values are intentional: they serialise verbatim
 * across the cross-plugin / Wasm boundary and match the cookie
 * banner UI keys used by consenting plugins. The string values are
 * part of the public API surface and MUST NOT change once shipped —
 * persisted consent records reference them by value.
 */
enum ConsentCategory: string
{
    /**
     * Strictly necessary for the site to function. Always implicitly
     * granted; the banner shows it as "always on" with no toggle.
     * Examples: CSRF token, session cookie, load-balancer routing,
     * the cookie-consent record itself.
     */
    case Necessary = 'necessary';

    /**
     * Non-essential but improves user convenience. The visitor can
     * opt out without losing core site functionality.
     * Examples: language / theme preference, UI customisation,
     * embedded media playback position.
     */
    case Functional = 'functional';

    /**
     * Measures site usage. Includes Google Analytics and equivalents.
     * In EU / UK / Brazil this is the typical reason a banner exists
     * at all.
     */
    case Analytics = 'analytics';

    /**
     * Used for targeted advertising or cross-site tracking.
     * Examples: ad tags, retargeting pixels, social media trackers.
     */
    case Marketing = 'marketing';
}
