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

namespace App\Repositories;

use App\Contracts\Repositories\MediaRepositoryInterface;
use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * メディアリポジトリ実装
 */
class MediaRepository implements MediaRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Media
    {
        return Media::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(int $id, array $relations = ['member']): ?Media
    {
        return Media::with($relations)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $relations = []): Collection
    {
        $query = Media::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(
        int $perPage = 25,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {
        $query = Media::with('member');

        // 検索フィルター
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // タイプフィルター
        if (! empty($filters['type'])) {
            $query->where('type', 'like', $filters['type'].'%');
        }

        // アップロード者フィルター
        if (! empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        // 日付範囲フィルター
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // ソート
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $media = $this->find($id);

        if (! $media) {
            return false;
        }

        return $media->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $media = $this->find($id);

        if (! $media) {
            return false;
        }

        return $media->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(int $id): bool
    {
        $media = Media::withTrashed()->find($id);

        if (! $media) {
            return false;
        }

        return $media->forceDelete();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(int $id): bool
    {
        $media = Media::withTrashed()->find($id);

        if (! $media) {
            return false;
        }

        return $media->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $search, array $relations = ['member']): Collection
    {
        return Media::with($relations)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function filterByType(string $type, array $relations = ['member']): Collection
    {
        return Media::with($relations)
            ->where('type', 'like', $type.'%')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function filterByUploader(int $memberId, array $relations = ['member']): Collection
    {
        return Media::with($relations)
            ->where('uploaded_by', $memberId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function filterByDateRange(?string $dateFrom, ?string $dateTo, array $relations = ['member']): Collection
    {
        $query = Media::with($relations);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(int $id): bool
    {
        return Media::where('id', $id)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $filters = []): int
    {
        $query = Media::query();

        // フィルター適用
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', 'like', $filters['type'].'%');
        }

        if (! empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        return $query->count();
    }
}
