{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

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

<section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-heart-pulse mr-2"></i>{{ __('admin/dashboard.site_health') }}
    </h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($siteHealth as $item)
            @if($item['is_clickable'])
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
