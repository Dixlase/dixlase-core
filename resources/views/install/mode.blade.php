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

@extends('layouts.install')

@section('title', __('install/mode.mode_title'))
@section('header', __('install/mode.mode_header'))
@section('description', __('install/mode.mode_description'))

@section('content')

<form action="{{ route('install.mode.store') }}" method="POST" x-data="{ selectedMode: {{ $selectedMode }} }">
    @csrf

    <div class="grid grid-cols-2 gap-8">
        {{-- Simple mode --}}
        <label class="block cursor-pointer">
            <input type="radio" name="install_mode" value="0" x-model="selectedMode" class="sr-only peer">
            <div class="border-2 rounded-lg p-5 h-full transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-magic text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center">{{ __('install/mode.simple_mode') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('install/mode.simple_mode_description') }}</p>
                        <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.simple_feature_security') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.simple_feature_quick') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.simple_feature_easy') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </label>

        {{-- Advanced mode --}}
        <label class="block cursor-pointer">
            <input type="radio" name="install_mode" value="1" x-model="selectedMode" class="sr-only peer">
            <div class="border-2 rounded-lg p-5 h-full transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                        <i class="fas fa-cogs text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center">{{ __('install/mode.advanced_mode') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('install/mode.advanced_mode_description') }}</p>
                        <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.advanced_feature_customize') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.advanced_feature_control') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.advanced_feature_admin_url') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                {{ __('install/mode.advanced_feature_audience') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </label>
    </div>

    <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        <i class="fas fa-info-circle mr-1"></i>
        {{ __('install/mode.can_change_later') }}
    </p>

    {{-- Navigation --}}
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-center mt-6">
        <a href="{{ route('install.index') }}"
            class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 mx-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install/common.back') }}
        </a>
        <x-form-button
            type="submit"
            variant="primary"
            :label="__('install/common.next')"
            class="mx-4"
        />
    </nav>
</form>

@endsection
