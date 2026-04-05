{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-select />

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
    'id' => null,            // selectのid属性
    'name' => null,          // selectのname属性
    'options' => [],         // 選択肢の配列
    'value' => null,         // 初期選択値
    'disabled' => false,     // 無効フラグ
    'class' => '',           // 追加クラス
    'required' => false,     // 必須フラグ
    'xModel' => null,        // Alpine.js x-model属性
    'onchange' => null,      // onchangeイベント
    'style' => null,         // インラインスタイル
    'useDefaultClass' => true, // デフォルトクラスを使用するか
])

@php
    $defaultClass = $useDefaultClass 
        ? 'block w-full max-w-full p-2 pr-10 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-white'
        : '';
    $finalClass = trim($defaultClass . ' ' . $class);
@endphp

<select @if($id) id="{{ $id }}" @endif
        name="{{ $name }}"
        class="input-common {{ $finalClass }}"
        @if ($disabled) disabled @endif
        @if ($required) required @endif
        @if ($xModel) x-model="{{ $xModel }}" @endif
        @if ($onchange) onchange="{{ $onchange }}" @endif
        @if ($style) style="{{ $style }}" @endif>
    @foreach ($options as $optionValue => $optionText)
        <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
            {{ __($optionText) }}
        </option>
    @endforeach
</select>
