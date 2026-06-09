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

@extends('layouts.install')

@section('title', __('install/complete.complete_title'))
@section('header', __('install/complete.complete_header'))

@section('description')
    {!! __('install/complete.complete_message') !!}
@endsection

@php
    // 完了画面では言語切り替え機能を無効化
    $availableLocales = null;
    $currentLocale = null;
@endphp

@section('content')

    <div class="flex flex-col justify-center items-center space-y-6" x-data="{ copiedSite: false, copiedAdmin: false }">
        <!-- フロントページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install/complete.site_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="site-url" class="text-blue-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $appUrl }}</strong>
                <button type="button"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition"
                    :title="copiedSite ? '{{ __('install/complete.copied') }}' : '{{ __('install/complete.copy') }}'"
                    x-on:click="navigator.clipboard.writeText(document.getElementById('site-url').textContent); copiedSite = true; setTimeout(() => copiedSite = false, 2000)">
                    <i x-show="!copiedSite" class="fas fa-copy text-gray-700 dark:text-gray-300"></i>
                    <i x-show="copiedSite" x-cloak class="fas fa-check text-green-600 dark:text-green-400"></i>
                </button>
            </div>
        </div>

        <!-- 管理者ログインページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install/complete.admin_login_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="admin-url" class="text-gray-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $adminLoginUrl }}</strong>
                <button type="button"
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition"
                    :title="copiedAdmin ? '{{ __('install/complete.copied') }}' : '{{ __('install/complete.copy') }}'"
                    x-on:click="navigator.clipboard.writeText(document.getElementById('admin-url').textContent); copiedAdmin = true; setTimeout(() => copiedAdmin = false, 2000)">
                    <i x-show="!copiedAdmin" class="fas fa-copy text-gray-700 dark:text-gray-300"></i>
                    <i x-show="copiedAdmin" x-cloak class="fas fa-check text-green-600 dark:text-green-400"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 管理画面URLのブックマーク推奨メッセージ -->
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
            <div>
                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-1">{{ __('install/complete.admin_url_notice_title') }}</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-400">{{ __('install/complete.admin_url_notice_message') }}</p>
                @if($isSimpleMode)
                    <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">{{ __('install/complete.admin_url_random_notice') }}</p>
                @endif
            </div>
        </div>
    </div>

    @if($isSimpleMode)
    <!-- かんたんモード: 自動設定された項目のガイダンス -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">{{ __('install/mode.auto_configured_title') }}</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 mb-3">
            <li>{{ __('install/step2.app_env') }}: {{ __('install/mode.app_env_production') }}</li>
            <li>{{ __('install/step2.app_debug') }}: {{ __('install/mode.debug_off') }}</li>
            <li>{{ __('install/step2.admin_url') }}: /{{ $adminSlug }}</li>
            <li>{{ __('install/step2.force_ssl') }}: {{ $forceSslEnabled ? __('install/common.enabled') : __('install/common.disabled') }}</li>
        </ul>
        <p class="text-xs text-blue-600 dark:text-blue-500">{{ __('install/mode.auto_configured_changeable') }}</p>
    </div>
    @endif

    <div class="flex flex-col items-center justify-center mt-6 space-y-4">
        <!-- ✅ フロントページへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $appUrl }}">
                <button type="submit" class="block w-auto bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700 transition">
                    {{ __('install/complete.go_to_site') }}
                </button>
            </form>
        </div>

        <!-- ✅ 管理画面トップへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $adminLoginUrl }}">
                <button type="submit" class="block w-auto bg-green-600 text-white py-2 px-4 rounded-lg text-center hover:bg-green-700 transition">
                    {{ __('install/complete.go_to_admin') }}
                </button>
            </form>
        </div>

    </div>

@endsection