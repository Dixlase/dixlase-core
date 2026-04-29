{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-checkbox-group />

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
    'name' => '',
    'options' => [],
    'values' => [],
    'disabled' => false,
    'class' => '',
    'flexDirection' => 'row',
    'permissionStyle' => false // 権限グループ用のスタイリング
])

<div @class([
    'flex',
    'flex-wrap',
    'gap-4' => !$permissionStyle,
    'space-y-2' => $permissionStyle,
    'flex-col' => $flexDirection == 'col',
    'flex-col sm:flex-row' => $flexDirection != 'col' && !$permissionStyle,
])>
    @foreach ($options as $option_value => $option_label)
        <label @class([
            'inline-flex items-center' => !$permissionStyle,
            'permission-option flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors' => $permissionStyle,
        ])>
            <input type="checkbox"
                id="{{ $name }}_{{ $option_value }}"
                name="{{ $name }}[]"
                value="{{ $option_value }}"
                @if ($disabled) disabled @endif
                @class([
                    'text-gray-600 dark:text-gray-600' => !$permissionStyle,
                    $class
                ])
                @if (in_array($option_value, $values)) checked @endif>
            <span @class([
                'ml-2 text-sm' => !$permissionStyle,
                'permission-option__label ml-3 text-sm text-gray-700 dark:text-gray-300' => $permissionStyle,
            ])>{{ __($option_label) }}</span>
        </label>
    @endforeach
</div>
