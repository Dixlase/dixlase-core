@extends('layouts.install')

@section('title', __('install.confirm_title'))
@section('header', __('install.confirm_header'))
@section('description', __('install.confirm_message'))

@section('content')

{{-- エラー表示セクション --}}
@if(session('error'))
    <aside class="bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6" role="alert" aria-labelledby="error-heading">
        <div class="flex">
            <div class="py-1" aria-hidden="true">
                <svg class="fill-current h-6 w-6 text-red-500 dark:text-red-400 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                </svg>
            </div>
            <div>
                <p id="error-heading" class="font-bold">{{ __('install.error.installation_failed') }}</p>
                <p class="text-sm">{{ session('error') }}</p>
                @if(session('error_details'))
                    <details class="mt-2">
                        <summary class="cursor-pointer text-sm font-medium">{{ __('install.error.technical_details') }}</summary>
                        <pre class="mt-2 text-xs bg-red-50 dark:bg-red-900/30 p-2 rounded border dark:border-red-800 overflow-x-auto">{{ session('error_details') }}</pre>
                    </details>
                @endif
            </div>
        </div>
    </aside>
@endif

@php
    // ✅ `force_ssl` に応じてプロトコルを決定
    $protocol = $data['force_ssl'] ? 'https://' : 'http://';

    // ✅ `app_url` にプロトコルを適用
    $fullAppUrl = $protocol . rtrim($data['app_url'], '/');

    // ✅ `admin_url` にプロトコルとベースURLを適用
    $fullAdminUrl = $fullAppUrl . '/' . ltrim($data['admin_url'], '/');
@endphp

