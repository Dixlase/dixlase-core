<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Contracts\Repositories;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * メディアリポジトリインターフェース
 */
interface MediaRepositoryInterface
{
    /**
     * IDでメディアを取得
     *
     * @param int $id
     * @return Media|null
     */
    public function find(int $id): ?Media;

    /**
     * IDでメディアを取得（リレーション付き）
     *
     * @param int $id
     * @param array<string> $relations
     * @return Media|null
     */
    public function findWithRelations(int $id, array $relations = ['member']): ?Media;

    /**
     * すべてのメディアを取得
     *
     * @param array<string> $relations
     * @return Collection
     */
    public function all(array $relations = []): Collection;

    /**
     * ページネーション付きでメディアを取得
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @param string $sortBy
     * @param string $sortOrder
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 25,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator;

    /**
     * メディアを作成
     *
     * @param array<string, mixed> $data
     * @return Media
     */
    public function create(array $data): Media;

    /**
     * メディアを更新
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * メディアを削除（ソフトデリート）
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * メディアを完全削除
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool;

    /**
     * 削除されたメディアを復元
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool;

    /**
     * 検索条件に一致するメディアを取得
     *
     * @param string $search
     * @param array<string> $relations
     * @return Collection
     */
    public function search(string $search, array $relations = ['member']): Collection;

    /**
     * タイプでフィルタリング
     *
     * @param string $type
     * @param array<string> $relations
     * @return Collection
     */
    public function filterByType(string $type, array $relations = ['member']): Collection;

    /**
     * アップロード者でフィルタリング
     *
     * @param int $memberId
     * @param array<string> $relations
     * @return Collection
     */
    public function filterByUploader(int $memberId, array $relations = ['member']): Collection;

    /**
     * 日付範囲でフィルタリング
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param array<string> $relations
     * @return Collection
     */
    public function filterByDateRange(?string $dateFrom, ?string $dateTo, array $relations = ['member']): Collection;

    /**
     * メディアが存在するか確認
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * メディア数を取得
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function count(array $filters = []): int;
}
