{{--
This file is part of Dixlase.

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
    'xModel' => null,         // Alpine.jsのx-model
])

<textarea
    name="{{ $name }}"
    @if($id) id="{{ $id }}" @endif
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    class="block w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 dark:text-white {{ $class }}"
    @if($required) required @endif
    @if($readonly) readonly @endif
    {{ $xBindReadonly ? "x-bind:readonly=$xBindReadonly" : '' }}
    {{ $xBindClass ? "x-bind:class=$xBindClass" : '' }}
    {{ $xModel ? "x-model=$xModel" : '' }}
>@if(!$xModel){{ $name ? (is_string($oldVal = old($name, $value)) ? $oldVal : $value) : $value }}@endif</textarea>
