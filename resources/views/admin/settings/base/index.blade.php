{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ __('admin.settings.base.index.heading') }}</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8">{{ __('admin.settings.base.index.description') }}</p>

    <!-- 設定カード -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <!-- サイト設定 -->
        <a href="{{ route('admin.settings.base.site') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-globe text-blue-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin.nav.settings.base.site') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="truncate">{{ $appName }}</p>
                <p class="text-xs mt-1">{{ __('admin.settings.base.index.locale') }}: {{ $locale }} / {{ $timezone }}</p>
            </div>
        </a>

        <!-- 管理画面設定 -->
        <a href="{{ route('admin.settings.base.admin') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-cog text-purple-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin.nav.settings.base.admin') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin.settings.base.index.admin_url') }}: /{{ $adminUrl }}</p>
                <p class="text-xs mt-1">
                    SSL: 
                    @if($forceSsl)
                        <span class="text-green-600 dark:text-green-400">{{ __('common.enabled') }}</span>
                    @else
                        <span class="text-gray-500">{{ __('common.disabled') }}</span>
                    @endif
                </p>
            </div>
        </a>

        <!-- メール設定 -->
        <a href="{{ route('admin.settings.base.mail') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-envelope text-green-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin.nav.settings.base.mail') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                <p class="text-gray-600 dark:text-gray-400">{{ __('admin.settings.base.index.mailer') }}: {{ $mailMailer }}</p>
                @if($mailTestComplete)
                    <span class="inline-flex items-center text-green-600 dark:text-green-400 text-xs mt-1">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin.settings.base.index.mail_test_complete') }}
                    </span>
                @else
                    <span class="inline-flex items-center text-yellow-600 dark:text-yellow-400 text-xs mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin.settings.base.index.mail_test_required') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- メンテナンス設定 -->
        <a href="{{ route('admin.settings.base.maintenance') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-tools text-orange-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin.nav.settings.base.maintenance') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($maintenanceMode)
                    <span class="inline-flex items-center text-orange-600 dark:text-orange-400">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ __('admin.settings.base.index.maintenance_active') }}
                    </span>
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin.settings.base.index.maintenance_inactive') }}
                    </span>
                @endif
            </div>
        </a>
    </div>
</div>
@endsection
