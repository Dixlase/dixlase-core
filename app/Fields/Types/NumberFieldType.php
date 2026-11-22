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

/**
 * A number, whole when the `integer` option is set
 */
class NumberFieldType extends AbstractCoreFieldType
{
    public function key(): string
    {
        return 'number';
    }

    public function rules(FieldDefinition $field): array
    {
        $rules = [$this->isInteger($field) ? 'integer' : 'numeric'];

        if (is_numeric($field->option('min'))) {
            $rules[] = 'min:'.$field->option('min');
        }

        if (is_numeric($field->option('max'))) {
            $rules[] = 'max:'.$field->option('max');
        }

        return $rules;
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        return $this->toNumber($input, $field);
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        return $this->toNumber($stored, $field);
    }

    private function toNumber(mixed $value, FieldDefinition $field): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        return $this->isInteger($field) ? (int) $value : $value + 0;
    }

    private function isInteger(FieldDefinition $field): bool
    {
        return (bool) $field->option('integer', false);
    }
}
