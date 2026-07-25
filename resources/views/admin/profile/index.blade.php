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

@extends('layouts.admin')

@section('content')
<div class="mx-auto">

    <p class="text-gray-600 dark:text-gray-400 mb-8">{{ __('admin/profile/index.description') }}</p>

    <!-- 設定カード -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
        <!-- 基本情報 -->
        <a href="{{ route('admin.profile.basic') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-user text-blue-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.basic_info') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="truncate">{{ $member->account_name }}</p>
                <p class="text-xs mt-1">{{ $member->email }}</p>
            </div>
        </a>

        <!-- パスワード設定 -->
        <a href="{{ route('admin.profile.password') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-key text-purple-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.password') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/profile/index.password_description') }}</p>
            </div>
        </a>

        <!-- 外観設定 -->
        <a href="{{ route('admin.profile.appearance') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-palette text-green-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.appearance') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ $member->appearance?->label() ?? __('components/ui-appearance-mode-selector.auto') }}</p>
            </div>
        </a>

        <!-- 通知設定 -->
        <a href="{{ route('admin.profile.notifications') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-bell text-yellow-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.notifications') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/profile/index.notifications_description') }}</p>
            </div>
        </a>

        <!-- 二段階認証設定 -->
        @if($isMailServerTested)
        <a href="{{ route('admin.profile.two-fa') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-red-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.two_factor') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($twoFaMode?->value > 0)
                    <span class="inline-flex items-center text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('common.enabled') }}
                    </span>
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('common.disabled') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- 二段階認証管理 -->
        <a href="{{ route('admin.profile.two-fa-management') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-fingerprint text-indigo-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/profile/index.two_factor_management') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/profile/index.passkey_count', ['count' => $twoFaPasskeyDevices->count()]) }}</p>
                <p class="text-xs mt-1">{{ __('admin/profile/index.recovery_codes_count', ['count' => $twoFaRecoveryCodesCount]) }}</p>
            </div>
        </a>
        @endif
    </div>
</div>
@endsection
