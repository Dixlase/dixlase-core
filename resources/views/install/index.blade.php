@extends('layouts.install')

@section('title', __('admin/install.title'))
@section('header', __('admin/install.header'))
@section('description', __('admin/install.description'))

@section('content')

    <!-- ✅ 環境チェック -->
    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">{{ __('admin/install.server_requirements') }}</h2>
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
            <li>
                <strong>PHP 8.2+</strong>:
                <span class="{{ $requirements['php'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['php'] ? __('admin/install.ok') : __('admin/install.failed') }}
                </span>
            </li>
            @foreach ($requirements['required_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('admin/install.required') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $status ? __('admin/install.ok') : __('admin/install.failed') }}
                    </span>
                </li>
            @endforeach
            @foreach ($requirements['optional_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('admin/install.optional') }}):
                    <span class="{{ $status ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $status ? __('admin/install.ok') : __('admin/install.not_required') }}
                    </span>
                </li>
            @endforeach
            <li>
                <strong>{{ __('admin/install.permissions.storage') }}</strong>:
                <span class="{{ $requirements['permissions']['storage'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['permissions']['storage'] ? __('admin/install.ok') : __('admin/install.failed') }}
                </span>
            </li>
            <li>
                <strong>{{ __('admin/install.permissions.cache') }}</strong>:
                <span class="{{ $requirements['permissions']['bootstrap/cache'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $requirements['permissions']['bootstrap/cache'] ? __('admin/install.ok') : __('admin/install.failed') }}
                </span>
            </li>
        </ul>
    </div>

    <!-- ✅ インストールボタン -->
    @php
        $hasRequiredIssues = !$requirements['php'] || 
                           in_array(false, $requirements['required_extensions']) || 
                           in_array(false, $requirements['permissions']);
    @endphp
    <div class="flex justify-center">
        <a href="{{ $hasRequiredIssues ? '#' : route('install.settings') }}"
        class="block w-auto bg-blue-600 dark:bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition text-center
                {{ $hasRequiredIssues ? 'opacity-50 cursor-not-allowed' : '' }}"
        {{ $hasRequiredIssues ? 'disabled' : '' }}>
            {{ __('admin/install.start_button') }}
        </a>
    </div>
    
    @if($hasRequiredIssues)
        <p class="text-sm text-red-600 dark:text-red-400 mt-2 text-center">
            {{ __('admin/install.required_issues') }}
        </p>
    @endif

@endsection