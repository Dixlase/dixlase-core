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

namespace App\Contracts\PluginIntegration;

use App\DTO\PluginIntegration\LinkableDTO;

/**
 * Contract for plugins that provide linkable content
 *
 * Common interface for menu plugins and others to
 * retrieve content from other plugins
 */
interface LinkableProviderInterface
{
    /**
     * Retrieve the provider identifier
     *
     * @return string e.g. 'dixlase-pages', 'dixlase-blog'
     */
    public function getProviderKey(): string;

    /**
     * Retrieve the provider display name
     *
     * @return string e.g. 'Pages', 'Blog Posts'
     */
    public function getProviderLabel(): string;

    /**
     * Retrieve the provider icon class (optional)
     *
     * @return string|null e.g. 'fas fa-file-alt'
     */
    public function getProviderIcon(): ?string;

    /**
     * Whether this provider is currently available
     */
    public function isAvailable(): bool;

    /**
     * Retrieve a list of available content
     *
     * @param  int  $limit  Maximum number of items to retrieve (default: 100)
     * @return LinkableDTO[]
     */
    public function getAvailableItems(int $limit = 100): array;

    /**
     * Search content based on a search query
     *
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum number of items to retrieve (default: 20)
     * @return LinkableDTO[]
     */
    public function searchItems(string $query, int $limit = 20): array;

    /**
     * Retrieve content by specific ID
     *
     * @param  string  $id  Content ID
     */
    public function getItemById(string $id): ?LinkableDTO;
}
