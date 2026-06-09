{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-front.breadcrumb />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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
    Breadcrumb Component
    
    Usage:
    <x-front.breadcrumb :items="$breadcrumbs" />
    
    Props:
    - items: array - Breadcrumb items [['label' => 'Home', 'url' => '/'], ...]
    - class: string - Additional CSS classes (optional)
--}}

@props(['items' => [], 'class' => ''])

@if(count($items) > 0)
<nav {{ $attributes->merge(['class' => 'breadcrumb ' . $class, 'aria-label' => 'Breadcrumb']) }}>
    <ol class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
        @foreach($items as $index => $item)
            <li class="breadcrumb__item flex items-center">
                @if($index > 0)
                    <span class="breadcrumb__separator mx-2 text-gray-400">/</span>
                @endif
                
                @if(isset($item['url']) && $index < count($items) - 1)
                    <a 
                        href="{{ $item['url'] }}" 
                        class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    >
                        {{ $item['label'] ?? '' }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-white font-medium" aria-current="page">
                        {{ $item['label'] ?? '' }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
