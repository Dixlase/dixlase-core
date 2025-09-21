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
    'rows' => 10,
    'placeholder' => '',
    'required' => false,
    'readonly' => false,
    'class' => '',
    'xBindReadonly' => null,  // Alpine.jsのx-bind:readonly
    'xBindClass' => null,     // Alpine.jsのx-bind:class
])

<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    class="block w-full px-3 py-2 bg-white text-gray-700 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm focus:outline-none {{ $class }}"


    {{ $xBindReadonly ? "x-bind:readonly=$xBindReadonly" : '' }}
    {{ $xBindClass ? "x-bind:class=$xBindClass" : '' }}
>{{ old($name, $value) }}
</textarea>
