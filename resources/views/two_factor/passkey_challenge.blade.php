@extends('layouts.auth')

@section('title', __('two-factor.passkey.title'))
@section('icon')
["fas fa-key", "fas fa-fingerprint"]
@endsection
@section('header', __('two-factor.passkey.title'))
@section('description', __('two-factor.passkey.prompt'))

@section('content')
    @php
        $passkeyChallenge = $challengeAction ?? route('admin.two-fa.passkey.challenge');
        $passkeyVerify = $verifyAction ?? route('admin.two-fa.passkey.verify');
    @endphp
    @include('two-factor.partials.passkey-challenge', [
        'challengeAction' => $passkeyChallenge,
        'verifyAction' => $passkeyVerify,
        'context' => 'admin'
    ])

    <!-- 別の認証方法へのリンク -->
    @include('two-factor.partials.alternative-methods', [
        'methods' => $availableMethods,
        'currentMethod' => $currentMethod,
        'context' => 'admin'
    ])
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-factor.back_to_login') }}
    </a>
@endsection
