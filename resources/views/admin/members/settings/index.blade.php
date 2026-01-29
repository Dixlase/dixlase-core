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
        <!-- パスワード設定（セキュリティ設定に移動） -->
        <a href="{{ route('admin.settings.security.password') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-key text-blue-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/members/settings/index.nav.password') }}</h3>
                </div>
                <i class="fas fa-external-link-alt text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="text-blue-600 dark:text-blue-400">
                    <i class="fas fa-arrow-right mr-1"></i>{{ __('admin/members/settings/index.managed_in_security_settings') }}
                </p>
            </div>
        </a>

        <!-- 認証設定 -->
        <a href="{{ route('admin.members.settings.auth') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-green-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/members/settings/index.nav.auth') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>
                    {{ __('admin/members/settings/index.two_fa') }}:
                    @if($twoFaForceMode == 0)
                        <span class="text-gray-500">{{ __('common.disabled') }}</span>
                    @elseif($twoFaForceMode == 1)
                        <span class="text-blue-600 dark:text-blue-400">{{ __('admin/members/settings/index.optional') }}</span>
                    @else
                        <span class="text-green-600 dark:text-green-400">{{ __('admin/members/settings/index.required') }}</span>
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
                <p>{{ __('admin/members/settings/index.roles_description') }}</p>
            </div>
        </a>
    </div>

    <!-- 強制ログアウト -->
    <section class="mt-8">
        <h2 class="text-xl font-semibold mb-4">{{ __('admin/members/settings/index.force_logout_heading') }}</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('admin/members/settings/index.force_logout_description') }}</p>
            <form method="POST" action="{{ route('admin.members.force-logout-all') }}" id="force-logout-all-form">
                @csrf
                <x-form.button
                    type="button"
                    variant="warning"
                    onclick="openModal('forceLogoutAllModal')"
                >
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    {{ __('admin/members/settings/index.force_logout_all_button') }}
                </x-form.button>
            </form>
        </div>
    </section>
</div>

<x-ui.modal
    id="forceLogoutAllModal"
    :title="__('admin/members/settings/index.force_logout_all_modal.title')"
    :message="__('admin/members/settings/index.force_logout_all_modal.message')"
    :confirm_label="__('admin/members/settings/index.force_logout_all_modal.confirm_label')"
    :cancel_label="__('common.cancel')"
    form="force-logout-all-form"
    icon_type="warning"
/>
@endsection
