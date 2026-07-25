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

namespace App\DTO\PluginIntegration;

/**
 * Search Query DTO
 *
 * Immutable data object representing search conditions
 * for performing content searches between plugins
 */
final readonly class SearchQueryDTO
{
    /**
     * @param  string  $q  Search keyword
     * @param  int  $page  Page number (1-based)
     * @param  int  $perPage  Items per page
     * @param  array<string,scalar|array|null>  $filters  Filter conditions
     * @param  array<string,'asc'|'desc'>  $sort  Sort conditions
     */
    public function __construct(
        public string $q = '',
        public int $page = 1,
        public int $perPage = 20,
        public array $filters = [],
        public array $sort = [],
    ) {}

    /**
     * Check if search keyword is specified
     */
    public function hasQuery(): bool
    {
        return $this->q !== '';
    }

    /**
     * Check if a specific filter is specified
     *
     * @param  string  $key  Filter key
     */
    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    /**
     * Get filter value
     *
     * @param  string  $key  Filter key
     * @param  mixed  $default  Default value
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Check if sort conditions are specified
     */
    public function hasSort(): bool
    {
        return ! empty($this->sort);
    }

    /**
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            q: (string) ($data['q'] ?? ''),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
            filters: (array) ($data['filters'] ?? []),
            sort: (array) ($data['sort'] ?? []),
        );
    }

    /**
     * Create DTO from request
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            q: (string) $request->query('q', ''),
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 20),
            filters: (array) $request->query('filters', []),
            sort: (array) $request->query('sort', []),
        );
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'q' => $this->q,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'filters' => $this->filters,
            'sort' => $this->sort,
        ];
    }

    /**
     * Create new DTO with modified search keyword
     *
     * @param  string  $q  New search keyword
     */
    public function withQuery(string $q): self
    {
        return new self(
            q: $q,
            page: $this->page,
            perPage: $this->perPage,
            filters: $this->filters,
            sort: $this->sort,
        );
    }

    /**
     * Create new DTO with modified page number
     *
     * @param  int  $page  New page number
     */
    public function withPage(int $page): self
    {
        return new self(
            q: $this->q,
            page: $page,
            perPage: $this->perPage,
            filters: $this->filters,
            sort: $this->sort,
        );
    }

    /**
     * Generate a new DTO with added filter
     *
     * @param  array<string,mixed>  $filters  Filter to add
     */
    public function withFilters(array $filters): self
    {
        return new self(
            q: $this->q,
            page: $this->page,
            perPage: $this->perPage,
            filters: array_merge($this->filters, $filters),
            sort: $this->sort,
        );
    }

    /**
     * Generate a new DTO with modified sort condition
     *
     * @param  array<string,'asc'|'desc'>  $sort  New sort condition
     */
    public function withSort(array $sort): self
    {
        return new self(
            q: $this->q,
            page: $this->page,
            perPage: $this->perPage,
            filters: $this->filters,
            sort: $sort,
        );
    }
}
