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
    'disabled' => false,
    'class' => '',
    'required' => false,
])

<input type="email"
    id="{{ $id }}"
    name="{{ $name }}"
    value="{{ old($name, $value) }}"
    @if ($disabled) disabled @endif
    @if ($required) required @endif
    class="mt-1 block w-full rounded-md shadow-sm text-lg {{ config('appearance.appearance_class.form.text') }} {{ $class }}"
>