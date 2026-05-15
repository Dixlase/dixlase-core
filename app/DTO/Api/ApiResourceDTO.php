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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
 * API Resource DTO
 *
 * Unified data structure for making content resources provided by plugins public via API
 * Unified data structure
 */
final readonly class ApiResourceDTO implements JsonSerializable
{
    /**
     * @param  string  $id  Resource ID (ULID/UUID)
     * @param  string  $type  Resource type ('page', 'post', etc.)
     * @param  string  $slug  Slug
     * @param  string|null  $title  Title
     * @param  string|null  $content  HTML body
     * @param  string|null  $excerpt  Excerpt
     * @param  string|null  $metaDescription  Meta description
     * @param  string  $status  Status ('published', 'draft', 'scheduled')
     * @param  string|null  $publishedAt  Published date and time (ISO 8601)
     * @param  string|null  $updatedAt  Updated date and time (ISO 8601)
     * @param  string|null  $url  Front URL
     * @param  string  $source  Plugin slug
     * @param  array<string, mixed>  $meta  Extended metadata
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
     * Serialize to JSON format
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
