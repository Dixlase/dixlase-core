{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'id' => null,
    'name' => null,
    'value' => '',
    'type' => 'text',
    'disabled' => false,
    'class' => '',
    'step' => null,
    'min' => null,
    'max' => null,
])

<input type="{{ $type }}"
    id="{{ $id }}"
    name="{{ $name }}"
    @if ($disabled) disabled @endif
    @if ($step) step="{{ $step }}" @endif
    @if ($min !== null) min="{{ $min }}" @endif
    @if ($max !== null) max="{{ $max }}" @endif
    class="mt-1 block w-full rounded-md shadow-sm text-lg {{ config('admin.appearance_class.form.text') }} {{ $class }}"
    value="{{ old($name, $value) }}"
    >
