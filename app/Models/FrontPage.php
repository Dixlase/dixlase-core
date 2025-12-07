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

namespace App\Models;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use Illuminate\Database\Eloquent\Model;

class FrontPage extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'front_pages';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_type',
        'title',
        'content',
        'content_html',
        'content_markdown',
        'content_blade',
        'storage_type',
        'editor_type',
        'status',
    ];

    /**
     * キャストする属性
     *
     * @var array
     */
    protected $casts = [
        'storage_type' => ContentStorageType::class,
        'editor_type' => ContentEditorType::class,
    ];

    /**
     * ページタイプで取得
     */
    public static function findByType(string $pageType): ?self
    {
        return static::where('page_type', $pageType)->first();
    }

    /**
     * ページタイプで取得または作成
     */
    public static function findOrCreateByType(string $pageType): self
    {
        return static::firstOrCreate(
            ['page_type' => $pageType],
            [
                'storage_type' => 'database',
                'editor_type' => 'html',
                'status' => 'published',
            ]
        );
    }

    /**
     * 公開されているかチェック
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * 公開ページのスコープ
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
