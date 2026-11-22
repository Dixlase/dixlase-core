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

declare(strict_types=1);

namespace App\DTO\Api;

use InvalidArgumentException;
use JsonSerializable;

/**
 * API Resource DTO
 *
 * Unified data structure for making content resources provided by plugins
 * public via the API.
 *
 * Three places carry a resource's data, and each has one job:
 *
 *   - the named properties: what every resource has, page-like or not
 *   - `fields`: the resource's typed values, keyed by field key. Use
 *     FieldTypeRegistry::apiValues() to build it from stored custom fields.
 *     Values are JSON scalars, lists or objects. A media item is an object
 *     with at least `id` and `url`; another resource is an object with at
 *     least `type` and `id`
 *   - `meta`: provider-specific extras that are not part of the content
 *
 * `contentFormat` says what `content` holds, so a consumer knows whether to
 * print it or render it.
 *
 * @see docs/development/api-reference/resources.md
 */
final readonly class ApiResourceDTO implements JsonSerializable
{
    public const CONTENT_FORMAT_HTML = 'html';

    public const CONTENT_FORMAT_MARKDOWN = 'markdown';

    public const CONTENT_FORMAT_BLOCKS = 'blocks';

    /** @var list<string> */
    public const CONTENT_FORMATS = [
        self::CONTENT_FORMAT_HTML,
        self::CONTENT_FORMAT_MARKDOWN,
        self::CONTENT_FORMAT_BLOCKS,
    ];

    /**
     * @param  string  $id  Resource ID (ULID/UUID)
     * @param  string  $type  Resource type ('page', 'post', etc.)
     * @param  string  $slug  Slug
     * @param  string|null  $title  Title
     * @param  string|null  $content  Body, in the format $contentFormat names
     * @param  string|null  $excerpt  Excerpt
     * @param  string|null  $metaDescription  Meta description
     * @param  string  $status  Status ('published', 'draft', 'scheduled')
     * @param  string|null  $publishedAt  Published date and time (ISO 8601)
     * @param  string|null  $updatedAt  Updated date and time (ISO 8601)
     * @param  string|null  $url  Front URL
     * @param  string  $source  Plugin slug
     * @param  array<string, mixed>  $meta  Provider-specific extras, not content
     * @param  string|null  $contentFormat  One of CONTENT_FORMATS, or null when unspecified
     * @param  array<string, mixed>  $fields  Typed values keyed by field key
     *
     * @throws InvalidArgumentException when $contentFormat is not one of CONTENT_FORMATS
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
        public ?string $contentFormat = null,
        public array $fields = [],
    ) {
        if ($contentFormat !== null && ! in_array($contentFormat, self::CONTENT_FORMATS, true)) {
            throw new InvalidArgumentException(
                "Unknown content format [{$contentFormat}]; expected one of: ".implode(', ', self::CONTENT_FORMATS).'.'
            );
        }
    }

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
            'content_format' => $this->contentFormat,
            'excerpt' => $this->excerpt,
            'meta_description' => $this->metaDescription,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'updated_at' => $this->updatedAt,
            'url' => $this->url,
            'source' => $this->source,
            'fields' => (object) $this->fields,
            'meta' => $this->meta,
        ];
    }
}
