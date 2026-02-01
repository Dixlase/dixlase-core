@extends('layouts.auth')

@section('title', __('two_fa.title'))
@section('icon')
["fas fa-envelope", "fas fa-key"]
@endsection
@section('header', __('two_fa.title'))
@section('description', __('two_fa.email.prompt'))

@section('content')
    @php
        $verifyAction = $action ?? route('admin.two-fa.email.verify');
        $resendRoute = $resendAction ?? route('admin.two-fa.email.resend');
        $contextValue = $context ?? 'admin';
    @endphp
    @include('two-fa.partials.email-challenge', [
        'action' => $verifyAction,
        'resendAction' => $resendRoute,
        'title' => __('two_fa.email.code_title'),
        'prompt' => __('two_fa.email.code_prompt'),
        'submitText' => __('two_fa.email.verify'),
        'resendText' => __('two_fa.email.resend'),
        'expireMinutes' => $expireMinutes,
        'resendIntervalSeconds' => $resendIntervalSeconds,
        'context' => $contextValue
    ])

    <!-- 回復コードへのリンク -->
    @if($recoveryCodeRoute ?? null)
        <div class="mt-4 text-center">
            <a href="{{ route($recoveryCodeRoute) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <i class="fas fa-life-ring mr-1"></i>{{ __('two_fa.recovery_code.use_recovery_code') }}
            </a>
        </div>
    @endif
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two_fa.back_to_login') }}
    </a>
@endsection
