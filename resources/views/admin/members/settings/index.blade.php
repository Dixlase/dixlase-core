{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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

@extends('layouts.admin')

@section('content')
<div class="mx-auto">

    <!-- 設定カード -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
        <!-- パスワード設定 -->
        <a href="{{ route('admin.members.settings.password') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-key text-blue-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/members/settings.nav.password') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/members/settings.index.password_min_length') }}: {{ $passwordMinLength }}{{ __('admin/members/settings.index.characters') }}</p>
                <p class="text-xs mt-1">
                    @if($passwordRequireUppercase || $passwordRequireNumber || $passwordRequireSymbol)
                        {{ __('admin/members/settings.index.requirements') }}:
                        @if($passwordRequireUppercase)<span class="text-green-600 dark:text-green-400">{{ __('admin/members/settings.index.uppercase') }}</span>@endif
                        @if($passwordRequireNumber)<span class="text-green-600 dark:text-green-400 ml-1">{{ __('admin/members/settings.index.number') }}</span>@endif
                        @if($passwordRequireSymbol)<span class="text-green-600 dark:text-green-400 ml-1">{{ __('admin/members/settings.index.symbol') }}</span>@endif
                    @else
                        <span class="text-gray-500">{{ __('admin/members/settings.index.no_requirements') }}</span>
                    @endif
                </p>
            </div>
        </a>

        <!-- セッション設定 -->
        <a href="{{ route('admin.members.settings.session') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-clock text-purple-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/members/settings.nav.session') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                @if($membersSessionLifetimeEnabled)
                    <p>{{ __('admin/members/settings.index.session_lifetime') }}: {{ $membersSessionLifetime }}{{ __('admin/members/settings.minutes') }}</p>
                    <span class="inline-flex items-center text-green-600 dark:text-green-400 text-xs mt-1">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin/members/settings.index.custom_session_enabled') }}
                    </span>
                @else
                    <p>{{ __('admin/members/settings.index.session_lifetime') }}: {{ __('admin/members/settings.index.system_default') }}</p>
                    <span class="inline-flex items-center text-gray-500 text-xs mt-1">
                        <i class="fas fa-minus-circle mr-1"></i>{{ __('admin/members/settings.index.custom_session_disabled') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- 認証設定 -->
        <a href="{{ route('admin.members.settings.auth') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-green-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/members/settings.nav.auth') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>
                    {{ __('admin/members/settings.index.two_factor') }}:
                    @if($force2fa == 0)
                        <span class="text-gray-500">{{ __('common.disabled') }}</span>
                    @elseif($force2fa == 1)
                        <span class="text-blue-600 dark:text-blue-400">{{ __('admin/members/settings.index.optional') }}</span>
                    @else
                        <span class="text-green-600 dark:text-green-400">{{ __('admin/members/settings.index.required') }}</span>
                    @endif
                </p>
                <p class="text-xs mt-1">
                    {{ __('admin/members/settings.index.login_attempt_limit') }}:
                    @if($loginAttemptLimitEnabled)
                        <span class="text-green-600 dark:text-green-400">{{ __('common.enabled') }}</span>
                    @else
                        <span class="text-gray-500">{{ __('common.disabled') }}</span>
                    @endif
                </p>
            </div>
        </a>

        <!-- 権限設定 -->
        <a href="{{ route('admin.members.settings.roles') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-user-shield text-orange-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.members.roles_short') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/members/settings.index.roles_description') }}</p>
            </div>
        </a>
    </div>

    <!-- 強制ログアウト -->
    <section class="mt-8">
        <h2 class="text-xl font-semibold mb-4">{{ __('admin/members/settings.index.force_logout_heading') }}</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('admin/members/settings.index.force_logout_description') }}</p>
            <form method="POST" action="{{ route('admin.members.force-logout-all') }}" id="force-logout-all-form">
                @csrf
                <x-form.button
                    type="button"
                    variant="warning"
                    onclick="openModal('forceLogoutAllModal')"
                >
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    {{ __('admin/members/settings.index.force_logout_all_button') }}
                </x-form.button>
            </form>
        </div>
    </section>
</div>

<x-modal
    id="forceLogoutAllModal"
    :title="__('admin/members/settings.index.force_logout_all_modal.title')"
    :message="__('admin/members/settings.index.force_logout_all_modal.message')"
    :confirm_label="__('admin/members/settings.index.force_logout_all_modal.confirm_label')"
    :cancel_label="__('common.cancel')"
    form="force-logout-all-form"
    icon_type="warning"
/>
@endsection
