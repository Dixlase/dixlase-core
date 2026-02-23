<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\App\Models;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plugins\DixlaseLegal\Database\Factories\DixlaseLegalPageFactory;

/**
 * 法務ページコンテンツモデル
 *
 * @property int $id
 * @property string $slug ページ種別スラッグ
 * @property string $lang 言語コード
 * @property string|null $title ページタイトル
 * @property string|null $content_html HTMLコンテンツ
 * @property string|null $content_markdown Markdownコンテンツ
 * @property ContentEditorType $editor_type エディタータイプ
 * @property ContentStatus $status ステータス
 * @property \Illuminate\Support\Carbon|null $published_at 公開日時
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class DixlaseLegalPage extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var string テーブル名 */
    protected $table = 'plg_dixlase_legal_pages';

    /** @var list<string> 一括代入可能属性 */
    protected $fillable = [
        'slug',
        'lang',
        'title',
        'content_html',
        'content_markdown',
        'editor_type',
        'status',
        'published_at',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'editor_type' => ContentEditorType::class,
            'status' => ContentStatus::class,
        ];
    }

    /**
     * ファクトリインスタンスを生成
     */
    protected static function newFactory(): DixlaseLegalPageFactory
    {
        return DixlaseLegalPageFactory::new();
    }

    /**
     * 公開済みコンテンツのスコープ
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::PUBLISHED->value);
    }

    /**
     * 指定スラッグのスコープ
     */
    public function scopeForSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * 指定言語のスコープ
     */
    public function scopeForLang(Builder $query, string $lang): Builder
    {
        return $query->where('lang', $lang);
    }

    /**
     * エディタータイプに応じたコンテンツを取得
     */
    public function getContentByEditorType(): ?string
    {
        return match ($this->editor_type) {
            ContentEditorType::MARKDOWN => $this->content_markdown,
            default => $this->content_html,
        };
    }

    /**
     * 公開済みかどうかを判定
     */
    public function isPublished(): bool
    {
        return $this->status === ContentStatus::PUBLISHED;
    }
}
