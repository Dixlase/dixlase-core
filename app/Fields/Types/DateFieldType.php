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
use Carbon\CarbonImmutable;
use Throwable;

/**
 * A calendar date with no time and no timezone
 *
 * Stored and handed to the API as `Y-m-d`; the PHP value is a
 * CarbonImmutable at midnight.
 */
class DateFieldType extends AbstractCoreFieldType
{
    public const FORMAT = 'Y-m-d';

    public function key(): string
    {
        return 'date';
    }

    public function rules(FieldDefinition $field): array
    {
        $rules = ['date_format:'.self::FORMAT];

        foreach (['min' => 'after_or_equal', 'max' => 'before_or_equal'] as $option => $rule) {
            if ($this->parse($field->option($option)) !== null) {
                $rules[] = $rule.':'.$field->option($option);
            }
        }

        return $rules;
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        return $this->parse($input);
    }

    public function toStorage(mixed $value, FieldDefinition $field): mixed
    {
        return $value instanceof CarbonImmutable ? $value->format(self::FORMAT) : null;
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        return $this->parse($stored);
    }

    public function toApi(mixed $value, FieldDefinition $field): mixed
    {
        return $this->toStorage($value, $field);
    }

    private function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!'.self::FORMAT, $value);
        } catch (Throwable) {
            return null;
        }

        return $date !== null && $date->format(self::FORMAT) === $value ? $date : null;
    }
}