<!-- 設定確認セクション -->
<section aria-labelledby="settings-review-heading" class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 p-4 rounded-lg mb-6 space-y-4 h-96 overflow-auto">
    <h2 id="settings-review-heading" class="sr-only">{{ __('install.settings_review') }}</h2>
    
    <!-- 基本設定 -->
    <article aria-labelledby="basic-settings-heading">
        <h3 id="basic-settings-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">基本設定</h3>
        <ul class="space-y-1 text-gray-700 dark:text-gray-300">
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.site_name') }}:</strong> {{ $data['site_name'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.admin_name') }}:</strong> {{ $data['admin_name'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.admin_email') }}:</strong> {{ $data['admin_email'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.admin_password') }}:</strong> <span class="text-gray-500 dark:text-gray-400">●●●●●</span></li>
        </ul>
    </article>

    <!-- アプリケーション設定 -->
    <article aria-labelledby="app-settings-heading">
        <h3 id="app-settings-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">アプリケーション設定</h3>
        <ul class="space-y-1 text-gray-700 dark:text-gray-300">
            <!-- ✅ アプリケーションURL（SSL反映） -->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.app_url') }}:</strong> {{ $fullAppUrl }}</li>
            <!-- ✅ SSL設定 -->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.force_ssl') }}:</strong> {{ $data['force_ssl'] ? __('install.enabled') : __('install.disabled') }}</li>
            <!-- ✅ 管理画面URL-->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.admin_url') }}:</strong> {{ url($data['admin_url']) }}</li>
        </ul>
    </article>

    <!-- データベース設定 -->
    <article aria-labelledby="database-settings-heading">
        <h3 id="database-settings-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">データベース設定</h3>
        <ul class="space-y-1 text-gray-700 dark:text-gray-300">
            <!-- ✅ DB情報 -->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_connection') }}:</strong> {{ ucfirst($data['db_connection']) }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_host') }}:</strong> {{ $data['db_host'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_port') }}:</strong> {{ $data['db_port'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_name') }}:</strong> {{ $data['db_database'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_user') }}:</strong> {{ $data['db_username'] }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.db_password') }}:</strong> <span class="text-gray-500 dark:text-gray-400">●●●●●</span></li>
        </ul>
    </article>

    <!-- メール設定 -->
    <article aria-labelledby="mail-settings-heading">
        <h3 id="mail-settings-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">メール設定</h3>
        <ul class="space-y-1 text-gray-700 dark:text-gray-300">
            <!-- ✅ メールサーバー設定 -->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mailer') }}:</strong> {{ $data['mail_mailer'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_host') }}:</strong> {{ $data['mail_host'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_port') }}:</strong> {{ $data['mail_port'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_username') }}:</strong> {{ $data['mail_username'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_password') }}:</strong> <span class="text-gray-500 dark:text-gray-400">{{ !empty($data['mail_password']) ? '●●●●●' : '' }}</span></li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_encryption') }}:</strong> {{ $data['mail_encryption'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_from_address') }}:</strong> {{ $data['mail_from_address'] ?? '' }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('mail.server_settings.mail_from_name') }}:</strong> {{ $data['mail_from_name'] ?? '' }}</li>
            
            <!-- メールテスト結果 -->
            @if(!empty($data['mail_mailer']))
                <li class="mt-3 pt-3 border-t border-gray-300 dark:border-gray-600">
                    <strong class="text-gray-900 dark:text-gray-100">{{ __('install.mail_test.title') }}:</strong>
                    <div class="ml-4 mt-2 space-y-1">
                        <!-- 接続テスト -->
                        <div class="flex items-center">
                            @if($mailTestStatus['connection_tested'])
                                <span class="text-green-600 dark:text-green-400">✅</span>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ __('install.mail_test_advanced.connection_test') }}</span>
                                @if($mailTestStatus['connection_test_date'])
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ $mailTestStatus['connection_test_date'] }})</span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">⚪</span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ __('install.mail_test_advanced.connection_test') }} - 未実行</span>
                            @endif
                        </div>
                        
                        <!-- 送信テスト -->
                        <div class="flex items-center">
                            @if($mailTestStatus['send_tested'])
                                <span class="text-green-600 dark:text-green-400">✅</span>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ __('install.mail_test_advanced.send_test') }}</span>
                                @if($mailTestStatus['send_test_date'])
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ $mailTestStatus['send_test_date'] }})</span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">⚪</span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ __('install.mail_test_advanced.send_test') }} - 未実行</span>
                            @endif
                        </div>
                        
                        <!-- 受信確認テスト -->
                        <div class="flex items-center">
                            @if($mailTestStatus['receive_tested'])
                                <span class="text-green-600 dark:text-green-400">✅</span>
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ __('install.mail_test_advanced.receive_test') }}</span>
                                @if($mailTestStatus['receive_test_date'])
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ $mailTestStatus['receive_test_date'] }})</span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">⚪</span>
                                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ __('install.mail_test_advanced.receive_test') }} - 未実行</span>
                            @endif
                        </div>
                        
                        <!-- 全体ステータス -->
                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                            @if($mailTestStatus['connection_tested'] && $mailTestStatus['send_tested'] && $mailTestStatus['receive_tested'])
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ __('install.mail_test_advanced.three_stage_test_complete') }}</span>
                            @else
                                <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ __('install.mail_test_advanced.three_stage_test_incomplete') }}</span>
                            @endif
                        </div>
                    </div>
                </li>
            @endif
        </ul>
    </article>

    <!-- セキュリティ設定 -->
    <article aria-labelledby="security-settings-heading">
        <h3 id="security-settings-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">セキュリティ設定</h3>
        <ul class="space-y-1 text-gray-700 dark:text-gray-300">
            <!-- ✅ IP制限（管理画面 & フロント） -->
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.enable_allowed_admin_ips') }}:</strong> {{ isset($data['enable_allowed_admin_ips']) && $data['enable_allowed_admin_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.allowed_admin_ips') }}:</strong>
                {{ isset($data['allowed_admin_ips']) && $data['allowed_admin_ips'] ? nl2br(e($data['allowed_admin_ips'])) : __('install.none') }}</li>

            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.enable_blocked_admin_ips') }}:</strong> {{ isset($data['enable_blocked_admin_ips']) && $data['enable_blocked_admin_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.blocked_admin_ips') }}:</strong>
                {{ isset($data['blocked_admin_ips']) && $data['blocked_admin_ips'] ? nl2br(e($data['blocked_admin_ips'])) : __('install.none') }}</li>

            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.enable_allowed_front_ips') }}:</strong> {{ isset($data['enable_allowed_front_ips']) && $data['enable_allowed_front_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.allowed_front_ips') }}:</strong>
                {{ isset($data['allowed_front_ips']) && $data['allowed_front_ips'] ? nl2br(e($data['allowed_front_ips'])) : __('install.none') }}</li>

            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.enable_blocked_front_ips') }}:</strong> {{ isset($data['enable_blocked_front_ips']) && $data['enable_blocked_front_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
            <li><strong class="text-gray-900 dark:text-gray-100">{{ __('install.blocked_front_ips') }}:</strong>
                {{ isset($data['blocked_front_ips']) && $data['blocked_front_ips'] ? nl2br(e($data['blocked_front_ips'])) : __('install.none') }}</li>
        </ul>
    </article>
</section>

<!-- 確認メッセージ -->
<p class="text-center text-gray-700 dark:text-gray-300 mb-4">{{ __('install.confirm_description') }}</p>

<!-- インストール実行フォーム -->
<form action="{{ route('install.confirm.store') }}" method="POST" class="space-y-4">
    @csrf
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between">
        <a href="{{ route('install.security') }}"
           class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install.back_button') }}
        </a>
        <x-form.button
            type="submit"
            variant="primary"
            :label="__('install.confirm_button')"
        />
    </nav>
</form>
@endsection