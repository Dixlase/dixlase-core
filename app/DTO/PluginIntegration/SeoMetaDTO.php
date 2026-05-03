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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * SEO meta information DTO for content units
 *
 * Immutable data object for passing SEO meta tags and OGP information
 * associated with plugin-generated pages (static pages, legal pages, blog posts, etc.)
 *
 * Current minimal fields: description, ogpMediaId
 * Plans to add title tag override and keywords in the future
 */
final readonly class SeoMetaDTO implements JsonSerializable
{
    /**
     * @param  string|null  $description  Meta description (null if not set)
     * @param  int|null  $ogpMediaId  Media ID for OGP image (null if not set)
     */
    public function __construct(
        public ?string $description = null,
        public ?int $ogpMediaId = null,
    ) {}

    /**
     * Determine if meta information is empty (all fields unset)
     */
    public function isEmpty(): bool
    {
        return $this->description === null
            && $this->ogpMediaId === null;
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'ogp_media_id' => $this->ogpMediaId,
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
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: isset($data['description']) ? (string) $data['description'] : null,
            ogpMediaId: isset($data['ogp_media_id']) ? (int) $data['ogp_media_id'] : null,
        );
    }
}
