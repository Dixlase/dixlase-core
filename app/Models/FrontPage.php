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

namespace App\Models;

use App\Contracts\Revisionable;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\Traits\BelongsToSite;
use App\Traits\HasRevisions;
use App\Traits\TranslatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Front page model
 */
class FrontPage extends Model implements Revisionable
{
    use BelongsToSite;
    use HasFactory;
    use HasRevisions;
    use TranslatableTrait;

    /**
     * Translatable fields (consumed by core's TranslatableTrait +
     * TranslationResolver indirection — DixlaseMultilingual binds the
     * resolver to read / write per-locale rows in its polymorphic
     * translations table). When the multilingual plugin is absent the
     * trait silently falls back to the row's column value (the primary
     * locale stored on this row), so single-locale installs keep
     * working.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'content',
    ];

    /**
     * Table name
     */
    protected $table = 'front_pages';

    /**
     * Mass assignable attributes
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
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
     * Attributes to cast
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
     * Get by page type and language
     */
    public static function findByTypeAndLang(string $pageType, string $lang): ?self
    {
        return static::query()
            ->where('page_type', $pageType)
            ->where('lang', $lang)
            ->first();
    }

    /**
     * Get by page type (backward compatibility)
     */
    public static function findByType(string $pageType): ?self
    {
        return static::query()->where('page_type', $pageType)->first();
    }

    /**
     * Check if public
     */
    public function isPublished(): bool
    {
        return $this->status === ContentStatus::PUBLISHED;
    }

    /**
     * Scope for public pages
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
