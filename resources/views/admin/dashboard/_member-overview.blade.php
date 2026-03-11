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

<section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 h-full">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-users mr-2"></i>{{ __('admin/dashboard.member_overview') }}
    </h2>

    {{-- メンバー統計 --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $memberOverview['total'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/dashboard.total_members') }}</p>
        </div>
        <div class="p-4 rounded-lg border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 text-center">
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $memberOverview['active'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/dashboard.active') }}</p>
        </div>
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 text-center">
            <p class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $memberOverview['inactive'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/dashboard.inactive') }}</p>
        </div>
    </div>

    {{-- 2FA有効率 --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fas fa-user-shield mr-1"></i>{{ __('admin/dashboard.two_fa_rate_label') }}
            </span>
            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $memberOverview['two_fa_rate'] }}%
                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $memberOverview['two_fa_enabled'] }}/{{ $memberOverview['total'] }})</span>
            </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div class="h-2 rounded-full
                @if($memberOverview['two_fa_rate'] >= 80)
                    bg-green-500
                @elseif($memberOverview['two_fa_rate'] >= 50)
                    bg-yellow-500
                @else
                    bg-red-500
                @endif
            " style="width: {{ min($memberOverview['two_fa_rate'], 100) }}%"></div>
        </div>
    </div>

    {{-- 詳細モード: ロール分布 + 最近のログイン --}}
    <div x-show="isDetailed" x-cloak>
        {{-- ロール分布 --}}
        @if(count($memberOverview['by_role']) > 0)
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('admin/dashboard.role_distribution') }}
            </h3>
            <ul class="space-y-1 mb-4">
                @foreach($memberOverview['by_role'] as $roleName => $roleInfo)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $roleInfo['label'] }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $roleInfo['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- 最近のログイン --}}
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ __('admin/dashboard.recent_logins') }}
        </h3>
        @if(count($memberOverview['recent_logins']) > 0)
            <ul class="space-y-2">
                @foreach($memberOverview['recent_logins'] as $login)
                    <li class="flex items-center justify-between text-sm">
                        <div class="min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $login['display_name'] }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 ml-1">
                                {{ $login['role_label'] }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0 ml-2">{{ $login['last_login_at'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/dashboard.no_recent_logins') }}</p>
        @endif
    </div>

    {{-- メンバー管理リンク --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.members.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            {{ __('admin/dashboard.manage_members') }} <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
</section>
