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

use App\DTO\Fields\FieldDefinition;
use Illuminate\Validation\Rule;

/**
 * One value out of the `choices` option (value => label)
 *
 * The stored and API value is the choice's value, never its label, so a
 * label can be reworded without touching stored content.
 */
class SelectFieldType extends AbstractCoreFieldType
{
    public function key(): string
    {
        return 'select';
    }

    public function rules(FieldDefinition $field): array
    {
        return ['string', Rule::in(array_map('strval', array_keys($this->choices($field))))];
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        return (string) $input;
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        if (! is_scalar($stored)) {
            return null;
        }

        $stored = (string) $stored;

        // A choice removed from the definition after it was stored is no
        // longer a value this field can hold.
        return array_key_exists($stored, $this->choices($field)) ? $stored : null;
    }

    /**
     * @return array<string, string>
     */
    public function choices(FieldDefinition $field): array
    {
        $choices = $field->option('choices', []);

        return is_array($choices) ? $choices : [];
    }
}
