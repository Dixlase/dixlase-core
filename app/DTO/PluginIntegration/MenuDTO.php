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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * Menu DTO
 *
 * Represents a navigation menu with its items.
 * Used by MenuProviderInterface to provide menu data to themes.
 */
final readonly class MenuDTO implements JsonSerializable
{
    /**
     * @param  int|string  $id  Menu identifier
     * @param  string  $name  Menu display name
     * @param  string  $slug  Menu slug
     * @param  MenuItemDTO[]  $items  Root-level menu items
     * @param  array<string,mixed>  $meta  Additional metadata
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public string $slug,
        public array $items = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'items' => array_map(fn (MenuItemDTO $item) => $item->jsonSerialize(), $this->items),
            'meta' => $this->meta,
        ];
    }
}
