{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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

<section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 h-full">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-puzzle-piece mr-2"></i>{{ __('admin/dashboard.extension_overview') }}
    </h2>

    {{-- プラグイン / テーマ統計 --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        {{-- プラグイン --}}
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-plug mr-1"></i>{{ __('admin/dashboard.plugins') }}
            </h3>
            <div class="mt-2 flex items-baseline gap-3">
                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $extensionOverview['plugins']['enabled'] }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin/dashboard.enabled') }} / {{ $extensionOverview['plugins']['installed'] }} {{ __('admin/dashboard.installed') }}
                </span>
            </div>
        </div>

        {{-- テーマ --}}
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-palette mr-1"></i>{{ __('admin/dashboard.themes') }}
            </h3>
            <div class="mt-2 flex items-baseline gap-3">
                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $extensionOverview['themes']['enabled'] }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin/dashboard.enabled') }} / {{ $extensionOverview['themes']['installed'] }} {{ __('admin/dashboard.installed') }}
                </span>
            </div>
        </div>
    </div>

    {{-- アップデート可能件数（クリックで統合アップデート管理ページへ） --}}
    @if(($extensionOverview['updates']['total'] ?? 0) > 0)
        <div class="mb-4 p-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20">
            <a href="{{ route('admin.settings.systems.updates.index') }}" class="flex items-center justify-between gap-3 text-sm">
                <span class="flex items-center gap-2 text-blue-800 dark:text-blue-200 font-medium">
                    <i class="fas fa-arrow-up"></i>
                    {{ __('admin/dashboard.updates_available_label') }}: {{ $extensionOverview['updates']['total'] }}
                </span>
                <span class="text-blue-700 dark:text-blue-300 text-xs">
                    {{ __('admin/dashboard.updates_available_summary', [
                        'plugins' => $extensionOverview['updates']['plugins'],
                        'themes' => $extensionOverview['updates']['themes'],
                    ]) }}
                    <i class="fas fa-arrow-right ml-1"></i>
                </span>
            </a>
        </div>
    @endif

    {{-- 健全性サマリー（詳細モードのみ） --}}
    <div x-show="isDetailed" x-cloak>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            <i class="fas fa-heartbeat mr-1"></i>{{ __('admin/dashboard.health_overview') }}
        </h3>

        @if($extensionOverview['has_audits'])
            <div class="flex flex-wrap gap-2">
                @foreach($extensionOverview['health'] as $statusKey => $info)
                    @if($info['count'] > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                            @if($info['color'] === 'green')
                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($info['color'] === 'yellow')
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($info['color'] === 'orange')
                                bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                            @else
                                bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                            @endif
                        ">
                            <i class="{{ $info['icon'] }}"></i>
                            {{ $info['label'] }}: {{ $info['count'] }}
                        </span>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/dashboard.no_audits') }}</p>
        @endif
    </div>

    {{-- プラグイン管理リンク --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.settings.plugins.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            {{ __('admin/dashboard.manage_plugins') }} <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
</section>
