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

    <!-- セキュリティステータスカード -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <!-- パスワード -->
        <a href="{{ route('admin.settings.security.password') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-key text-blue-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.password') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/settings/security/index.password_security') }}</p>
            </div>
        </a>

        <!-- ログイン試行制限 -->
        <a href="{{ route('admin.settings.security.login') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-sign-in-alt text-indigo-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.login_attempt') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/settings/security/index.login_attempt_desc') }}</p>
            </div>
        </a>

        <!-- 二段階認証設定 -->
        <a href="{{ route('admin.settings.security.two-fa') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-user-shield text-teal-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.two-fa') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/settings/security/index.two_fa_desc') }}</p>
            </div>
        </a>

        <!-- CAPTCHA -->
        <a href="{{ route('admin.settings.security.captcha') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-robot text-purple-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.captcha') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($captchaEnabled)
                    @if($captchaTestResult)
                        <span class="inline-flex items-center text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/index.captcha_active') }}
                        </span>
                    @else
                        <span class="inline-flex items-center text-yellow-600 dark:text-yellow-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin/settings/security/index.captcha_test_required') }}
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('admin/settings/security/index.captcha_disabled') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- セッション -->
        <a href="{{ route('admin.settings.security.session') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-clock text-green-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.session') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p>{{ __('admin/settings/security/index.session_driver') }}: <span class="font-medium">{{ $sessionDriver }}</span></p>
            </div>
        </a>

        <!-- 通知 -->
        <a href="{{ route('admin.settings.security.notifications') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-bell text-yellow-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.notifications') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($notificationEnabled)
                    @if($mailTestComplete)
                        <span class="inline-flex items-center text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/index.notifications_active') }}
                        </span>
                    @else
                        <span class="inline-flex items-center text-yellow-600 dark:text-yellow-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin/settings/security/index.mail_test_required') }}
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('admin/settings/security/index.notifications_disabled') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- CSP -->
        <a href="{{ route('admin.settings.security.csp') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-code text-orange-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.csp') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($cspEnabled)
                    <span class="inline-flex items-center text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/index.csp_mode') }}: {{ $cspMode }}
                    </span>
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('admin/settings/security/index.csp_disabled') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- 拡張機能 -->
        <a href="{{ route('admin.settings.security.extensions') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-puzzle-piece text-pink-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.extensions') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin/settings/security/index.extensions_desc') }}
            </div>
        </a>

        <!-- IPアクセス制御 -->
        <a href="{{ route('admin.settings.security.ip') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-network-wired text-cyan-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.ip') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                @if($enableAllowedAdminIps || $enableBlockedAdminIps)
                    <span class="inline-flex items-center text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/index.ip_active') }}
                    </span>
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-minus-circle mr-1"></i>{{ __('admin/settings/security/index.ip_inactive') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- ファイル整合性 -->
        <a href="{{ route('admin.settings.security.integrity') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-file-shield text-red-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.integrity') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @if($latestIntegrityAudit)
                    @if($latestIntegrityAudit->status === \App\Models\FileIntegrityAudit::STATUS_OK)
                        <span class="inline-flex items-center text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/index.integrity_ok') }}
                        </span>
                    @elseif($latestIntegrityAudit->status === \App\Models\FileIntegrityAudit::STATUS_WARNING)
                        <span class="inline-flex items-center text-yellow-600 dark:text-yellow-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin/settings/security/index.integrity_warning') }}
                        </span>
                    @else
                        <span class="inline-flex items-center text-red-600 dark:text-red-400">
                            <i class="fas fa-times-circle mr-1"></i>{{ __('admin/settings/security/index.integrity_critical') }}
                        </span>
                    @endif
                @elseif(!$hasBaseline)
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-minus-circle mr-1"></i>{{ __('admin/settings/security/index.integrity_no_baseline') }}
                    </span>
                @else
                    <span class="inline-flex items-center text-gray-500">
                        <i class="fas fa-minus-circle mr-1"></i>{{ __('admin/settings/security/index.integrity_not_scanned') }}
                    </span>
                @endif
            </div>
        </a>

        <!-- 環境設定 -->
        <a href="{{ route('admin.settings.security.environment') }}" class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <i class="fas fa-cog text-gray-500 text-xl mr-3"></i>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('admin/nav.settings.security.environment') }}</h3>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <div class="text-sm">
                @php
                    $envColors = [
                        'local' => 'text-blue-600 dark:text-blue-400',
                        'staging' => 'text-yellow-600 dark:text-yellow-400',
                        'production' => 'text-green-600 dark:text-green-400',
                    ];
                    $envIcons = [
                        'local' => 'fa-laptop-code',
                        'staging' => 'fa-flask',
                        'production' => 'fa-server',
                    ];
                @endphp
                <span class="inline-flex items-center {{ $envColors[$appEnv] ?? $envColors['local'] }}">
                    <i class="fas {{ $envIcons[$appEnv] ?? $envIcons['local'] }} mr-1"></i>{{ __('admin/settings/security/environment.env_options.' . $appEnv) }}
                </span>
                @if($appDebug)
                    <span class="inline-flex items-center text-red-600 dark:text-red-400 ml-2">
                        <i class="fas fa-bug mr-1"></i>{{ __('admin/settings/security/index.debug_enabled') }}
                    </span>
                @endif
            </div>
        </a>
    </div>

    <!-- 最新のファイル整合性スキャン結果 -->
    @if($latestIntegrityAudit)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin/settings/security/index.latest_integrity_scan') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/index.scan_date') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $latestIntegrityAudit->created_at->format('Y-m-d H:i') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/index.files_scanned') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $latestIntegrityAudit->total_files_scanned }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/index.status') }}</p>
                @if($latestIntegrityAudit->status === \App\Models\FileIntegrityAudit::STATUS_OK)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ __('admin/settings/security/integrity.status_ok') }}
                    </span>
                @elseif($latestIntegrityAudit->status === \App\Models\FileIntegrityAudit::STATUS_WARNING)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        {{ __('admin/settings/security/integrity.status_warning') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        {{ __('admin/settings/security/integrity.status_critical') }}
                    </span>
                @endif
            </div>
            <div>
                <a href="{{ route('admin.settings.security.integrity') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline">
                    {{ __('admin/settings/security/index.view_details') }}
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
