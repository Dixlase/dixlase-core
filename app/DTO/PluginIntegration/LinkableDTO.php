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

namespace App\DTO\PluginIntegration;

use App\Contracts\PluginIntegration\LinkableInterface;
use JsonSerializable;

/**
 * DTO for linkable content
 *
 * Immutable data object for passing content information
 * between plugins
 */
final readonly class LinkableDTO implements JsonSerializable, LinkableInterface
{
    /**
     * @param  string  $id  Content ID (ULID/UUID)
     * @param  string  $title  Content title
     * @param  string  $url  Content URL
     * @param  string  $type  Content type ('post', 'page', 'media', etc.)
     * @param  string  $source  Data source ('core' or plugin slug)
     * @param  string|null  $sourceTable  Source table name (optional)
     * @param  string|null  $locale  Locale ('ja', 'en', etc.)
     * @param  array<string,mixed>  $meta  Additional metadata
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $url,
        public string $type,
        public string $source,
        public ?string $sourceTable = null,
        public ?string $locale = null,
        public array $meta = [],
    ) {}

    /**
     * Get content ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get content title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get content URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get content type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get data source
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get source table name
     */
    public function getSourceTable(): ?string
    {
        return $this->sourceTable;
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type,
            'source' => $this->source,
            'source_table' => $this->sourceTable,
            'locale' => $this->locale,
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Determine if content is from Core
     */
    public function isCore(): bool
    {
        return $this->source === 'core';
    }

    /**
     * Determine if this is plugin content
     */
    public function isPlugin(): bool
    {
        return $this->source !== 'core';
    }

    /**
     * Determine if this is content from a specific plugin
     *
     * @param  string  $pluginSlug  Plugin slug
     */
    public function isFromPlugin(string $pluginSlug): bool
    {
        return $this->source === $pluginSlug;
    }

    /**
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            url: $data['url'],
            type: $data['type'],
            source: $data['source'],
            sourceTable: $data['source_table'] ?? null,
            locale: $data['locale'] ?? null,
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * Create new DTO with changed title (immutable object pattern)
     *
     * @param  string  $title  New title
     */
    public function withTitle(string $title): self
    {
        return new self(
            id: $this->id,
            title: $title,
            url: $this->url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: $this->meta,
        );
    }

    /**
     * Create new DTO with changed URL (immutable object pattern)
     *
     * @param  string  $url  New URL
     */
    public function withUrl(string $url): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            url: $url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: $this->meta,
        );
    }

    /**
     * Create new DTO with added metadata (immutable object pattern)
     *
     * @param  array<string,mixed>  $meta  Metadata to add
     */
    public function withMeta(array $meta): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            url: $this->url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: array_merge($this->meta, $meta),
        );
    }
}
