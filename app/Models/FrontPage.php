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

namespace App\Models;

use App\Contracts\Content\PublishesFileContent;
use App\Contracts\Revisionable;
use App\Contracts\TranslationResolver;
use App\Enums\ContentEditorType;
use App\Enums\ContentPublishMode;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Helpers\LocaleHelper;
use App\Models\Traits\BelongsToSite;
use App\Services\FrontPageContentService;
use App\Traits\HasRevisions;
use App\Traits\TranslatableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Front page model
 */
class FrontPage extends Model implements PublishesFileContent, Revisionable
{
    use BelongsToSite;
    use HasFactory;
    use HasRevisions;
    use TranslatableTrait;

    /**
     * Translatable fields (consumed by core's TranslatableTrait +
     * TranslationResolver indirection — a translation provider binds the
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
     * Resolve the row rendered on the public front for a locale.
     *
     * Without a translation provider each locale is its own row (the
     * installer seeds one per shipped locale), so prefer the row whose
     * `lang` matches the request locale, then the site's primary locale,
     * then the first row. When a TranslationResolver is bound the model is
     * ONE canonical row plus per-locale translations overlaid by
     * FrontPageContentService, so the canonical row is kept — otherwise the
     * front would show an orphaned seeded row instead of the operator's
     * translation.
     */
    public static function findForFrontend(string $pageType, ?string $locale = null): ?self
    {
        if (App::bound(TranslationResolver::class)) {
            return static::findByType($pageType);
        }

        $rows = static::query()->where('page_type', $pageType)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return null;
        }

        foreach ([$locale ?? App::getLocale(), LocaleHelper::getSiteDefaultLocale()] as $candidate) {
            $match = $rows->firstWhere('lang', $candidate);
            if ($match !== null) {
                return $match;
            }
        }

        return $rows->first();
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

    /**
     * The front settings key holding the publish mode for file-stored content
     */
    public const PUBLISH_MODE_SETTING = 'publish_mode';

    public function usesFileStorage(): bool
    {
        return $this->storage_type === ContentStorageType::FILE;
    }

    public function fileContentPublishMode(): ContentPublishMode
    {
        return ContentPublishMode::fromSetting(FrontSetting::getValue(self::PUBLISH_MODE_SETTING));
    }

    /**
     * The body, plus the custom CSS and JS that only the HTML editor keeps
     *
     * @return array<string, string>
     */
    public function fileContentParts(): array
    {
        $parts = ['content' => 'content'];

        if ($this->editorType() === ContentEditorType::HTML) {
            $parts['custom_css'] = 'custom_css';
            $parts['custom_js'] = 'custom_js';
        }

        return $parts;
    }

    public function fileContentPath(string $part): string
    {
        $files = app(FrontPageContentService::class);
        $locale = (string) $this->lang;

        return match ($part) {
            'custom_css' => $files->getCssFilePath($this->page_type, $locale),
            'custom_js' => $files->getJsFilePath($this->page_type, $locale),
            default => $files->getFilePath($this->page_type, $locale, $this->editorType()->slug()),
        };
    }

    public function fileContentDisk(): string
    {
        return app(FrontPageContentService::class)->getDisk();
    }

    public function fileContentAuditPrefix(): string
    {
        return 'front_page';
    }

    /**
     * @return Builder<static>
     */
    public static function fileStoredQuery(): Builder
    {
        return static::query()->where('storage_type', ContentStorageType::FILE->value);
    }

    /**
     * The editor type as the enum the cast gives
     */
    private function editorType(): ContentEditorType
    {
        $editorType = $this->editor_type;

        return $editorType instanceof ContentEditorType ? $editorType : ContentEditorType::from((int) $editorType);
    }
}
