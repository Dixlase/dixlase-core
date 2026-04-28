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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * ページネーション結果DTO
 *
 * プラグイン間で検索結果を受け渡しする際の
 * ページネーション情報を含む不変データオブジェクトです。
 *
 * @template T of JsonSerializable
 */
final readonly class PaginatedResultDTO implements JsonSerializable
{
    /**
     * @param  array<T>  $items  検索結果アイテム
     * @param  int  $total  総件数
     * @param  int  $page  現在のページ番号
     * @param  int  $perPage  1ページあたりの件数
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * 総ページ数を取得
     */
    public function totalPages(): int
    {
        if ($this->perPage <= 0) {
            return 0;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * 次のページが存在するか
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    /**
     * 前のページが存在するか
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * 現在のページの開始位置（1始まり）
     */
    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->page - 1) * $this->perPage + 1;
    }

    /**
     * 現在のページの終了位置
     */
    public function to(): int
    {
        $to = $this->page * $this->perPage;

        return min($to, $this->total);
    }

    /**
     * 結果が空かどうか
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * 結果が存在するかどうか
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * JSON形式にシリアライズ
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
     * 配列形式に変換
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Laravelのページネータから生成
     *
     * @param  callable|null  $transformer  アイテム変換関数
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
     * 配列から生成
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
     * アイテムを変換した新しいDTOを生成
     *
     * @param  callable  $callback  変換関数
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
     * アイテムをフィルタした新しいDTOを生成
     *
     * @param  callable  $callback  フィルタ関数
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
