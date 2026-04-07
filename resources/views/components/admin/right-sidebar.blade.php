{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-admin.right-sidebar />

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
    'openLabel' => __('common.open_settings'),
    'closeLabel' => __('common.close_settings'),
    'panelClass' => '',
])

{{-- トグルボタン --}}
<button type="button"
        @click="toggleRightSidebar()"
        class="flex fixed top-14 right-0 z-50 items-center backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-l-lg shadow-md border border-r-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
        :class="{
            'translate-x-0': rightSidebarCollapsed,
            '-translate-x-80': !rightSidebarCollapsed
        }"
        :style="rightSidebarReady ? 'transition: translate 200ms ease-in-out, transform 200ms ease-in-out' : ''"
        :aria-label="rightSidebarCollapsed ? '{{ $openLabel }}' : '{{ $closeLabel }}'">
    <i class="fas text-sm" :class="rightSidebarCollapsed ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
</button>

{{-- サイドバーパネル --}}
<div class="space-y-5 fixed top-12 right-0 bottom-0 w-80 z-50 overflow-y-auto bg-white/75 dark:bg-gray-900/75 backdrop-blur-sm border-l border-gray-200 dark:border-gray-600 shadow-md px-5 py-5 {{ $panelClass }}"
     :class="{
         'translate-x-80': rightSidebarCollapsed,
         'translate-x-0': !rightSidebarCollapsed
     }"
     :style="rightSidebarReady ? 'transition: translate 300ms ease-in-out, transform 300ms ease-in-out' : ''">

    {{ $slot }}

    {{-- セーブボタン用の下部スペース --}}
    <div class="h-20"></div>
</div>
