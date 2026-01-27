@extends('layouts.install')

@section('title', __('install.complete_title'))
@section('header', __('install.complete_header'))

@section('description')
    {!! __('install.complete_message') !!}
@endsection

@php
    // 完了画面では言語切り替え機能を無効化
    $availableLocales = null;
    $currentLocale = null;
@endphp

@section('content')

    <div class="flex flex-col justify-center items-center space-y-6">
        <!-- ✅ フロントページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install.site_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="site-url" class="text-blue-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $appUrl }}</strong>
                <button type="button" onclick="copyToClipboard('site-url')" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition" title="コピー">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- ✅ 管理者ログインページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install.admin_login_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="admin-url" class="text-gray-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $adminLoginUrl }}</strong>
                <button type="button" onclick="copyToClipboard('admin-url')" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition" title="コピー">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center mt-6 space-y-4">
        <!-- ✅ フロントページへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $appUrl }}">
                <button type="submit" class="block w-auto bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700 transition">
                    {{ __('install.go_to_site') }}
                </button>
            </form>
        </div>

        <!-- ✅ 管理画面トップへのリンク -->
        <div>
            <form method="POST" action="{{ route('install.finalize') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $adminLoginUrl }}">
                <button type="submit" class="block w-auto bg-green-600 text-white py-2 px-4 rounded-lg text-center hover:bg-green-700 transition">
                    {{ __('install.go_to_admin') }}
                </button>
            </form>
        </div>

    </div>

@endsection