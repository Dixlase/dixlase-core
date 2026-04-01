{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

Collapsible accordion section for preview sidebar.
--}}

@props([
    'title',
    'icon' => null,
    'open' => false,
    'divider' => true,
])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }">
    <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        <span>@if($icon)<i class="{{ $icon }} mr-1.5"></i>@endif{{ $title }}</span>
        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-collapse>
        {{ $slot }}
    </div>
</div>

@if($divider)
<hr class="border-gray-200 dark:border-gray-700">
@endif
