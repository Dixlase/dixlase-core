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

/**
 * @api Available as a stable API for plugins/themes
 *
 * Preview form field DTO
 *
 * Describes an individual field in a plugin's form for preview rendering.
 * Themes can render an accurate form representation using only this DTO.
 */
final readonly class PreviewFieldDTO
{
    /**
     * @param  string  $name  Field name (e.g. 'last_name')
     * @param  string  $type  Field type ('text', 'email', 'tel', 'textarea', 'select', 'radio_card', 'checkbox')
     * @param  string  $label  Display label (pre-translated string)
     * @param  bool  $required  Whether the field is required
     * @param  string|null  $placeholder  Placeholder text (pre-translated string)
     * @param  string|null  $group  Field group name (fields in the same group are displayed side by side)
     * @param  int  $order  Display order
     * @param  array<string,string>  $options  Options for select/radio (value => label)
     * @param  array<string,mixed>  $meta  Additional metadata (maxlength, icon, rows, etc.)
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $label,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $group = null,
        public int $order = 0,
        public array $options = [],
        public array $meta = [],
    ) {}
}
