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

namespace App\Contracts\Front;

use App\Contracts\Plugin\PluginCapabilityInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Front Page Provider Contract
 *
 * Lets an extension render the site front page (`/`) in place of the theme-based
 * front page. Core owns the `/` route; a provider only supplies what to show.
 *
 * Register it with a tag in the service container via your ServiceProvider:
 *
 * ```php
 * $this->app->tag([MyFrontPageProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * and declare the "front-page" capability in plugin.json. Core then offers this
 * provider as a choice for the per-site `front_page_display` setting; when that
 * setting is the provider's slug, core serves `renderFrontPage()` for `/`. The
 * setting also has the built-in values `theme` (the default theme front page)
 * and `forbidden` (a 403), so no provider is needed to hide the front page.
 *
 * While the setting is `theme` or a provider is unavailable, core renders the
 * theme front page exactly as before.
 */
interface FrontPageProviderInterface extends PluginCapabilityInterface
{
    /**
     * A short human label for this front-page option, shown where an operator
     * chooses what the site's front page displays (e.g. "Documentation home").
     */
    public function getFrontPageLabel(): string;

    /**
     * Render the response served for the site front page (`/`).
     *
     * Returned as-is. Build it with the usual helpers, e.g.
     * `response()->view(...)`, a redirect, or any other Response.
     */
    public function renderFrontPage(Request $request): Response;
}
