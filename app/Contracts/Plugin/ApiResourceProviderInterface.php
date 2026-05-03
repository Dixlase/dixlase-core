<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Contracts\Plugin;

use App\DTO\Api\ApiResourceCollection;
use App\DTO\Api\ApiResourceDTO;

/**
 * API resource provider interface
 *
 * Implemented when a plugin exposes content resources via REST API
 * The DixlaseApi plugin auto-discovers implementations of this interface
 * and provides them as unified API endpoints
 */
interface ApiResourceProviderInterface extends PluginCapabilityInterface
{
    /**
     * Get resource type identifier
     *
     * @return string e.g. 'pages', 'posts'
     */
    public function getResourceType(): string;

    /**
     * Get resource display name
     *
     * @return string e.g. 'Pages', 'Blog Posts'
     */
    public function getResourceLabel(): string;

    /**
     * Get resource list (with pagination support)
     *
     * @param  int  $page  Page number
     * @param  int  $perPage  Items per page
     * @param  array<string, mixed>  $filters  Filter conditions
     */
    public function listResources(int $page = 1, int $perPage = 20, array $filters = []): ApiResourceCollection;

    /**
     * Get resource by slug
     *
     * @param  string  $slug  Resource slug
     */
    public function findBySlug(string $slug): ?ApiResourceDTO;

    /**
     * Get resource by ID
     *
     * @param  string  $id  Resource ID
     */
    public function findById(string $id): ?ApiResourceDTO;

    /**
     * Return available filter keys
     *
     * @return array<string, string> Associative array of key => description
     */
    public function getAvailableFilters(): array;
}
