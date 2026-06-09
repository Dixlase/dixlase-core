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

use JsonSerializable;

/**
 * Paginated result DTO
 *
 * Immutable data object containing pagination information
 * for passing search results between plugins
 *
 * @template T of JsonSerializable
 */
final readonly class PaginatedResultDTO implements JsonSerializable
{
    /**
     * @param  array<T>  $items  Search result items
     * @param  int  $total  Total count
     * @param  int  $page  Current page number
     * @param  int  $perPage  Items per page
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * Get total number of pages
     */
    public function totalPages(): int
    {
        if ($this->perPage <= 0) {
            return 0;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Check if next page exists
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    /**
     * Check if previous page exists
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * Start position of current page (1-indexed)
     */
    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->page - 1) * $this->perPage + 1;
    }

    /**
     * End position of current page
     */
    public function to(): int
    {
        $to = $this->page * $this->perPage;

        return min($to, $this->total);
    }

    /**
     * Check if result is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if result exists
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => array_map(
                fn ($item) => $item instanceof JsonSerializable ? $item->jsonSerialize() : $item,
                $this->items
            ),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages(),
            'has_next_page' => $this->hasNextPage(),
            'has_previous_page' => $this->hasPreviousPage(),
            'from' => $this->from(),
            'to' => $this->to(),
        ];
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Create from Laravel paginator
     *
     * @param  callable|null  $transformer  Item transformation function
     */
    public static function fromPaginator(
        \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator,
        ?callable $transformer = null
    ): self {
        $items = $paginator->items();

        if ($transformer !== null) {
            $items = array_map($transformer, $items);
        }

        return new self(
            items: $items,
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
        );
    }

    /**
     * Create from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: $data['items'] ?? [],
            total: (int) ($data['total'] ?? 0),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
        );
    }

    /**
     * Create new DTO with transformed items
     *
     * @param  callable  $callback  Conversion function
     */
    public function map(callable $callback): self
    {
        return new self(
            items: array_map($callback, $this->items),
            total: $this->total,
            page: $this->page,
            perPage: $this->perPage,
        );
    }

    /**
     * Generate a new DTO with filtered items
     *
     * @param  callable  $callback  Filter function
     */
    public function filter(callable $callback): self
    {
        $filtered = array_filter($this->items, $callback);

        return new self(
            items: array_values($filtered),
            total: count($filtered),
            page: $this->page,
            perPage: $this->perPage,
        );
    }
}
