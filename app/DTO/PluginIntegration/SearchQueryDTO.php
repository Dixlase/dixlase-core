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

namespace App\DTO\PluginIntegration;

/**
 * 検索クエリDTO
 *
 * プラグイン間でコンテンツ検索を行う際の
 * 検索条件を表現する不変データオブジェクトです。
 */
final readonly class SearchQueryDTO
{
    /**
     * @param  string  $q  検索キーワード
     * @param  int  $page  ページ番号（1始まり）
     * @param  int  $perPage  1ページあたりの件数
     * @param  array<string,scalar|array|null>  $filters  フィルタ条件
     * @param  array<string,'asc'|'desc'>  $sort  ソート条件
     */
    public function __construct(
        public string $q = '',
        public int $page = 1,
        public int $perPage = 20,
        public array $filters = [],
        public array $sort = [],
    ) {}

    /**
     * 検索キーワードが指定されているか
     */
    public function hasQuery(): bool
    {
        return $this->q !== '';
    }

    /**
     * 特定のフィルタが指定されているか
     *
     * @param  string  $key  フィルタキー
     */
    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    /**
     * フィルタ値を取得
     *
     * @param  string  $key  フィルタキー
     * @param  mixed  $default  デフォルト値
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * ソート条件が指定されているか
     */
    public function hasSort(): bool
    {
        return ! empty($this->sort);
    }

    /**
     * 配列からDTOを生成
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
     * リクエストからDTOを生成
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
     * 配列形式に変換
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
     * 検索キーワードを変更した新しいDTOを生成
     *
     * @param  string  $q  新しい検索キーワード
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
     * ページ番号を変更した新しいDTOを生成
     *
     * @param  int  $page  新しいページ番号
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
     * フィルタを追加した新しいDTOを生成
     *
     * @param  array<string,mixed>  $filters  追加するフィルタ
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
     * ソート条件を変更した新しいDTOを生成
     *
     * @param  array<string,'asc'|'desc'>  $sort  新しいソート条件
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
