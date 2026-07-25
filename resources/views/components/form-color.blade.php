{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-form-color />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
    'value' => '#000000',
    'label' => null,
    'disabled' => false,
    'required' => false,
    'help' => null,
])

<div x-data="colorPicker('{{ old($name, $value) }}')" class="space-y-2">
    @if ($label)
        <x-form-label :for="$id ?? $name" :required="$required">
            {{ $label }}
        </x-form-label>
    @endif

    <div class="flex items-center space-x-3">
        <input type="color"
            id="{{ $id ?? $name }}"
            name="{{ $name }}"
            x-model="color"
            @if ($disabled) disabled @endif
            @if ($required) required @endif
            class="h-10 w-20 rounded border border-gray-300 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
        >
        <input type="text"
            x-model="color"
            readonly
            class="block flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm text-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white"
        >
    </div>

    @if ($help)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    <x-form-error :name="$name" />
</div>
