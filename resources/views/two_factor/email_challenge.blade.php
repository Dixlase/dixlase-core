@extends('layouts.auth')

@section('title', __('two-factor.title'))
@section('icon')
["fas fa-envelope", "fas fa-key"]
@endsection
@section('header', __('two-factor.title'))
@section('description', __('two-factor.email.prompt'))

@section('content')
    @php
        $verifyAction = $action ?? route('admin.two-fa.email.verify');
        $resendRoute = $resendAction ?? route('admin.two-fa.email.resend');
    @endphp
    @include('two_factor.partials.email_challenge', [
        'action' => $verifyAction,
        'resendAction' => $resendRoute,
        'title' => __('two-factor.email.code_title'),
        'prompt' => __('two-factor.email.code_prompt'),
        'submitText' => __('two-factor.email.verify'),
        'resendText' => __('two-factor.email.resend'),
        'expireMinutes' => $expireMinutes,
        'resendIntervalSeconds' => $resendIntervalSeconds,
        'context' => 'admin'
    ])

    <!-- 別の認証方法へのリンク -->
    @include('two_factor.partials.alternative_methods', [
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
