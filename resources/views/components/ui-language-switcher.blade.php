{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-language-switcher />

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

@props([
    'type' => 'dropdown', // 'dropdown', 'inline', 'flags'
    'showLabel' => true,
])

@php
use App\Helpers\LocaleHelper;

$currentLocale = app()->getLocale();
$supportedLocales = LocaleHelper::supportedLocales();
$currentUrl = request()->path();
@endphp

@if($type === 'dropdown')
{{-- Dropdown style --}}
<div x-data="{ open: false }" class="relative inline-block text-left">
    <div>
        <button type="button" 
                @click="open = !open"
                @click.away="open = false"
                class="inline-flex items-center justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                aria-expanded="true" 
                aria-haspopup="true">
            @if($showLabel)
                <i class="fas fa-globe mr-2"></i>
            @endif
            <span>{{ LocaleHelper::getLocaleName($currentLocale, true) }}</span>
            <i class="fas fa-chevron-down ml-2 -mr-1 h-4 w-4"></i>
        </button>
    </div>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         role="menu" 
         aria-orientation="vertical">
        <div class="py-1" role="none">
            @foreach($supportedLocales as $locale)
            <a href="{{ LocaleHelper::switchLocaleUrl('/' . $currentUrl, $locale) }}"
               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $locale === $currentLocale ? 'bg-gray-50 dark:bg-gray-900 font-semibold' : '' }}"
               role="menuitem">
                <span class="flex items-center">
                    @if($locale === $currentLocale)
                        <i class="fas fa-check mr-2 text-blue-500"></i>
                    @else
                        <span class="mr-2 w-4"></span>
                    @endif
                    {{ LocaleHelper::getLocaleName($locale, true) }}
                </span>
            </a>
            @endforeach
        </div>
    </div>
</div>

@elseif($type === 'inline')
{{-- Inline style --}}
<div class="inline-flex items-center space-x-2">
    @if($showLabel)
        <i class="fas fa-globe text-gray-600 dark:text-gray-400"></i>
    @endif
    @foreach($supportedLocales as $locale)
        <a href="{{ LocaleHelper::switchLocaleUrl('/' . $currentUrl, $locale) }}"
           class="px-3 py-1 rounded-md text-sm font-medium transition-colors {{ $locale === $currentLocale ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>

@elseif($type === 'flags')
{{-- Flag style (emoji) --}}
<div class="inline-flex items-center space-x-3">
    @if($showLabel)
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.language') }}:</span>
    @endif
    @foreach($supportedLocales as $locale)
        @php
            $flag = match($locale) {
                'ja' => '🇯🇵',
                'en' => '🇬🇧',
                default => '🌐',
            };
        @endphp
        <a href="{{ LocaleHelper::switchLocaleUrl('/' . $currentUrl, $locale) }}"
           class="inline-flex items-center px-2 py-1 rounded-md text-sm transition-colors {{ $locale === $currentLocale ? 'bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
           title="{{ LocaleHelper::getLocaleName($locale, true) }}">
            <span class="text-2xl">{{ $flag }}</span>
            <span class="ml-1 text-xs font-medium {{ $locale === $currentLocale ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                {{ strtoupper($locale) }}
            </span>
        </a>
    @endforeach
</div>

@endif
