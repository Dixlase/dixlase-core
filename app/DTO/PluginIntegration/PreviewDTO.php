<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * @api Available as a stable API for plugins/themes
 *
 * Preview data DTO
 *
 * Holds all information for a previewable section provided by a plugin.
 * Supports multiple preview types: forms, content blocks, widgets.
 */
final readonly class PreviewDTO implements JsonSerializable
{
    /**
     * @param  string  $key  Unique preview key (e.g. 'inquiry_form')
     * @param  string  $type  Preview type ('form', 'content', 'widget')
     * @param  string  $title  Section title (pre-translated string)
     * @param  string|null  $description  Section description (pre-translated string)
     * @param  string  $source  Data source (plugin slug)
     * @param  PreviewFieldDTO[]  $fields  Form fields (when type='form')
     * @param  string|null  $submitLabel  Submit button label (when type='form')
     * @param  string|null  $submitIcon  Submit button icon class (when type='form')
     * @param  string|null  $renderedHtml  Plugin-generated complete HTML (for front page use)
     * @param  array<string,mixed>  $meta  Additional metadata
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $title,
        public ?string $description = null,
        public string $source = '',
        public array $fields = [],
        public ?string $submitLabel = null,
        public ?string $submitIcon = null,
        public ?string $renderedHtml = null,
        public array $meta = [],
    ) {}

    /**
     * Whether this is a form-type preview
     */
    public function isForm(): bool
    {
        return $this->type === 'form';
    }

    /**
     * Get fields grouped by their group name
     *
     * @return array<string|null, PreviewFieldDTO[]>
     */
    public function getFieldsByGroup(): array
    {
        $grouped = [];
        foreach ($this->fields as $field) {
            $grouped[$field->group][] = $field;
        }

        return $grouped;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'submit_label' => $this->submitLabel,
            'submit_icon' => $this->submitIcon,
            'field_count' => count($this->fields),
            'meta' => $this->meta,
        ];
    }
}
