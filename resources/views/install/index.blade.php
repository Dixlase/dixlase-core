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
      (see LICENSE.commercial, or contact info@dixlase.org).

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

@section('title', __('install/common.title'))
@section('header', __('install/common.header'))
@section('description', __('install/index.description'))

@section('content')

    @php
        // 各カテゴリのNG件数を事前計算
        $phpOk = $requirements['php'];
        $phpSettingsOk = true;
        foreach ($requirements['php_settings'] as $info) {
            if (!$info['ok']) { $phpSettingsOk = false; break; }
        }
        $phpAllOk = $phpOk && $phpSettingsOk;
        $phpTotal = 1 + count($requirements['php_settings']);
        $phpFailed = ($phpOk ? 0 : 1) + collect($requirements['php_settings'])->filter(fn($i) => !$i['ok'])->count();

        $extFailed = collect($requirements['required_extensions'])->filter(fn($s) => !$s)->count();
        $extTotal = count($requirements['required_extensions']);
        $extAllOk = $extFailed === 0;

        $permFailed = collect($requirements['permissions'])->filter(fn($w) => !$w)->count();
        $permTotal = count($requirements['permissions']);
        $permAllOk = $permFailed === 0;

        $otherAllOk = $requirements['has_theme'];

        $hasRequiredIssues = !$phpAllOk || !$extAllOk || !$permAllOk || !$otherAllOk;
    @endphp

    {{-- Required checks --}}
    <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">{{ __('install/index.required_section') }}</h2>

        {{-- PHP --}}
        <div x-data="{ open: {{ $phpAllOk ? 'false' : 'true' }} }" class="border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    PHP
                    @if($phpAllOk)
                        <span class="text-green-600 dark:text-green-400 font-normal ml-1">{{ $phpTotal }}/{{ $phpTotal }} OK</span>
                    @else
                        <span class="text-red-600 dark:text-red-400 font-normal ml-1">{{ $phpFailed }} {{ __('install/common.failed') }}</span>
                    @endif
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
            </button>
            <ul x-show="open" x-cloak x-collapse class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4 pb-2">
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
        </div>

        {{-- Extensions --}}
        <div x-data="{ open: {{ $extAllOk ? 'false' : 'true' }} }" class="border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('install/index.category.extensions') }}
                    @if($extAllOk)
                        <span class="text-green-600 dark:text-green-400 font-normal ml-1">{{ $extTotal }}/{{ $extTotal }} OK</span>
                    @else
                        <span class="text-red-600 dark:text-red-400 font-normal ml-1">{{ $extFailed }} {{ __('install/common.failed') }}</span>
                    @endif
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
            </button>
            <ul x-show="open" x-cloak x-collapse class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4 pb-2">
                @foreach ($requirements['required_extensions'] as $ext => $status)
                    <li>
                        <strong>{{ $ext }}</strong>:
                        <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $status ? __('install/common.ok') : __('install/common.failed') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Permissions --}}
        <div x-data="{ open: {{ $permAllOk ? 'false' : 'true' }} }" class="border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('install/index.category.permissions') }}
                    @if($permAllOk)
                        <span class="text-green-600 dark:text-green-400 font-normal ml-1">{{ $permTotal }}/{{ $permTotal }} OK</span>
                    @else
                        <span class="text-red-600 dark:text-red-400 font-normal ml-1">{{ $permFailed }} {{ __('install/common.failed') }}</span>
                    @endif
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
            </button>
            <ul x-show="open" x-cloak x-collapse class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4 pb-2">
                @foreach ($requirements['permissions'] as $dir => $writable)
                    <li>
                        <strong>{{ $dir }}</strong> ({{ __('install/index.permissions.writable_required') }}):
                        <span class="{{ $writable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $writable ? __('install/common.ok') : __('install/common.failed') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Other --}}
        <div x-data="{ open: {{ $otherAllOk ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-left">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('install/index.category.other') }}
                    @if($otherAllOk)
                        <span class="text-green-600 dark:text-green-400 font-normal ml-1">OK</span>
                    @else
                        <span class="text-red-600 dark:text-red-400 font-normal ml-1">1 {{ __('install/common.failed') }}</span>
                    @endif
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
            </button>
            <ul x-show="open" x-cloak x-collapse class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-4 pb-2">
                <li>
                    <strong>{{ __('install/index.theme_check.label') }}</strong>:
                    <span class="{{ $requirements['has_theme'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $requirements['has_theme'] ? __('install/common.ok') : __('install/index.theme_check.not_found') }}
                    </span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Theme download (shown only when no theme is present and there is a registered downloadable) --}}
    @if(!$requirements['has_theme'] && count($missingDownloadableThemes) > 0)
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"
             x-data="installThemeDownload({
                 endpoint: '{{ route('install.download-theme') }}',
                 csrf: '{{ csrf_token() }}',
                 modalId: 'installThemeDownloadModal',
                 invalidMessage: @js(__('install/index.theme_download.invalid')),
             })">
            <h2 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-download mr-1"></i>{{ __('install/index.theme_download.heading') }}
            </h2>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                {{ __('install/index.theme_download.description') }}
            </p>
            <ul class="space-y-2">
                @foreach($missingDownloadableThemes as $theme)
                    <li class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $theme['label'] }}</span>
                        <button type="button"
                                @click="downloadTheme(@js($theme['directory']))"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="downloading">
                            <i class="fas fa-download mr-1"></i>
                            {{ __('install/index.theme_download.button', ['label' => $theme['label']]) }}
                        </button>
                    </li>
                @endforeach
            </ul>
            <p x-show="errorMessage" x-cloak x-text="errorMessage"
               class="text-sm text-red-600 dark:text-red-400 mt-3"></p>
        </div>

        {{-- Download progress modal --}}
        <x-ui-modal
            id="installThemeDownloadModal"
            :title="__('install/index.theme_download.progress_title')"
            message=""
            iconType="info"
            :dismissible="false"
            :closeOnly="true"
            :centered="true"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
                {!! __('install/index.theme_download.progress_message') !!}
            </p>
            <x-slot:footer>
                <div class="flex items-center justify-center w-full py-1">
                    <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
                </div>
            </x-slot:footer>
        </x-ui-modal>
    @endif

    {{-- Recommended/optional checks --}}
    @php
        $recommendedPhpSettings = $requirements['php_settings_recommended'] ?? [];
        $recOptAllOk = !in_array(false, $requirements['recommended_extensions'])
                       && !in_array(false, $requirements['optional_extensions'])
                       && !collect($recommendedPhpSettings)->contains(fn($i) => !$i['ok']);
        $recOptTotal = count($requirements['recommended_extensions'])
                       + count($requirements['optional_extensions'])
                       + count($recommendedPhpSettings);
        $recOptFailed = collect($requirements['recommended_extensions'])->filter(fn($s) => !$s)->count()
                      + collect($requirements['optional_extensions'])->filter(fn($s) => !$s)->count()
                      + collect($recommendedPhpSettings)->filter(fn($i) => !$i['ok'])->count();
    @endphp
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600"
         x-data="{ open: {{ $recOptAllOk ? 'false' : 'true' }} }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-left">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                {{ __('install/index.recommended_section') }}
                @if($recOptAllOk)
                    <span class="text-green-600 dark:text-green-400 text-sm font-normal ml-2">{{ $recOptTotal }}/{{ $recOptTotal }} OK</span>
                @else
                    <span class="text-yellow-600 dark:text-yellow-400 text-sm font-normal ml-2">{{ $recOptFailed }} {{ __('install/common.not_installed') }}</span>
                @endif
            </h2>
            <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
        </button>
        <ul x-show="open" x-cloak x-collapse class="text-sm text-gray-700 dark:text-gray-300 space-y-1 ml-2 mt-2">
            @foreach ($recommendedPhpSettings as $setting => $info)
                <li>
                    <strong>{{ $setting }}</strong> ({{ __('install/common.recommended') }}: {{ $info['required'] }}):
                    <span class="{{ $info['ok'] ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $info['current'] }}{{ $info['ok'] ? ' ' . __('install/common.ok') : '' }}
                    </span>
                </li>
            @endforeach
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
