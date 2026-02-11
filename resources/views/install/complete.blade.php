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

    <div class="flex flex-col justify-center items-center space-y-6">
        <!-- ✅ フロントページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install/complete.site_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="site-url" class="text-blue-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $appUrl }}</strong>
                <button type="button" 
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition" 
                    title="コピー"
                    x-on:click="navigator.clipboard.writeText(document.getElementById('site-url').textContent)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- ✅ 管理者ログインページURL -->
        <div class="flex flex-col justify-center items-center space-y-2">
            <p class="text-gray-700 dark:text-gray-300 font-semibold">{{ __('install/complete.admin_login_url') }}</p>
            <div class="flex items-center space-x-2">
                <strong id="admin-url" class="text-gray-600 dark:text-white px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded break-words">{{ $adminLoginUrl }}</strong>
                <button type="button" 
                    class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded transition" 
                    title="コピー"
                    x-on:click="navigator.clipboard.writeText(document.getElementById('admin-url').textContent)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
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
            <li>{{ __('install/step2.admin_url') }}: /admin</li>
            <li>{{ __('install/step2.force_ssl') }}: {{ __('install/mode.ssl_on') }}</li>
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