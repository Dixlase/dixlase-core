{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'name' => '',          // チェックボックスの共通name
    'options' => [],       // 選択肢（キー: name, 値: label）
    'values' => [],        // 現在の選択値
    'class' => '',         // カスタムクラス
    'theme' => 'light',  // テーマ
])

@php
    $theme_class = $theme === 'light'
        ? 'text-gray-600'
        : 'text-gray-600';
@endphp

<div class="flex flex-wrap gap-4">
    @foreach ($options as $optionName => $optionLabel)
        <input type="hidden" name="{{ $name }}[{{ $optionName }}]" value="0">
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $optionName }}"
                name="{{ $name }}[{{ $optionName }}]"
                value="1"
                class="{{ $theme_class }} {{ $class }}"
                @if (!empty($values[$optionName])) checked @endif>
            <span class="ml-2">{{ $optionLabel }}</span>
        </label>
    @endforeach
</div>
