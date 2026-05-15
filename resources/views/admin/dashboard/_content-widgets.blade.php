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
      (see LICENSE.commercial, or contact info@dixlase.org).

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

@if(count($pluginWidgets) > 0)
<section>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-th-large mr-2"></i>{{ __('admin/dashboard.content_overview') }}
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($pluginWidgets as $widget)
            @php
                $colorClasses = match($widget->color) {
                    'green' => 'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
                    'purple' => 'border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400',
                    'orange' => 'border-orange-300 dark:border-orange-600 bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
                    'red' => 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400',
                    'yellow' => 'border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400',
                    'indigo' => 'border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400',
                    default => 'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
                };
            @endphp
            <a href="{{ $widget->url ?? '#' }}"
               class="block p-4 rounded-lg border {{ $colorClasses }} transition-shadow hover:shadow-md"
               @if(!$widget->url) aria-disabled="true" @endif
            >
                <div class="flex items-center gap-3">
                    <span class="text-2xl">
                        <i class="{{ $widget->icon }}"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $widget->value }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $widget->label }}</p>
                    </div>
                </div>
                @if($widget->description)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $widget->description }}</p>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif
