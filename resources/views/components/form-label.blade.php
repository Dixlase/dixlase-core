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
    'for' => null,     // labelのfor属性
    'text' => null,    // labelに表示するテキスト（翻訳済み）
    'key' => null,     // 翻訳キー（textとkeyのどちらか一方を指定）
    'class' => '',     // labelの追加クラス
    'required' => false, // 必須マーク表示
])

<label for="{{ $for }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 {{ $class }}">
    @if ($key)
        {{ __($key) }}
    @elseif ($text)
        {{ $text }}
    @else
        {{ $slot }}
    @endif
    @if ($required)
        <x-form-required-badge />
    @endif
</label>
