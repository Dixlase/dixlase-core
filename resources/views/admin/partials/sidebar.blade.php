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


<div class="flex flex-col w-64 h-full">
    <nav class="flex-1 px-4 py-4 space-y-1">
        @foreach (config('admin.nav') as $key => $item)
            @php
                $open_key = 'open_' . $key;
                $is_open = preg_match('/' . preg_quote($key, '/') . '/', $route_name);
            @endphp

            <div x-data="{ {{ $open_key }} : {{ $is_open ? 'true' : 'false' }} }">
                @if (isset($item['route']) && is_string($item['route']))
                    <a href="{{ route($item['route']) }}"
                    class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-black dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white rounded-md {{ $item['route'] === $route_name ? config('admin.theme_class.sidebar.active') : '' }}">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                    </a>
                @else
                    <button @click="{{ $open_key }} = !{{ $open_key }}"
                            class="flex items-center w-full px-4 py-2 text-sm font-medium {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} rounded-md focus:outline-none">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                        <svg class="w-4 h-4 ml-auto transform" :class="{ 'rotate-180': {{ $open_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @endif

                @if (isset($item['children']) && is_array($item['children']))
                    <div x-show="{{ $open_key }}"
                        x-transition:enter="transition ease-out duration-300 h-0"
                        x-transition:enter-start="opacity-0 transform -translate-y-2 h-0"
                        x-transition:enter-end="opacity-100 transform translate-y-0 h-auto"
                        x-transition:leave="transition ease-in duration-200 h-auto"
                        x-transition:leave-start="opacity-100 transform translate-y-0 h-auto"
                        x-transition:leave-end="opacity-0 transform -translate-y-2 h-0"
                        class="ml-4 space-y-1">
                        @foreach ($item['children'] as $childKey => $childItem)
                            @if (isset($childItem['route']) && is_string($childItem['route']))
                                <a href="{{ route($childItem['route']) }}"
                                class="flex items-center px-4 py-2 text-sm font-medium rounded-md {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} {{ $childItem['route'] === $route_name ? config('admin.theme_class.sidebar.active') : '' }}">
                                    <i class="{{ $childItem['icon'] }} mr-3"></i>
                                    <span>{{ __($childItem['text']) }}</span>
                                </a>
                            @else
                                @php
                                    $open_child_key = 'open_' . $childKey;
                                    $is_open_child = preg_match('/' . preg_quote($childKey, '/') . '/', $route_name);
                                @endphp

                                <div x-data="{ {{ $open_child_key }}: {{ $is_open_child ? 'true' : 'false' }} }">
                                    <button @click="{{ $open_child_key }} = !{{ $open_child_key }}"
                                            class="flex items-center w-full px-4 py-2 text-sm font-medium {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} rounded-md focus:outline-none">
                                        <i class="{{ $childItem['icon'] }} mr-3"></i>
                                        <span>{{ __($childItem['text']) }}</span>
                                        <svg class="w-4 h-4 ml-auto transform" :class="{ 'rotate-180': {{ $open_child_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>


                                    @if (isset($item['children']) && is_array($item['children']))
                                        <div x-show="{{ $open_child_key }}"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                                            x-transition:enter-end="opacity-100 transform translate-y-0"
                                            x-transition:leave="transition ease-in duration-200 to"
                                            x-transition:leave-start="opacity-100 transform translate-y-0"
                                            x-transition:leave-end="opacity-0 transform -translate-y-2"
                                            class="ml-4 space-y-1">
                                            @foreach ($childItem['children'] as $sgrandchildKey => $grandchildItem)
                                                <a href="{{ route($grandchildItem['route']) }}"
                                                class="flex items-center px-4 py-2 text-sm font-medium rounded-md {{ $isDark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-black' }} {{ $grandchildItem['route'] === $route_name ? config('admin.theme_class.sidebar.active') : '' }}">
                                                    <i class="{{ $grandchildItem['icon'] }} mr-3"></i>
                                                    <span>{{ __($grandchildItem['text']) }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </nav>
</div>
