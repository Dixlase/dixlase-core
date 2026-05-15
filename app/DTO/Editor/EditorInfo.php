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

namespace App\DTO\Editor;

use App\Contracts\Plugin\EditorCapableInterface;
use JsonSerializable;

/**
 * DTO for editor information
 *
 * Data transfer object for passing EditorCapableInterface information to views
 */
final readonly class EditorInfo implements JsonSerializable
{
    /**
     * @param  string  $typeSlug  Editor type slug (e.g. 'gui')
     * @param  string  $label  Localized display label
     * @param  string  $description  Localized description
     * @param  string  $icon  Font Awesome icon class
     * @param  string  $viewName  Blade view name for editor panel
     * @param  string  $pluginDirectory  Plugin directory name for asset loading
     * @param  string  $pluginSlug  Plugin slug identifier
     * @param  array{js: array<string>, css: array<string>}  $assets  Asset files to load
     * @param  string  $contentFormat  Content storage format (e.g. 'json')
     */
    public function __construct(
        public string $typeSlug,
        public string $label,
        public string $description,
        public string $icon,
        public string $viewName,
        public string $pluginDirectory,
        public string $pluginSlug,
        public array $assets,
        public string $contentFormat,
    ) {}

    /**
     * Create from EditorCapableInterface instance
     */
    public static function fromCapability(EditorCapableInterface $capability): self
    {
        $locale = app()->getLocale();
        $labels = $capability->getEditorLabel();
        $descriptions = $capability->getEditorDescription();

        return new self(
            typeSlug: $capability->getEditorTypeSlug(),
            label: $labels[$locale] ?? $labels['en'] ?? '',
            description: $descriptions[$locale] ?? $descriptions['en'] ?? '',
            icon: $capability->getEditorIcon(),
            viewName: $capability->getEditorViewName(),
            pluginDirectory: $capability->getPluginDirectoryName(),
            pluginSlug: $capability->getPluginSlug(),
            assets: $capability->getEditorAssets(),
            contentFormat: $capability->getContentFormat(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type_slug' => $this->typeSlug,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'plugin_slug' => $this->pluginSlug,
            'content_format' => $this->contentFormat,
        ];
    }
}
