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
 * On or off
 *
 * <x-form-toggle> always submits "0" or "1", so an untouched toggle is off,
 * never empty. A required toggle therefore accepts off as well; use
 * `accepted` in an extension's own rules when on is mandatory.
 */
class ToggleFieldType extends AbstractCoreFieldType
{
    public function key(): string
    {
        return 'toggle';
    }

    public function rules(FieldDefinition $field): array
    {
        return ['boolean'];
    }

    public function fromInput(mixed $input, FieldDefinition $field): mixed
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    public function fromStorage(mixed $stored, FieldDefinition $field): mixed
    {
        return is_bool($stored) ? $stored : filter_var($stored, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
