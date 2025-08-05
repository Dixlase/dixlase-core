@extends('layouts.install')

@section('title', __('install.complete_title'))

@section('content')

    <h1 class="text-2xl font-bold text-gray-800 mb-4">{{ __('install.complete_title') }}</h1>
    <p class="text-gray-600 mb-6">{{ __('install.complete_message') }}</p>

    <div class="text-left space-y-4">
        <!-- ✅ フロントページURL -->
        <div>
            <p class="text-gray-700 font-semibold">{{ __('install.site_url') }}</p>
            <a href="{{ $appUrl }}" class="text-blue-600 underline break-words">{{ $appUrl }}</a>
        </div>

        <!-- ✅ 管理者ログインページURL -->
        <div>
            <p class="text-gray-700 font-semibold">{{ __('install.admin_login_url') }}</p>
            <a href="{{ $adminLoginUrl }}" class="text-red-600 underline break-words">{{ $adminLoginUrl }}</a>
        </div>
    </div>

    <div class="mt-6 space-y-4">
        <!-- ✅ フロントページへのリンク -->
        <a href="{{ $appUrl }}"
            class="block w-full bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700 transition">
            {{ __('install.go_to_site') }}
        </a>

        <!-- ✅ 管理画面トップへのリンク -->
        <a href="{{ $adminLoginUrl }}"
            class="block w-full bg-green-600 text-white py-2 px-4 rounded-lg text-center hover:bg-green-700 transition">
            {{ __('install.go_to_admin') }}
        </a>

    </div>

@endsection