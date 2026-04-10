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
--}}

<div x-data="gettingStartedCard()" x-show="!dismissed" x-cloak
     data-dismiss-url="{{ route('admin.dashboard.dismiss-getting-started') }}"
     class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

    <div class="flex items-start justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                <i class="fas fa-rocket mr-2"></i>{{ __('admin/dashboard/getting-started.heading') }}
            </h2>
            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">{{ __('admin/dashboard/getting-started.description') }}</p>
        </div>
        <button type="button" @click="dismiss()"
                class="text-blue-400 dark:text-blue-500 hover:text-blue-600 dark:hover:text-blue-300 transition p-1"
                title="{{ __('admin/dashboard/getting-started.dismiss') }}"
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- 2段階認証 --}}
        <a href="{{ route('admin.profile.two-fa-management') }}"
           class="flex items-start gap-3 p-4 rounded-lg border transition
                  {{ $gettingStarted['twoFaEnabled']
                      ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
                      : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500' }}">
            <span class="mt-0.5 text-lg {{ $gettingStarted['twoFaEnabled'] ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400' }}">
                <i class="fas {{ $gettingStarted['twoFaEnabled'] ? 'fa-check-circle' : 'fa-shield-alt' }}"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.two_fa_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                    {{ $gettingStarted['twoFaEnabled']
                        ? __('admin/dashboard/getting-started.two_fa_done')
                        : __('admin/dashboard/getting-started.two_fa_description') }}
                </p>
            </div>
        </a>

        {{-- プラグインを探す --}}
        <a href="{{ route('admin.settings.plugins.add') }}"
           class="flex items-start gap-3 p-4 rounded-lg border transition
                  {{ $gettingStarted['hasPlugins']
                      ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
                      : 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500' }}">
            <span class="mt-0.5 text-lg {{ $gettingStarted['hasPlugins'] ? 'text-green-500 dark:text-green-400' : 'text-blue-500 dark:text-blue-400' }}">
                <i class="fas {{ $gettingStarted['hasPlugins'] ? 'fa-check-circle' : 'fa-puzzle-piece' }}"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.plugins_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                    {{ $gettingStarted['hasPlugins']
                        ? __('admin/dashboard/getting-started.plugins_done')
                        : __('admin/dashboard/getting-started.plugins_description') }}
                </p>
            </div>
        </a>

        {{-- テーマをカスタマイズ --}}
        <a href="{{ route('admin.front.edit') }}"
           class="flex items-start gap-3 p-4 rounded-lg border border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 hover:border-blue-400 dark:hover:border-blue-500 transition">
            <span class="mt-0.5 text-lg text-blue-500 dark:text-blue-400">
                <i class="fas fa-palette"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('admin/dashboard/getting-started.theme_title') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ __('admin/dashboard/getting-started.theme_description') }}</p>
            </div>
        </a>
    </div>
</div>
