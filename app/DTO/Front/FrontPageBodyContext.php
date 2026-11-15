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

namespace App\DTO\Front;

use App\Contracts\Front\FrontPageBodyProviderInterface;
use JsonSerializable;

/**
 * Front page source render context DTO
 *
 * Passed to FrontPageBodyProviderInterface::renderFrontPageBody() so the same
 * choice can adapt between the live front page and a theme's settings preview.
 *
 * It deliberately carries no Request. A provider has to be renderable from an admin
 * preview, where the request is the admin's, not the front page's — so everything a
 * provider may depend on travels here explicitly.
 *
 * @see \App\Contracts\Front\FrontPageBodyProviderInterface
 */
final readonly class FrontPageBodyContext implements JsonSerializable
{
    /**
     * @param  string  $surface  Where the body is being rendered
     *                           (one of FrontPageBodyProviderInterface::SURFACE_*)
     * @param  string|null  $locale  Request locale; null leaves the choice to the provider
     * @param  int  $page  Page number for a paginated body (1-based)
     * @param  bool  $showHeading  Whether the body should print its own top-level heading.
     *                             A theme whose hero already carries the page heading passes
     *                             false, so the page does not end up with two of them.
     * @param  int|null  $siteId  Current site id (multisite aware); null outside a site context
     * @param  array<string, mixed>  $meta  Additional theme-specific metadata
     */
    public function __construct(
        public string $surface = FrontPageBodyProviderInterface::SURFACE_FRONT,
        public ?string $locale = null,
        public int $page = 1,
        public bool $showHeading = true,
        public ?int $siteId = null,
        public array $meta = [],
    ) {}

    /**
     * Whether this render is for the live front page.
     */
    public function isFront(): bool
    {
        return $this->surface === FrontPageBodyProviderInterface::SURFACE_FRONT;
    }

    /**
     * Whether this render is for the theme settings preview.
     */
    public function isPreview(): bool
    {
        return $this->surface === FrontPageBodyProviderInterface::SURFACE_PREVIEW;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'surface' => $this->surface,
            'locale' => $this->locale,
            'page' => $this->page,
            'show_heading' => $this->showHeading,
            'site_id' => $this->siteId,
            'meta' => $this->meta,
        ];
    }
}
