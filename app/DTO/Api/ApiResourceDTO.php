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

declare(strict_types=1);

namespace App\DTO\Api;

use JsonSerializable;

/**
 * APIリソースDTO
 *
 * プラグインが提供するコンテンツリソースをAPI経由で公開する際の
 * 統一データ構造です。
 */
final readonly class ApiResourceDTO implements JsonSerializable
{
    /**
     * @param  string  $id  リソースID（ULID/UUID）
     * @param  string  $type  リソースタイプ（'page', 'post' 等）
     * @param  string  $slug  スラッグ
     * @param  string|null  $title  タイトル
     * @param  string|null  $content  HTML本文
     * @param  string|null  $excerpt  抜粋
     * @param  string|null  $metaDescription  メタディスクリプション
     * @param  string  $status  ステータス（'published', 'draft', 'scheduled'）
     * @param  string|null  $publishedAt  公開日時（ISO 8601）
     * @param  string|null  $updatedAt  更新日時（ISO 8601）
     * @param  string|null  $url  フロントURL
     * @param  string  $source  プラグインスラッグ
     * @param  array<string, mixed>  $meta  拡張メタデータ
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $slug,
        public ?string $title,
        public ?string $content,
        public ?string $excerpt,
        public ?string $metaDescription,
        public string $status,
        public ?string $publishedAt,
        public ?string $updatedAt,
        public ?string $url,
        public string $source,
        public array $meta = [],
    ) {}

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'meta_description' => $this->metaDescription,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'updated_at' => $this->updatedAt,
            'url' => $this->url,
            'source' => $this->source,
            'meta' => $this->meta,
        ];
    }
}
