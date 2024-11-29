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
    'id' => null, // selectのid属性
    'name' => null, // selectのname属性
    'options' => [], // 選択肢の配列
    'value' => null, // 初期選択値
    'class' => 'default-class', // 追加クラス
    'required' => false, // 必須フラグ
    'theme' => 'light', // テーマ
])

@php
    $theme_class = $theme === 'light' ? 'bg-white' : 'bg-gray-900';
@endphp

<select id="{{ $id }}"
        name="{{ $name }}"
        class="mt-1 block rounded-md shadow-sm {{ $theme_class }} {{ $class }}"
        @if ($required) required @endif>
    @foreach ($options as $optionValue => $optionText)
        <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
            {{ __($optionText) }}
        </option>
    @endforeach
</select>
