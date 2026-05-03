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
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            <i class="fas fa-history mr-2"></i>{{ __('admin/dashboard.recent_activity') }}
        </h2>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('admin/dashboard.activity_last_24h') }}
        </span>
    </div>

    {{-- セキュリティサマリー（警告・失敗がある場合） --}}
    @if($recentActivity['summary']['failed_count'] > 0 || $recentActivity['summary']['warning_count'] > 0)
        <div class="flex items-center gap-3 mb-4 p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
            <div class="flex gap-4 text-sm">
                @if($recentActivity['summary']['failed_count'] > 0)
                    <span class="text-red-600 dark:text-red-400 font-medium">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('admin/dashboard.activity_failed_count', ['count' => $recentActivity['summary']['failed_count']]) }}
                    </span>
                @endif
                @if($recentActivity['summary']['warning_count'] > 0)
                    <span class="text-yellow-600 dark:text-yellow-400 font-medium">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ __('admin/dashboard.activity_warning_count', ['count' => $recentActivity['summary']['warning_count']]) }}
                    </span>
                @endif
            </div>
        </div>
    @endif

    {{-- アクティビティリスト --}}
    @if(count($recentActivity['entries']) > 0)
        <ul class="space-y-3">
            @foreach($recentActivity['entries'] as $entry)
                <li class="flex items-start gap-3 text-sm">
                    {{-- 結果インジケーター --}}
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full flex-shrink-0 mt-0.5
                        @if($entry['outcome'] === 'success')
                            bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400
                        @elseif($entry['outcome'] === 'failure')
                            bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400
                        @elseif($entry['outcome'] === 'denied')
                            bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400
                        @else
                            bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                        @endif
                    ">
                        @if($entry['outcome'] === 'success')
                            <i class="fas fa-check text-xs"></i>
                        @elseif($entry['outcome'] === 'failure')
                            <i class="fas fa-times text-xs"></i>
                        @elseif($entry['outcome'] === 'denied')
                            <i class="fas fa-ban text-xs"></i>
                        @else
                            <i class="fas fa-circle text-xs"></i>
                        @endif
                    </span>

                    {{-- アクション情報 --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ __('admin/dashboard.action_' . $entry['action'], [], null) !== 'admin/dashboard.action_' . $entry['action'] ? __('admin/dashboard.action_' . $entry['action']) : $entry['action'] }}
                            </span>
                            @if($entry['target_label'])
                                <span class="text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="{{ $entry['target_label'] }}">
                                    {{ $entry['target_label'] }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $entry['actor_name'] }}</span>
                            <span>&middot;</span>
                            <span>{{ $entry['occurred_at'] }}</span>
                        </div>
                    </div>

                    {{-- 重要度バッジ（warning以上のみ表示） --}}
                    @if(in_array($entry['severity'], ['warning', 'error', 'critical', 'alert', 'emergency']))
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium flex-shrink-0 {{ $entry['severity_color'] }}">
                            {{ $entry['severity'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin/dashboard.activity_no_entries') }}
        </p>
    @endif

    {{-- ログ管理リンク --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.settings.systems.logs.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            {{ __('admin/dashboard.view_logs') }} <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
</section>
