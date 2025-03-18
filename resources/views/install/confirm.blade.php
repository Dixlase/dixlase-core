@extends('layouts.install')

@section('title', __('install.confirm_title'))
@section('header', __('install.confirm_header'))
@section('description', __('install.confirm_message'))

@section('content')

@php
    // ✅ `force_ssl` に応じてプロトコルを決定
    $protocol = $data['force_ssl'] ? 'https://' : 'http://';

    // ✅ `app_url` にプロトコルを適用
    $fullAppUrl = $protocol . rtrim($data['app_url'], '/');

    // ✅ `admin_url` にプロトコルとベースURLを適用
    $fullAdminUrl = $fullAppUrl . '/' . ltrim($data['admin_url'], '/');
@endphp

    <ul class="bg-gray-50 border border-gray-300 p-4 rounded-lg mb-6 space-y-2">
        <!-- ✅ 基本情報 -->
        <li><strong>{{ __('install.site_name') }}:</strong> {{ $data['site_name'] }}</li>
        <li><strong>{{ __('install.admin_name') }}:</strong> {{ $data['admin_name'] }}</li>
        <li><strong>{{ __('install.admin_email') }}:</strong> {{ $data['admin_email'] }}</li>
        <li><strong>{{ __('install.admin_password') }}:</strong> <span class="text-gray-500">●●●●●</span></li>

        <!-- ✅ アプリケーションURL（SSL反映） -->
        <li><strong>{{ __('install.app_url') }}:</strong> {{ $fullAppUrl }}</li>
        <!-- ✅ SSL設定 -->
        <li><strong>{{ __('install.force_ssl') }}:</strong> {{ $data['force_ssl'] ? __('install.enabled') : __('install.disabled') }}</li>

        <!-- ✅ 管理画面URL-->
        <li><strong>{{ __('install.admin_url') }}:</strong> {{ url($data['admin_url']) }}</li>

        <!-- ✅ IP制限（管理画面 & フロント） -->
        <li><strong>{{ __('install.enable_allowed_admin_ips') }}:</strong> {{ isset($data['enable_allowed_admin_ips']) && $data['enable_allowed_admin_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
        <li><strong>{{ __('install.allowed_admin_ips') }}:</strong>
            {{ isset($data['allowed_admin_ips']) && $data['allowed_admin_ips'] ? nl2br(e($data['allowed_admin_ips'])) : __('install.none') }}</li>

        <li><strong>{{ __('install.enable_blocked_admin_ips') }}:</strong> {{ isset($data['enable_blocked_admin_ips']) && $data['enable_blocked_admin_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
        <li><strong>{{ __('install.blocked_admin_ips') }}:</strong>
            {{ isset($data['blocked_admin_ips']) && $data['blocked_admin_ips'] ? nl2br(e($data['blocked_admin_ips'])) : __('install.none') }}</li>

        <li><strong>{{ __('install.enable_allowed_front_ips') }}:</strong> {{ isset($data['enable_allowed_front_ips']) && $data['enable_allowed_front_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
        <li><strong>{{ __('install.allowed_front_ips') }}:</strong>
            {{ isset($data['allowed_front_ips']) && $data['allowed_front_ips'] ? nl2br(e($data['allowed_front_ips'])) : __('install.none') }}</li>

        <li><strong>{{ __('install.enable_blocked_front_ips') }}:</strong> {{ isset($data['enable_blocked_front_ips']) && $data['enable_blocked_front_ips'] ? __('install.enabled') : __('install.disabled') }}</li>
        <li><strong>{{ __('install.blocked_front_ips') }}:</strong>
            {{ isset($data['blocked_front_ips']) && $data['blocked_front_ips'] ? nl2br(e($data['blocked_front_ips'])) : __('install.none') }}</li>

        <!-- ✅ DB情報 -->
        <li><strong>{{ __('install.db_connection') }}:</strong> {{ ucfirst($data['db_connection']) }}</li>
        <li><strong>{{ __('install.db_host') }}:</strong> {{ $data['db_host'] }}</li>
        <li><strong>{{ __('install.db_port') }}:</strong> {{ $data['db_port'] }}</li>
        <li><strong>{{ __('install.db_name') }}:</strong> {{ $data['db_database'] }}</li>
        <li><strong>{{ __('install.db_user') }}:</strong> {{ $data['db_username'] }}</li>
        <li><strong>{{ __('install.db_password') }}:</strong> <span class="text-gray-500">●●●●●</span></li>
    </ul>

    <p class="text-center">{{ __('install.confirm_description') }}</p>
    <form action="{{ route('install.confirm.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="flex justify-between">
            <a href="{{ route('install.database') }}"
               class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition">
                {{ __('install.back_button') }}
            </a>
            <button type="submit"
                    class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                {{ __('install.confirm_button') }}
            </button>
        </div>
    </form>
@endsection