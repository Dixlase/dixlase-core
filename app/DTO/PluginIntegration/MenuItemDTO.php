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
 * DTO for menu item
 *
 * Immutable data object for menu items
 * used by the menu plugin
 */
final readonly class MenuItemDTO implements JsonSerializable
{
    /**
     * @param  string  $label  Menu label (display name)
     * @param  string  $url  Menu URL
     * @param  string  $target  Link target ('_self', '_blank', '_parent', '_top')
     * @param  string|null  $sourceType  Source type ('custom', 'page', 'post', etc.)
     * @param  string|null  $sourceId  Source ID (plugin content ID)
     * @param  string|null  $sourceProvider  Source provider (plugin slug)
     * @param  string|null  $iconClass  Icon class (e.g., 'fas fa-home')
     * @param  string|null  $cssClass  CSS class
     * @param  int  $displayOrder  Display order
     * @param  bool  $isActive  Enabled/disabled
     * @param  array<string,mixed>  $meta  Additional metadata
     * @param  MenuItemDTO[]  $children  Child menu items
     */
    public function __construct(
        public string $label,
        public string $url,
        public string $target = '_self',
        public ?string $sourceType = 'custom',
        public ?string $sourceId = null,
        public ?string $sourceProvider = null,
        public ?string $iconClass = null,
        public ?string $cssClass = null,
        public int $displayOrder = 0,
        public bool $isActive = true,
        public array $meta = [],
        public array $children = [],
    ) {}

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'target' => $this->target,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'source_provider' => $this->sourceProvider,
            'icon_class' => $this->iconClass,
            'css_class' => $this->cssClass,
            'display_order' => $this->displayOrder,
            'is_active' => $this->isActive,
            'meta' => $this->meta,
            'children' => array_map(fn (self $child) => $child->jsonSerialize(), $this->children),
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
            label: $data['label'] ?? '',
            url: $data['url'] ?? '',
            target: $data['target'] ?? '_self',
            sourceType: $data['source_type'] ?? 'custom',
            sourceId: $data['source_id'] ?? null,
            sourceProvider: $data['source_provider'] ?? null,
            iconClass: $data['icon_class'] ?? null,
            cssClass: $data['css_class'] ?? null,
            displayOrder: $data['display_order'] ?? 0,
            isActive: $data['is_active'] ?? true,
            meta: $data['meta'] ?? [],
            children: array_map(fn (array $child) => self::fromArray($child), $data['children'] ?? []),
        );
    }

    /**
     * Create MenuItemDTO from LinkableDTO
     *
     * @param  string  $target  Link target
     */
    public static function fromLinkable(LinkableDTO $linkable, string $target = '_self'): self
    {
        return new self(
            label: $linkable->title,
            url: $linkable->url,
            target: $target,
            sourceType: $linkable->type,
            sourceId: $linkable->id,
            sourceProvider: $linkable->source,
        );
    }

    /**
     * Check if it is a custom URL
     */
    public function isCustomUrl(): bool
    {
        return $this->sourceType === 'custom' || $this->sourceId === null;
    }

    /**
     * Check if it is plugin content
     */
    public function isPluginContent(): bool
    {
        return $this->sourceType !== 'custom' && $this->sourceId !== null;
    }

    /**
     * Generate a new DTO with changed target
     *
     * @param  string  $target  New target
     */
    public function withTarget(string $target): self
    {
        return new self(
            label: $this->label,
            url: $this->url,
            target: $target,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            sourceProvider: $this->sourceProvider,
            iconClass: $this->iconClass,
            cssClass: $this->cssClass,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            meta: $this->meta,
            children: $this->children,
        );
    }

    /**
     * Generate a new DTO with changed label
     *
     * @param  string  $label  New label
     */
    public function withLabel(string $label): self
    {
        return new self(
            label: $label,
            url: $this->url,
            target: $this->target,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            sourceProvider: $this->sourceProvider,
            iconClass: $this->iconClass,
            cssClass: $this->cssClass,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            meta: $this->meta,
            children: $this->children,
        );
    }

    /**
     * Check if it has child menus
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Check if it is a menu group (dropdown container without URL)
     */
    public function isMenuGroup(): bool
    {
        return $this->sourceType === 'menu_group';
    }
}
