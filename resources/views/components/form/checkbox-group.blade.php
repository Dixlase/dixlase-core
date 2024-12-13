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
    'disabled' => false,   // チェックボックスを無効にする
    'class' => '',         // カスタムクラス
])

<div class="flex flex-wrap gap-4">
    @foreach ($options as $option_name => $option_label)
        <input type="hidden" name="{{ $name }}[{{ $option_name }}]" value="0">
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $option_name }}"
                name="{{ $name }}[{{ $option_name }}]"
                value="1"
                @if ($disabled) disabled @endif
                class="{{ config('admin.appearance_class.form.checkbox') }} {{ $class }}"
                @if (!empty($values[$option_name])) checked @endif>
            <span class="ml-2">{{ __($option_label) }}</span>
        </label>
    @endforeach
</div>
