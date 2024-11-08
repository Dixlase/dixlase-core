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
// テーマの設定を取得
$theme = config('admin.theme');
$isDark = $theme === 'dark';

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

<div class="flex flex-col w-64 h-full {{ $isDark ? 'bg-gray-800 text-white' : 'bg-white text-gray-800' }}">
    <nav class="flex-1 px-4 py-4 space-y-1">
        @foreach (config('admin.nav') as $key => $item)
            <div x-data="{ open: false }">
                @if (isset($item['route']) && is_string($item['route']))
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center px-4 py-2 text-sm font-medium {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} rounded-md">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                    </a>
                @else
                    <button @click="open = !open"
                            class="flex items-center w-full px-4 py-2 text-sm font-medium {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} rounded-md focus:outline-none">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                        <svg class="w-4 h-4 ml-auto transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @endif

                @if (hasSubmenu($item))
                    <div x-show="open" class="ml-4 space-y-1">
                        @foreach ($item['children'] as $subKey => $subItem)
                            <a href="{{ route($subItem['route']) }}"
                               class="flex items-center px-4 py-2 text-sm font-medium {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} rounded-md">
                                <i class="{{ $subItem['icon'] }} mr-3"></i>
                                <span>{{ __($subItem['text']) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </nav>
</div>
