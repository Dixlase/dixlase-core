<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * コンテンツスラッグ一意性バリデーションルール
 *
 * プラグインテーブル内でスラッグが重複しないことを検証する。
 * デフォルトでソフトデリート済みレコードを除外する。
 */
class UniqueContentSlug implements ValidationRule
{
    /** @var int|string|null 更新時に除外するレコードのID */
    private int|string|null $ignoreId = null;

    /** @var string 除外するレコードのIDカラム名 */
    private string $ignoreIdColumn = 'id';

    /** @var array<int, array{column: string, value: mixed}> 追加WHERE条件 */
    private array $wheres = [];

    /** @var bool ソフトデリート済みレコードを除外するか */
    private bool $excludeSoftDeleted = true;

    /**
     * @param  string  $table  テーブル名
     * @param  string  $column  スラッグカラム名
     */
    public function __construct(
        private string $table,
        private string $column = 'slug',
    ) {}

    /**
     * 静的ファクトリーメソッド
     *
     * @param  string  $table  テーブル名
     * @param  string  $column  スラッグカラム名
     */
    public static function for(string $table, string $column = 'slug'): static
    {
        return new static($table, $column);
    }

    /**
     * 更新時に自身のレコードを除外する
     *
     * @param  int|string  $id  除外するレコードのID
     * @param  string  $idColumn  IDカラム名
     */
    public function ignore(int|string $id, string $idColumn = 'id'): static
    {
        $this->ignoreId = $id;
        $this->ignoreIdColumn = $idColumn;

        return $this;
    }

    /**
     * 追加WHERE条件を指定する
     *
     * @param  string  $column  カラム名
     * @param  mixed  $value  値
     */
    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = ['column' => $column, 'value' => $value];

        return $this;
    }

    /**
     * ソフトデリート除外を無効化する
     */
    public function withoutSoftDeletes(): static
    {
        $this->excludeSoftDeleted = false;

        return $this;
    }

    /**
     * バリデーション実行
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
