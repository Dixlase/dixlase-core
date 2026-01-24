@extends('layouts.auth')

@section('title', __('two_fa.passkey.title'))
@section('icon')
["fas fa-key", "fas fa-fingerprint"]
@endsection
@section('header', __('two_fa.passkey.title'))
@section('description', __('two_fa.passkey.prompt'))

@section('content')
    @php
        $passkeyChallenge = $challengeAction ?? route('admin.two-fa.passkey.challenge');
        $passkeyVerify = $verifyAction ?? route('admin.two-fa.passkey.verify');
    @endphp
    
    <!-- Passkeyデバイス未登録警告 -->
    @if(!($hasPasskeyDevices ?? true))
        <x-message 
            type="warning" 
            :message="'<strong>' . __('two_fa.passkey_device_not_registered_title') . '</strong><br>' . __('two_fa.passkey_device_not_registered_message')" 
        />
    @endif
    
    @include('two-fa.partials.passkey-challenge', [
        'challengeAction' => $passkeyChallenge,
        'verifyAction' => $passkeyVerify,
        'context' => $context ?? 'admin',
        'hasPasskeyDevices' => $hasPasskeyDevices ?? true
    ])

    <!-- 別の認証方法へのリンク -->
    @include('two-fa.partials.alternative-methods', [
        'methods' => $availableMethods,
        'currentMethod' => $currentMethod,
        'context' => $context ?? 'admin',
        'recoveryCodeRoute' => $recoveryCodeRoute ?? null
    ])
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two_fa.back_to_login') }}
    </a>
@endsection
