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
    'label' => '', // チェックボックスのラベル
    'id' => '',            // チェックボックスのid
    'name' => '',          // チェックボックスの共通name
    'value' => 1,          // チェックボックスの値（デフォルト1）
    'checked' => false,    // チェック状態
    'class' => '',         // カスタムクラス
    'xModel' => null, // Alpine.jsのx-model属性
])

<div class="flex flex-wrap gap-4 my-4">
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $id ?: $name }}"
                name="{{ $name }}"
                value="1"
                {{ $xModel ? "x-model=$xModel" : '' }}
                class="{{ config('appearance.appearance_class.form.checkbox') }} {{ $class }}"
                @if ($value || $checked) checked @endif>
            <span class="ml-2 text-sm dark:text-white">{{ __($label) }}</span>
        </label>
</div>
