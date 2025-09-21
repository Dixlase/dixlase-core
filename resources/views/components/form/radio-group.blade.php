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
    'name' => '',      // radioのname属性
    'options' => [],   // 選択肢の配列
    'value' => '',     // 現在の選択値
    'disabled' => false, // 無効にする
    'flexDirection' => 'row',
    'class' => '',     // カスタムクラス
])

<div @class([
    'flex',
    'flex-wrap',
    'gap-4',
    'mb-4',
    'flex-col' => $flexDirection == 'col',
    'flex-row' => $flexDirection != 'col',
])>
    @foreach ($options as $option_value => $option_label)
        <label class="inline-flex items-center">
            <input type="radio"
                name="{{ $name }}"
                value="{{ $option_value }}"
                @if ($disabled) disabled @endif
                class="{{ config('admin.appearance_class.form.radio') }} {{ $class }}"
                @if ($value == $option_value) checked @endif>
            <span class="ml-2">{{ __($option_label) }}</span>
        </label>
    @endforeach
</div>
