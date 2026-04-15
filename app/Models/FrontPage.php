<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Contracts\Revisionable;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Traits\HasRevisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * フロントページモデル
 */
class FrontPage extends Model implements Revisionable
{
    use HasFactory;
    use HasRevisions;

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
        'lang',
        'title',
        'content',
        'custom_js',
        'custom_css',
        'storage_type',
        'editor_type',
        'status',
    ];

    /**
     * キャストする属性
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'storage_type' => ContentStorageType::class,
            'editor_type' => ContentEditorType::class,
            'status' => ContentStatus::class,
        ];
    }

    /**
     * ページタイプと言語で取得
     */
    public static function findByTypeAndLang(string $pageType, string $lang): ?self
    {
        return static::query()
            ->where('page_type', $pageType)
            ->where('lang', $lang)
            ->first();
    }

    /**
     * ページタイプで取得（後方互換）
     */
    public static function findByType(string $pageType): ?self
    {
        return static::query()->where('page_type', $pageType)->first();
    }

    /**
     * 公開されているかチェック
     */
    public function isPublished(): bool
    {
        return $this->status === ContentStatus::PUBLISHED;
    }

    /**
     * 公開ページのスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::PUBLISHED->value);
    }

    public function revisionModel(): string
    {
        return FrontPageRevision::class;
    }

    public function revisionForeignKey(): string
    {
        return 'front_page_id';
    }

    /**
     * @return list<string>
     */
    public function revisionableFields(): array
    {
        return [
            'page_type',
            'lang',
            'title',
            'content',
            'custom_js',
            'custom_css',
            'storage_type',
            'editor_type',
            'status',
        ];
    }
}
