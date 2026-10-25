{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

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

<section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-heart-pulse mr-2"></i>{{ __('admin/dashboard.site_health') }}
    </h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($siteHealth as $item)
            @if(! empty($item['records']))
                {{-- A card that lists records holds links of its own, so it cannot be one link itself --}}
                <div class="flex items-start gap-3 p-4 rounded-lg border {{ $item['border_bg_class'] }}">
                    <span class="mt-0.5 text-lg {{ $item['icon_color_class'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if($item['is_clickable'])
                                    <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['label'] }}</a>
                                @else
                                    {{ $item['label'] }}
                                @endif
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['badge_label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $item['description'] }}</p>
                        <ul class="mt-2 space-y-1 text-xs">
                            @foreach($item['records'] as $record)
                                <li>
                                    @if($record['is_clickable'])
                                        <a href="{{ $record['url'] }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $record['label'] }}</a>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300">{{ $record['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                            @if($item['records_more'] > 0)
                                <li class="text-gray-500 dark:text-gray-400">{{ __('admin/dashboard.file_content_more', ['count' => $item['records_more']]) }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            @elseif($item['is_clickable'])
                <a href="{{ $item['url'] }}" class="flex items-start gap-3 p-4 rounded-lg border {{ $item['border_bg_class'] }} hover:shadow-md transition-shadow no-underline">
                    <span class="mt-0.5 text-lg {{ $item['icon_color_class'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['badge_label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $item['description'] }}</p>
                    </div>
                </a>
            @else
                <div class="flex items-start gap-3 p-4 rounded-lg border {{ $item['border_bg_class'] }}">
                    <span class="mt-0.5 text-lg {{ $item['icon_color_class'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['badge_label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $item['description'] }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>
