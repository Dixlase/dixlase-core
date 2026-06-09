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

use JsonSerializable;

/**
 * Block metadata DTO
 *
 * Describes a Block without rendering it. Used by the future block registry,
 * admin block-picker UI, and PLUGIN-API consumers to enumerate available
 * blocks and their configuration shape.
 *
 * @see \App\Contracts\PluginIntegration\BlockProviderInterface
 */
final readonly class BlockDescriptor implements JsonSerializable
{
    /**
     * @param  string  $key  Unique block identifier (e.g. 'dixlase-blog.category_list')
     * @param  string  $label  Human-readable label (recommended: a translation key)
     * @param  string|null  $description  Short description shown in block pickers
     * @param  string|null  $icon  Icon identifier (e.g. heroicon name) for UI
     * @param  array<string, mixed>  $configSchema  JSON Schema-like config description
     *                                              (drives form generation + validation)
     * @param  array<string, mixed>  $defaultConfig  Default config values used when a new
     *                                               instance is added by the user
     * @param  array<int, string>  $usableIn  Surfaces where this block can be placed
     *                                        (one or more of BlockProviderInterface::SURFACE_*)
     * @param  string  $source  Origin slug (plugin or theme slug) for attribution
     * @param  array<string, mixed>  $meta  Extension-point metadata
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
        public ?string $icon = null,
        public array $configSchema = [],
        public array $defaultConfig = [],
        public array $usableIn = [],
        public string $source = '',
        public array $meta = [],
    ) {}

    /**
     * Whether this block declares it can be used in the GUI editor surface.
     */
    public function isUsableInEditor(): bool
    {
        return in_array('editor', $this->usableIn, true);
    }

    /**
     * Whether this block declares it can be used in theme widget areas.
     */
    public function isUsableInWidgetArea(): bool
    {
        return in_array('widget_area', $this->usableIn, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'config_schema' => $this->configSchema,
            'default_config' => $this->defaultConfig,
            'usable_in' => $this->usableIn,
            'source' => $this->source,
            'meta' => $this->meta,
        ];
    }
}
