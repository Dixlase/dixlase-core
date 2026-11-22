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
use App\Helpers\DateTimeHelper;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * A definite instant
 *
 * Drawn with <x-form-datetime>, so the editor types a wall clock in the
 * site's display timezone. It is stored and handed to the API as an ISO 8601
 * string in UTC; the PHP value is a CarbonImmutable in UTC.
 */
class DateTimeFieldType extends AbstractCoreFieldType
{
    public function key(): string
    {
        return 'datetime';
    }

    public function rules(FieldDefinition $field): array
    {
        return ['date'];
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        $utc = DateTimeHelper::fromInput($input);

        return $utc === null ? null : CarbonImmutable::parse($utc, 'UTC');
    }

    public function toStorage(mixed $value, FieldDefinition $field): mixed
    {
        return $value instanceof CarbonImmutable ? DateTimeHelper::toIsoUtc($value) : null;
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($stored)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    public function toApi(mixed $value, FieldDefinition $field): mixed
    {
        return $this->toStorage($value, $field);
    }
}
