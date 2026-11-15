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
use App\DTO\Front\FrontPageBodyContext;
use App\DTO\Front\FrontPageBodyDTO;
use App\DTO\Front\FrontPageBodyResult;

/**
 * Front Page Body Provider Contract
 *
 * Lets an extension supply the body of the theme's front page — what a theme shows
 * between its hero and its footer — in place of core's front page content. The theme
 * keeps its own layout; a provider only supplies the body.
 *
 * This is the body-granularity counterpart to FrontPageProviderInterface. That one
 * replaces the whole response for `/`, so the theme's hero and footer go with it, and
 * it offers one choice per plugin. This one leaves the page to the theme and can offer
 * many choices — a page each, say — so an operator picks a particular one.
 *
 * Register it with a tag in the service container via your ServiceProvider:
 *
 * ```php
 * $this->app->tag([MyFrontPageBody::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * and declare the "front-page-body" capability in plugin.json. A theme then offers the
 * choices in its own settings, because only the theme knows it has a body slot to
 * fill, and renders the stored one through FrontPageBodyResolver.
 *
 * Rendering stays with the provider. That is what lets the plugin's own display
 * settings apply without the theme knowing any of their keys.
 *
 * **The per-site `front_page_display` setting is resolved first.** When it names a
 * front-page provider or `forbidden`, the theme front page never renders and a body
 * choice does not apply. A body choice is only ever reached while that setting is
 * `theme`.
 *
 * @see \App\Contracts\Front\FrontPageProviderInterface
 * @see \App\Services\Front\FrontPageBodyResolver
 */
interface FrontPageBodyProviderInterface extends PluginCapabilityInterface
{
    /**
     * Surface identifier for the live front page.
     */
    public const SURFACE_FRONT = 'front';

    /**
     * Surface identifier for a theme's settings preview.
     */
    public const SURFACE_PREVIEW = 'preview';

    /**
     * A short human label for this provider's group of choices, shown where an
     * operator chooses what the front page body displays (e.g. "Blog").
     *
     * A translation key is recommended so the chooser follows the admin locale.
     */
    public function getFrontPageBodyLabel(): string;

    /**
     * The choices this provider offers, without rendering any of them.
     *
     * Called to build a chooser, so it should stay cheap: return identifiers and
     * labels, not content. Return an empty array when the plugin has nothing to offer
     * yet — no published page, for instance. That is not an error.
     *
     * @param  int  $limit  Maximum number of choices to return
     * @return FrontPageBodyDTO[]
     */
    public function availableFrontPageBodies(int $limit = 100): array;

    /**
     * Render one choice as the body of the front page.
     *
     * Return null when $bodyId no longer resolves — the page it named was deleted or
     * unpublished, for instance. The caller then falls back to core's front page
     * content; returning null is the documented way to decline, not an error.
     *
     * The result carries the body plus the concerns a body cannot express on its own:
     * the title and description for SEO, the provider's own asset URLs, a canonical
     * URL, whether the response may be stored, and an admin edit link.
     * Implementations must not emit a surrounding layout: the theme places the body
     * inside its own.
     *
     * @param  string  $bodyId  The id of a FrontPageBodyDTO this provider offered
     * @param  FrontPageBodyContext  $context  Where and how the body is being rendered
     * @return FrontPageBodyResult|null The body, or null to decline
     */
    public function renderFrontPageBody(
        string $bodyId,
        FrontPageBodyContext $context,
    ): ?FrontPageBodyResult;
}
