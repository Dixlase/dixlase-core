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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * Block render context DTO
 *
 * Passed to BlockProviderInterface::render() so that the same Block can adapt
 * its output between the GUI editor, a theme widget area, and admin preview.
 *
 * @see \App\Contracts\PluginIntegration\BlockProviderInterface
 */
final readonly class BlockContext implements JsonSerializable
{
    /**
     * @param  string  $surface  Where the block is being rendered
     *                           (one of BlockProviderInterface::SURFACE_*)
     * @param  string|null  $areaName  Theme widget area name when surface = widget_area (e.g. 'sidebar')
     * @param  int|null  $siteId  Current site id (multisite aware); null in CLI / non-site contexts
     * @param  array<string, mixed>  $meta  Additional surface-specific metadata
     */
    public function __construct(
        public string $surface,
        public ?string $areaName = null,
        public ?int $siteId = null,
        public array $meta = [],
    ) {}

    /**
     * Whether this render is for the GUI editor surface.
     */
    public function isEditor(): bool
    {
        return $this->surface === 'editor';
    }

    /**
     * Whether this render is for a theme widget area.
     */
    public function isWidgetArea(): bool
    {
        return $this->surface === 'widget_area';
    }

    /**
     * Whether this render is for admin preview.
     */
    public function isPreview(): bool
    {
        return $this->surface === 'preview';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'surface' => $this->surface,
            'area_name' => $this->areaName,
            'site_id' => $this->siteId,
            'meta' => $this->meta,
        ];
    }
}
