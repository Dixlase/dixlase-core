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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Content slug uniqueness validation rule
 *
 * Validates that the slug is not duplicated within the plugin table.
 * Excludes soft-deleted records by default.
 */
class UniqueContentSlug implements ValidationRule
{
    /** @var int|string|null Record ID to exclude during update */
    private int|string|null $ignoreId = null;

    /** @var string ID column name of the record to exclude */
    private string $ignoreIdColumn = 'id';

    /** @var array<int, array{column: string, value: mixed}> Additional WHERE conditions */
    private array $wheres = [];

    /** @var bool Whether to exclude soft-deleted records */
    private bool $excludeSoftDeleted = true;

    /**
     * @param  string  $table  Table name
     * @param  string  $column  Slug column name
     */
    public function __construct(
        private string $table,
        private string $column = 'slug',
    ) {}

    /**
     * Static factory method
     *
     * @param  string  $table  Table name
     * @param  string  $column  Slug column name
     */
    public static function for(string $table, string $column = 'slug'): static
    {
        return new static($table, $column);
    }

    /**
     * Exclude own record during update
     *
     * @param  int|string  $id  Record ID to exclude
     * @param  string  $idColumn  ID column name
     */
    public function ignore(int|string $id, string $idColumn = 'id'): static
    {
        $this->ignoreId = $id;
        $this->ignoreIdColumn = $idColumn;

        return $this;
    }

    /**
     * Specify additional WHERE conditions
     *
     * @param  string  $column  Column name
     * @param  mixed  $value  Value
     */
    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = ['column' => $column, 'value' => $value];

        return $this;
    }

    /**
     * Disable soft-delete exclusion
     */
    public function withoutSoftDeletes(): static
    {
        $this->excludeSoftDeleted = false;

        return $this;
    }

    /**
     * Execute validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $query = DB::table($this->table)->where($this->column, $value);

        if ($this->excludeSoftDeleted) {
            $query->whereNull('deleted_at');
        }

        if ($this->ignoreId !== null) {
            $query->where($this->ignoreIdColumn, '!=', $this->ignoreId);
        }

        foreach ($this->wheres as $where) {
            $query->where($where['column'], $where['value']);
        }

        if ($query->exists()) {
            $fail(__('validation/content-slug.unique', [
                'attribute' => $attribute,
            ]));
        }
    }
}
