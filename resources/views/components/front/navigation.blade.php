{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-front.navigation />

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

{{--
    Navigation Component
    
    Usage:
    <x-front.navigation :items="$menuItems" />
    
    Props:
    - items: array - Navigation menu items
    - class: string - Additional CSS classes (optional)
--}}

@props(['items' => [], 'class' => ''])

<nav {{ $attributes->merge(['class' => 'navigation ' . $class]) }}>
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            {{-- Mobile menu button --}}
            <button 
                type="button" 
                data-nav-toggle
                aria-expanded="false"
                aria-label="Toggle navigation"
                class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {{-- Desktop menu --}}
            <div class="hidden md:flex md:items-center md:space-x-4">
                @foreach($items as $item)
                    <a 
                        href="{{ $item['url'] ?? '#' }}" 
                        class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                        @if(isset($item['current']) && $item['current']) aria-current="page" @endif
                    >
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Mobile menu --}}
        <div data-nav-menu class="hidden md:hidden pb-3">
            <div class="space-y-1">
                @foreach($items as $item)
                    <a 
                        href="{{ $item['url'] ?? '#' }}" 
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                        @if(isset($item['current']) && $item['current']) aria-current="page" @endif
                    >
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>
