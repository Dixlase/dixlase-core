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
    'name' => '',
    'options' => [],
    'values' => [],
    'disabled' => false,
    'class' => '',
    'flexDirection' => 'row'
])

<div @class([
    'flex',
    'flex-wrap',
    'gap-4',
    'flex-col' => $flexDirection == 'col',
    'flex-row' => $flexDirection != 'col',
])>
    @foreach ($options as $option_value => $option_label)
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $name }}_{{ $option_value }}"
                name="{{ $name }}[]"
                value="{{ $option_value }}"
                @if ($disabled) disabled @endif
                class="{{ config('appearance.appearance_class.form.checkbox') }} {{ $class }}"
                @if (in_array($option_value, $values)) checked @endif>
            <span class="ml-2">{{ __($option_label) }}</span>
        </label>
    @endforeach
</div>
