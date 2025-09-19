@extends('layouts.install')

@section('title', __('install.title'))
@section('header', __('install.header'))
@section('description', __('install.description'))

@section('content')

    <!-- ✅ 環境チェック -->
    <div class="mb-6 p-4 bg-gray-100 rounded-lg">
        <h2 class="text-lg font-bold text-gray-800 mb-2">{{ __('install.server_requirements') }}</h2>
        <ul class="text-sm space-y-1">
            <li>
                <strong>PHP 8.2+</strong>:
                <span class="{{ $requirements['php'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['php'] ? __('install.ok') : __('install.failed') }}
                </span>
            </li>
            @foreach ($requirements['required_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.required') }}):
                    <span class="{{ $status ? 'text-green-600' : 'text-red-600' }}">
                        {{ $status ? __('install.ok') : __('install.failed') }}
                    </span>
                </li>
            @endforeach
            @foreach ($requirements['optional_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.optional') }}):
                    <span class="{{ $status ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $status ? __('install.ok') : __('install.not_required') }}
                    </span>
                </li>
            @endforeach
            <li>
                <strong>{{ __('install.permissions.storage') }}</strong>:
                <span class="{{ $requirements['permissions']['storage'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['permissions']['storage'] ? __('install.ok') : __('install.failed') }}
                </span>
            </li>
            <li>
                <strong>{{ __('install.permissions.cache') }}</strong>:
                <span class="{{ $requirements['permissions']['bootstrap/cache'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['permissions']['bootstrap/cache'] ? __('install.ok') : __('install.failed') }}
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
        class="block w-auto bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-center
                {{ $hasRequiredIssues ? 'opacity-50 cursor-not-allowed' : '' }}"
        {{ $hasRequiredIssues ? 'disabled' : '' }}>
            {{ __('install.start_button') }}
        </a>
    </div>
    
    @if($hasRequiredIssues)
        <p class="text-sm text-red-600 mt-2">
            {{ __('install.required_issues') }}
        </p>
    @endif

@endsection