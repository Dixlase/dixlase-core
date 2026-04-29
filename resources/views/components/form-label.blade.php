{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-label />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
