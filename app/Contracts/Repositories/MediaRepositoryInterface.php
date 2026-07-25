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

namespace App\Contracts\Repositories;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Media repository interface
 */
interface MediaRepositoryInterface
{
    /**
     * Get media by ID
     */
    public function find(int $id): ?Media;

    /**
     * Get media by ID with relations
     *
     * @param  array<string>  $relations
     */
    public function findWithRelations(int $id, array $relations = ['member']): ?Media;

    /**
     * Get all media
     *
     * @param  array<string>  $relations
     */
    public function all(array $relations = []): Collection;

    /**
     * Get media with pagination
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $perPage = 25,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator;

    /**
     * Create media
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Media;

    /**
     * Update media
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete media (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Permanently delete media
     */
    public function forceDelete(int $id): bool;

    /**
     * Restore deleted media
     */
    public function restore(int $id): bool;

    /**
     * Get media matching search criteria
     *
     * @param  array<string>  $relations
     */
    public function search(string $search, array $relations = ['member']): Collection;

    /**
     * Filter by type
     *
     * @param  array<string>  $relations
     */
    public function filterByType(string $type, array $relations = ['member']): Collection;

    /**
     * Filter by uploader
     *
     * @param  array<string>  $relations
     */
    public function filterByUploader(int $memberId, array $relations = ['member']): Collection;

    /**
     * Filter by date range
     *
     * @param  array<string>  $relations
     */
    public function filterByDateRange(?string $dateFrom, ?string $dateTo, array $relations = ['member']): Collection;

    /**
     * Check if media exists
     */
    public function exists(int $id): bool;

    /**
     * Get media count
     *
     * @param  array<string, mixed>  $filters
     */
    public function count(array $filters = []): int;
}
