<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace App\Contracts\Theme;

/**
 * Optional contract exposing the active site theme's effective
 * appearance preference (light / dark / OS-follow) so that surfaces
 * rendered outside the theme can mirror the chosen colour scheme.
 *
 * The canonical use case is Cloudflare Turnstile, which renders its
 * challenge widget inside a same-origin iframe — the iframe is a
 * separate browsing context, so Tailwind's `dark:` class on
 * `<html>` does not propagate. Without an explicit `data-theme`
 * hint, the widget falls back to `prefers-color-scheme`, which
 * routinely diverges from the site's chosen appearance (e.g. a
 * theme pinned to dark by `appearance_mode = '2'` rendered to a
 * visitor whose OS is in light mode).
 *
 * Themes that surface a light/dark setting in their admin UI bind
 * an implementation of this interface in their service provider.
 * Consumers (e.g. `TurnstileCaptchaDriver`) probe `app()->bound()`
 * for the binding and gracefully fall back to safe defaults when
 * no theme provider is in play — default scaffolds, headless test
 * runs, themes that genuinely have no appearance concept, etc.
 *
 * Implementations must be cheap to invoke (preferably reading from
 * an in-process cache) since consumers may call them on every
 * widget render.
 */
interface SiteAppearanceProviderInterface
{
    /**
     * Return the site's effective appearance preference.
     *
     * @return string One of `auto` (follow visitor's
     *                `prefers-color-scheme`), `light` (pin to
     *                light), or `dark` (pin to dark). Implementations
     *                that cannot resolve a meaningful value should
     *                return `auto` rather than throw.
     */
    public function getAppearanceMode(): string;
}
