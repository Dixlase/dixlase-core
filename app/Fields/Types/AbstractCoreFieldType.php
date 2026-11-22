<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Fields\Types;

use App\Contracts\PluginIntegration\FieldTypeInterface;
use App\DTO\Fields\FieldDefinition;

/**
 * What the core field types share: they belong to core, are always
 * available, and draw their input from resources/views/fields/<key>.blade.php
 *
 * Each type is its own class. PluginServiceResolver drops a second instance
 * of a class it has already seen under the same slug, so one class
 * parametrised by key would collapse to a single type.
 */
abstract class AbstractCoreFieldType implements FieldTypeInterface
{
    public const CORE_SLUG = 'core';

    public function getPluginSlug(): string
    {
        return self::CORE_SLUG;
    }

    public function isCapabilityAvailable(): bool
    {
        return true;
    }

    public function label(): string
    {
        return __('components/form-field.types.'.$this->key());
    }

    public function view(): string
    {
        return 'fields.'.$this->key();
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        return $input;
    }

    public function toStorage(mixed $value, FieldDefinition $field): mixed
    {
        return $value;
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        return $stored;
    }

    public function toApi(mixed $value, FieldDefinition $field): mixed
    {
        return $value;
    }

    /**
     * Rules from the `min_length` / `max_length` options
     *
     * @return list<string>
     */
    protected function lengthRules(FieldDefinition $field): array
    {
        $rules = [];

        if (is_numeric($field->option('min_length'))) {
            $rules[] = 'min:'.(int) $field->option('min_length');
        }

        if (is_numeric($field->option('max_length'))) {
            $rules[] = 'max:'.(int) $field->option('max_length');
        }

        return $rules;
    }
}
