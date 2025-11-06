@extends('layouts.auth')

@section('title', __('two-factor.passkey.title'))
@section('icon')
["fas fa-key", "fas fa-fingerprint"]
@endsection
@section('header', __('two-factor.passkey.title'))
@section('description', __('two-factor.passkey.prompt'))

@section('content')
    <x-two-factor.passkey-challenge
        :challenge-action="route('admin.two-factor.passkey.challenge')"
        :verify-action="route('admin.two-factor.passkey.verify')"
        context="admin"
    />

    @if(!empty($availableMethods))
        <div class="mt-6 text-center">
            <x-two-factor.alternative-methods
                :methods="$availableMethods"
                :current-method="$currentMethod"
                context="admin"
            />
        </div>
    @endif

    <!-- 別の認証方法へのリンク -->
    <div class="mt-4 text-center space-y-2">
        @php
            $emailMethod = collect($availableMethods)->firstWhere('value', 0);
        @endphp
        
        @if($emailMethod)
            <div>
                <a href="{{ $emailMethod['url'] }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    <i class="fas fa-envelope mr-1"></i>{{ __('two-factor.switch_to_email') }}
                </a>
            </div>
        @endif
        
        <div>
            <a href="{{ route('admin.two-factor.recovery-code.show') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                {{ __('two-factor.recovery_code.use_recovery_code') }}
            </a>
        </div>
    </div>
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-factor.back_to_login') }}
    </a>
@endsection
