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

    <!-- ✅ 環境チェック -->
    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">{{ __('install/index.server_requirements') }}</h2>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
            <li>
                <strong>PHP 8.2+</strong>:
                <span class="{{ $requirements['php'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['php'] ? __('install/common.ok') : __('install/common.failed') }}
                </span>
            </li>
            @foreach ($requirements['required_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.required') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $status ? __('install/common.ok') : __('install/common.failed') }}
                    </span>
                </li>
            @endforeach
            @foreach ($requirements['optional_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.optional') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $status ? __('install/common.ok') : __('install.not_required') }}
                    </span>
                </li>
            @endforeach
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

    <!-- ✅ インストールボタン -->
    @php
        $hasRequiredIssues = !$requirements['php'] || 
                           in_array(false, $requirements['required_extensions']) || 
                           in_array(false, $requirements['permissions']);
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
            {{ __('install.required_issues') }}
        </p>
    @endif

@endsection