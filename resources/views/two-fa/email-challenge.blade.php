@extends('layouts.auth')

@section('title', __('two-fa/common.title'))
@section('icon')
["fas fa-envelope", "fas fa-key"]
@endsection
@section('header', __('two-fa/common.title'))
@section('description', __('two-fa/email.prompt'))

@section('content')
    @php
        $verifyAction = $action ?? route('admin.two-fa.email.verify');
        $resendRoute = $resendAction ?? route('admin.two-fa.email.resend');
        $expireMinutes = $expireMinutes ?? 10;
        $resendIntervalSeconds = $resendIntervalSeconds ?? 60;
        $codeLength = 6;
    @endphp

    <div class="text-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two-fa/email.code_title') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            {{ __('two-fa/email.code_prompt') }}
        </p>

        <form action="{{ $verifyAction }}" method="POST" id="two-factor-form">
            @csrf
            
            <!-- 認証コード入力 -->
            <div class="mb-4">
                <div class="flex justify-center space-x-2" id="code-inputs">
                    @for($i = 0; $i < $codeLength; $i++)
                        <input 
                            type="text" 
                            maxlength="1" 
                            class="w-12 h-12 text-center text-lg font-bold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            data-index="{{ $i }}"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]*"
                        >
                    @endfor
                </div>
                <input type="hidden" name="code" id="hidden-code">
                @error('code')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- 有効期限表示 -->
            <div class="mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('two-fa/email.expire_label') }}: <span id="expire-time">{{ $expireMinutes }}{{ __('two-fa/email.minutes_suffix') }}</span>
                </p>
            </div>

            <!-- CAPTCHA -->
            <x-captcha :enabled="$captchaEnabled ?? false" :widget="$captchaWidget ?? null" />

            <!-- 送信ボタン -->
            <div class="mb-4">
                <button 
                    type="submit" 
                    id="submit-button"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-blue-500 dark:hover:bg-blue-600"
                    disabled
                >
                    {{ __('two-fa/email.verify') }}
                </button>
            </div>

            <!-- 再送信ボタン -->
            @if($resendRoute)
                <div class="text-center">
                    <button 
                        type="button" 
                        id="resend-button"
                        class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        onclick="resendCode()"
                    >
                        {{ __('two-fa/email.resend') }}
                    </button>
                    <span id="resend-countdown" class="text-xs text-gray-500 dark:text-gray-400 ml-2 hidden"></span>
                </div>
            @endif
        </form>
    </div>

    <!-- 回復コードへのリンク -->
    @if($recoveryCodeRoute ?? null)
        <div class="mt-4 text-center">
            <a href="{{ route($recoveryCodeRoute) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <i class="fas fa-life-ring mr-1"></i>{{ __('two-fa/recovery-code.use_recovery_code') }}
            </a>
        </div>
    @endif

    <script type="application/json" id="email-challenge-config">
    {
        "resendAction": "{{ $resendRoute }}",
        "csrfToken": "{{ csrf_token() }}",
        "codeLength": {{ $codeLength }},
        "expireMinutes": {{ $expireMinutes }},
        "resendIntervalSeconds": {{ $resendIntervalSeconds }},
        "autoSubmit": true,
        "showExpireTime": true,
        "showResend": true,
        "translations": {
            "minutes_suffix": "{{ __('two-fa/email.minutes_suffix') }}",
            "seconds_suffix": "{{ __('two-fa/email.seconds_suffix') }}",
            "expired": "{{ __('two-fa/email.expired') }}",
            "resend_failed": "{{ __('two-fa/email.resend_failed') }}",
            "network_error": "{{ __('two-fa/email.network_error') }}"
        }
    }
    </script>
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-fa/common.back_to_login') }}
    </a>
@endsection
