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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * メディアリポジトリインターフェース
 */
interface MediaRepositoryInterface
{
    /**
     * IDでメディアを取得
     */
    public function find(int $id): ?Media;

    /**
     * IDでメディアを取得（リレーション付き）
     *
     * @param  array<string>  $relations
     */
    public function findWithRelations(int $id, array $relations = ['member']): ?Media;

    /**
     * すべてのメディアを取得
     *
     * @param  array<string>  $relations
     */
    public function all(array $relations = []): Collection;

    /**
     * ページネーション付きでメディアを取得
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
     * メディアを作成
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Media;

    /**
     * メディアを更新
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * メディアを削除（ソフトデリート）
     */
    public function delete(int $id): bool;

    /**
     * メディアを完全削除
     */
    public function forceDelete(int $id): bool;

    /**
     * 削除されたメディアを復元
     */
    public function restore(int $id): bool;

    /**
     * 検索条件に一致するメディアを取得
     *
     * @param  array<string>  $relations
     */
    public function search(string $search, array $relations = ['member']): Collection;

    /**
     * タイプでフィルタリング
     *
     * @param  array<string>  $relations
     */
    public function filterByType(string $type, array $relations = ['member']): Collection;

    /**
     * アップロード者でフィルタリング
     *
     * @param  array<string>  $relations
     */
    public function filterByUploader(int $memberId, array $relations = ['member']): Collection;

    /**
     * 日付範囲でフィルタリング
     *
     * @param  array<string>  $relations
     */
    public function filterByDateRange(?string $dateFrom, ?string $dateTo, array $relations = ['member']): Collection;

    /**
     * メディアが存在するか確認
     */
    public function exists(int $id): bool;

    /**
     * メディア数を取得
     *
     * @param  array<string, mixed>  $filters
     */
    public function count(array $filters = []): int;
}
