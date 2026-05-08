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

<div x-data="gettingStartedCard(@js($gettingStarted['visited']), {{ $gettingStarted['allCompleted'] ? 'true' : 'false' }})"
     x-show="!dismissed"
     x-cloak
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     data-dismiss-url="{{ route('admin.dashboard.dismiss-getting-started') }}"
     data-visit-url="{{ route('admin.dashboard.visit-getting-started') }}"
     class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

    <div class="flex items-start justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                <i class="fas fa-rocket mr-2"></i>{{ __('admin/dashboard/getting-started.heading') }}
            </h2>
            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">{{ __('admin/dashboard/getting-started.description') }}</p>
        </div>
        <button type="button" @click="dismiss()"
                class="flex-shrink-0 text-sm text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-100 transition px-2 py-1 rounded hover:bg-blue-100 dark:hover:bg-blue-800/30">
            <i class="fas fa-times mr-1"></i>{{ __('admin/dashboard/getting-started.dismiss') }}
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Two-factor authentication --}}
        <a href="{{ route('admin.profile.two-fa') }}" @click="visit('two_fa')"
           class="flex items-start gap-3 p-4 rounded-lg border transition"
           :class="isVisited('two_fa')
               ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
               : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500'">
            <span class="mt-0.5 text-lg" :class="isVisited('two_fa') ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400'">
                <i class="fas" :class="isVisited('two_fa') ? 'fa-check-circle' : 'fa-shield-alt'"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.two_fa_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ __('admin/dashboard/getting-started.two_fa_description') }}</p>
            </div>
        </a>

        {{-- Find plugins --}}
        <a href="{{ route('admin.settings.plugins.add') }}" @click="visit('plugins')"
           class="flex items-start gap-3 p-4 rounded-lg border transition"
           :class="isVisited('plugins')
               ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
               : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500'">
            <span class="mt-0.5 text-lg" :class="isVisited('plugins') ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400'">
                <i class="fas" :class="isVisited('plugins') ? 'fa-check-circle' : 'fa-puzzle-piece'"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.plugins_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ __('admin/dashboard/getting-started.plugins_description') }}</p>
            </div>
        </a>

        {{-- Customize theme --}}
        <a href="{{ Route::has('admin.settings.themes.settings') ? route('admin.settings.themes.settings') : route('admin.settings.themes.index') }}"
           @click="visit('theme')"
           class="flex items-start gap-3 p-4 rounded-lg border transition"
           :class="isVisited('theme')
               ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
               : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500'">
            <span class="mt-0.5 text-lg" :class="isVisited('theme') ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400'">
                <i class="fas" :class="isVisited('theme') ? 'fa-check-circle' : 'fa-palette'"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.theme_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ __('admin/dashboard/getting-started.theme_description') }}</p>
            </div>
        </a>

        {{-- Edit front page --}}
        <a href="{{ route('admin.front.index') }}" @click="visit('front')"
           class="flex items-start gap-3 p-4 rounded-lg border transition"
           :class="isVisited('front')
               ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
               : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500'">
            <span class="mt-0.5 text-lg" :class="isVisited('front') ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400'">
                <i class="fas" :class="isVisited('front') ? 'fa-check-circle' : 'fa-file-alt'"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.front_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ __('admin/dashboard/getting-started.front_description') }}</p>
            </div>
        </a>
    </div>
</div>
