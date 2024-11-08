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


@php
/**
 * サブメニューが存在するかをチェックする関数
 *
 * @param array $item メニュー項目
 * @return bool サブメニューが存在する場合は true
 */
function hasSubmenu(array $item): bool
{
    return isset($item['children']) && is_array($item['children']);
}
@endphp



<ul class="nav flex-column">
    @foreach (config('admin.nav') as $key => $item)
        <li class="nav-item">
            @if (isset($item['route']) && is_string($item['route']))
                <a href="{{ route($item['route']) }}" class="nav-link">
                    <i class="{{ $item['icon'] }}"></i>
                    <span>{{ __($item['text']) }}</span>
                </a>
            @else
                <span class="nav-link">
                    <i class="{{ $item['icon'] }}"></i>
                    <span>{{ __($item['text']) }}</span>
                </span>
            @endif

            {{-- サブメニューが存在する場合、再帰的にレンダリング --}}
            @if (hasSubmenu($item))
                <ul class="nav flex-column ml-3">
                    @foreach ($item['children'] as $subKey => $subItem)
                        <li class="nav-item">
                            @if (isset($subItem['route']) && is_string($subItem['route']))
                                <a href="{{ route($subItem['route']) }}" class="nav-link">
                                    <i class="{{ $subItem['icon'] }}"></i>
                                    <span>{{ __($subItem['text']) }}</span>
                                </a>
                            @else
                                <span class="nav-link">
                                    <i class="{{ $subItem['icon'] }}"></i>
                                    <span>{{ __($subItem['text']) }}</span>
                                </span>
                            @endif


                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
