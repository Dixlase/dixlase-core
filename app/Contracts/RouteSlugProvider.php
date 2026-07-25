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

use App\DTO\RouteSlug\RegisteredSlug;

/**
 * Route Slug Provider Interface
 *
 * Interface for providing URL slugs managed by plugins and themes
 * By implementing this interface, you can participate in
 * duplicate checking for top-level URL slugs
 *
 * @example
 * class MyPluginRouteSlugProvider implements RouteSlugProvider
 * {
 *     public function getRouteSlugs(): array
 *     {
 *         $slug = $this->settingRepo->get('url_slug', 'my-plugin');
 *         return [
 *             new RegisteredSlug(
 *                 slug: $slug,
 *                 owner: 'my-plugin:url_slug',
 *                 label: 'my-plugin::admin.settings.url_slug',
 *             ),
 *         ];
 *     }
 * }
 */
interface RouteSlugProvider
{
    /**
     * Get list of managed route slugs
     *
     * @return array<RegisteredSlug> Array of registered slugs
     */
    public function getRouteSlugs(): array;
}
