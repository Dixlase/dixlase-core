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
    'button_class' => 'flex items-center px-4 py-2 text-sm font-medium rounded-md focus:outline-none',
    'arrow_class' => 'w-4 h-4 ml-auto transform'
])

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
                    class="{{ $button_class }} {{ $item['route'] === $route_name ? config('admin.appearance_class.sidebar.active') : config('admin.appearance_class.sidebar.normal') }}">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                    </a>
                @else
                    <button @click="{{ $open_key }} = !{{ $open_key }}" class="{{ $button_class }} {{ config('admin.appearance_class.sidebar.normal') }} w-full">
                        <i class="{{ $item['icon'] }} mr-3"></i>
                        <span>{{ __($item['text']) }}</span>
                        <svg class="{{ $arrow_class }}" :class="{ 'rotate-180': {{ $open_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        @foreach ($item['children'] as $child_key => $child_item)
                            @if (isset($child_item['route']) && is_string($child_item['route']))
                                <a href="{{ route($child_item['route']) }}" class="{{ $button_class }} {{ $child_item['route'] === $route_name ? config('admin.appearance_class.sidebar.active') : config('admin.appearance_class.sidebar.normal') }}">
                                    <i class="{{ $child_item['icon'] }} mr-3"></i>
                                    <span>{{ __($child_item['text']) }}</span>
                                </a>
                            @else
                                @php
                                    $open_child_key = 'open_' . $child_key;
                                    $is_open_child = preg_match('/' . preg_quote($child_key, '/') . '/', $route_name);
                                @endphp

                                <div x-data="{ {{ $open_child_key }}: {{ $is_open_child ? 'true' : 'false' }} }">
                                    <button @click="{{ $open_child_key }} = !{{ $open_child_key }}" class="{{ $button_class }} w-full">
                                        <i class="{{ $child_item['icon'] }} mr-3"></i>
                                        <span>{{ __($child_item['text']) }}</span>
                                        <svg class="{{ $arrow_class }}" :class="{ 'rotate-180': {{ $open_child_key }} }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                            @foreach ($child_item['children'] as $grand_child_key => $grand_child_item)
                                                <a href="{{ route($grand_child_item['route']) }}"
                                                class="{{ $button_class }} {{ $grand_child_item['route'] === $route_name ? config('admin.appearance_class.sidebar.active') : config('admin.appearance_class.sidebar.normal') }}">
                                                    <i class="{{ $grand_child_item['icon'] }} mr-3"></i>
                                                    <span>{{ __($grand_child_item['text']) }}</span>
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
