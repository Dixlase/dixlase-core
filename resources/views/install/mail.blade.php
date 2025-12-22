@extends('layouts.install')

@section('title', __('install.mail_title'))
@section('header', __('install.mail_header'))
@section('description')
    {!! __('install.mail_description') !!}
@endsection

@section('content')
<form action="{{ route('install.mail.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- メールサーバー設定セクション -->
    <section aria-labelledby="mail-server-heading">
        <h2 id="mail-server-heading" class="sr-only">{{ __('install.mail_server_settings') }}</h2>
        
        <x-mail-server-form
            :settings="[]"
            context="install"
            :admin_email="$admin_email"
        />
    </section>

    <!-- メール接続テストセクション -->
    <section aria-labelledby="mail-test-heading">
        <h2 id="mail-test-heading" class="sr-only">{{ __('install.mail_connection_test') }}</h2>
        
        <x-mail-test
            context="install"
            :connectionTestRoute="route('install.mail.test-connection')"
            :mailTestRoute="route('install.mail.test-send')"
            :showStatus="false"
            :testStatus="$testStatus"
        />
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between mt-6">
        <a href="{{ route('install.database') }}" 
            class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install.back') }}
        </a>
        <x-form.button
            type="submit"
            variant="primary"
            :label="__('install.next')"
        />
    </nav>
</form>

@endsection
