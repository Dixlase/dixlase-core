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

<section>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-bell mr-2"></i>{{ __('admin/dashboard.plugin_notifications') }}
    </h2>

    <div class="space-y-3">
        @foreach($pluginNotifications as $notification)
            <div class="flex items-start gap-3 p-4 rounded-lg border
                @if($notification->level === 'warning')
                    border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20
                @elseif($notification->level === 'recommendation')
                    border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20
                @else
                    border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20
                @endif
            ">
                <span class="mt-0.5 text-lg
                    @if($notification->level === 'warning')
                        text-yellow-600 dark:text-yellow-400
                    @elseif($notification->level === 'recommendation')
                        text-amber-600 dark:text-amber-400
                    @else
                        text-blue-600 dark:text-blue-400
                    @endif
                ">
                    <i class="{{ $notification->icon }}"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $notification->pluginName }}</h3>
                        @if($notification->level === 'warning')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                {{ __('admin/dashboard.status_warning') }}
                            </span>
                        @elseif($notification->level === 'recommendation')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                {{ __('admin/dashboard.status_recommendation') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ __('admin/dashboard.status_info') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $notification->message }}</p>
                    @if($notification->url)
                        <a href="{{ $notification->url }}" class="inline-flex items-center text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1.5">
                            {{ $notification->actionLabel ?? __('admin/dashboard.view_settings') }}
                            <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
