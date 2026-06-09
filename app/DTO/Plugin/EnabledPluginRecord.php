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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * DTO that holds basic information of an enabled plugin
 *
 * Pure value object that does not depend on the Plugin model.
 * Used as the return value of PluginRepositoryInterface::getEnabled().
 */
final readonly class EnabledPluginRecord implements JsonSerializable
{
    /**
     * @param  string  $name  Plugin name (e.g., DixlasePages)
     * @param  string  $directory  Plugin directory name (e.g., DixlasePages)
     * @param  string  $slug  Plugin slug (e.g., dixlase-pages)
     * @param  string  $description  Plugin description (e.g., "SEO optimization plugin")
     * @param  string  $version  Plugin version string (e.g., "0.1.0")
     */
    public function __construct(
        public string $name,
        public string $directory,
        public string $slug,
        public string $description = '',
        public string $version = '',
    ) {}

    /**
     * Create instance from array
     *
     * @param  array{name: string, directory: string, slug: string, description?: string, version?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            directory: $data['directory'],
            slug: $data['slug'],
            description: $data['description'] ?? '',
            version: $data['version'] ?? '',
        );
    }

    /**
     * @return array{name: string, directory: string, slug: string, description: string, version: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'directory' => $this->directory,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->version,
        ];
    }

    /**
     * @return array{name: string, directory: string, slug: string, description: string, version: string}
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
