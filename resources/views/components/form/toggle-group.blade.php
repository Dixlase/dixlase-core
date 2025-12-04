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
    'name' => '',
    'options' => [],
    'values' => [],
    'disabled' => false,
    'class' => '',
    'flexDirection' => 'col',
])

<div @class([
    'flex',
    'gap-3',
    'flex-col' => $flexDirection == 'col',
    'flex-row flex-wrap' => $flexDirection == 'row',
    $class,
])>
    @foreach ($options as $option_value => $option_label)
        @php
            $isChecked = in_array($option_value, $values);
            $toggleId = $name . '_' . $option_value;
        @endphp
        <div class="flex items-center space-x-3">
            <label for="{{ $toggleId }}" class="relative inline-flex items-center {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                <input type="checkbox"
                       id="{{ $toggleId }}"
                       name="{{ $name }}[]"
                       value="{{ $option_value }}"
                       {{ $isChecked ? 'checked' : '' }}
                       @if($disabled) disabled @endif
                       class="sr-only peer">
                <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none
                    {{ $disabled && $isChecked ? 'bg-indigo-900 dark:bg-indigo-900' : '' }}
                    {{ $disabled && !$isChecked ? 'bg-gray-300 dark:bg-gray-700' : '' }}
                    {{ !$disabled ? 'bg-gray-200 dark:bg-gray-600 peer-checked:bg-indigo-600' : '' }}
                "></div>
                <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
            </label>
            <span class="text-sm {{ $disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}">
                {{ __($option_label) }}
            </span>
        </div>
    @endforeach
</div>
