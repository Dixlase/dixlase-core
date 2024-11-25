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
    'type' => 'button',      // ボタンのタイプ (button, submit, reset)
    'class' => '',           // カスタムクラス
    'label' => 'Button',     // ボタンのテキスト
    'onclick' => null,       // onclick属性を追加
    'theme' => 'light',      // テーマ
])

@php
    // テーマに応じたクラス設定
    $theme_class = $theme === 'light'
        ? 'bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none'
        : 'bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none';
@endphp


<button type="{{ $type }}"
    @if ($onclick) onclick="{{ $onclick }}" @endif
    class="py-2 px-4 rounded-md shadow-sm {{ $theme_class }} {{ $class }}">
    {{ $label }}
</button>
