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

@extends('layouts.install')

@section('title', __('install/common.title'))
@section('header', __('install/common.header'))
@section('description', __('install/index.description'))

@section('content')

    {{-- 必須チェック --}}
    <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3">{{ __('install/index.required_section') }}</h2>

        {{-- PHP --}}
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-3 mb-1">PHP</h3>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2">
            <li>
                <strong>PHP 8.2+</strong>:
                <span class="{{ $requirements['php'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['php'] ? __('install/common.ok') : __('install/common.failed') }}
                </span>
            </li>
            @foreach ($requirements['php_settings'] as $setting => $info)
                <li>
                    <strong>{{ $setting }}</strong> ({{ __('install/index.php_settings.required') }}: {{ $info['required'] }}):
                    <span class="{{ $info['ok'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $info['current'] }} {{ $info['ok'] ? __('install/common.ok') : __('install/common.failed') }}
                    </span>
                </li>
            @endforeach
        </ul>

        {{-- 拡張機能 --}}
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-3 mb-1">{{ __('install/index.category.extensions') }}</h3>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2">
            @foreach ($requirements['required_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong>:
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $status ? __('install/common.ok') : __('install/common.failed') }}
                    </span>
                </li>
            @endforeach
        </ul>

        {{-- パーミッション --}}
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-3 mb-1">{{ __('install/index.category.permissions') }}</h3>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2">
            @foreach ($requirements['permissions'] as $dir => $writable)
                <li>
                    <strong>{{ $dir }}</strong> ({{ __('install/index.permissions.writable_required') }}):
                    <span class="{{ $writable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $writable ? __('install/common.ok') : __('install/common.failed') }}
                    </span>
                </li>
            @endforeach
        </ul>

        {{-- その他 --}}
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-3 mb-1">{{ __('install/index.category.other') }}</h3>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2">
            <li>
                <strong>{{ __('install/index.theme_check.label') }}</strong>:
                <span class="{{ $requirements['has_theme'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['has_theme'] ? __('install/common.ok') : __('install/index.theme_check.not_found') }}
                </span>
            </li>
        </ul>
    </div>

    {{-- 推奨・オプションチェック --}}
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-3">{{ __('install/index.recommended_section') }}</h2>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2">
            @foreach ($requirements['recommended_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install/common.recommended') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $status ? __('install/common.ok') : __('install/common.not_installed') }}
                    </span>
                </li>
            @endforeach
            @foreach ($requirements['optional_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install/common.optional') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $status ? __('install/common.ok') : __('install/common.not_installed') }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    @php
        $hasRequiredIssues = !$requirements['php'] ||
                           in_array(false, $requirements['required_extensions']) ||
                           in_array(false, $requirements['permissions']) ||
                           !$requirements['has_theme'];
        foreach ($requirements['php_settings'] as $info) {
            if (!$info['ok']) {
                $hasRequiredIssues = true;
                break;
            }
        }
    @endphp
    <div class="flex justify-center">
        <a href="{{ $hasRequiredIssues ? '#' : route('install.mode') }}"
        class="block w-auto bg-blue-600 dark:bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition text-center
                {{ $hasRequiredIssues ? 'opacity-50 cursor-not-allowed' : '' }}"
        {{ $hasRequiredIssues ? 'disabled' : '' }}>
            {{ __('install/index.start_button') }}
        </a>
    </div>
    
    @if($hasRequiredIssues)
        <p class="text-sm text-red-600 dark:text-red-400 mt-2 text-center">
            {{ __('install/common.required_issues') }}
        </p>
    @endif

@endsection