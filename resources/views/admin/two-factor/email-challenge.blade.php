@extends('layouts.auth')

@section('title', __('two-factor.email.title'))
@section('icon')
["fas fa-envelope", "fas fa-key"]
@endsection
@section('header', __('two-factor.email.title'))
@section('description', __('two-factor.email.prompt'))

@section('content')
    <x-two-factor.email-challenge
        :action="route('admin.two-factor.confirm')"
        :resend-action="route('admin.two-factor.resend')"
        :title="__('two-factor.email.code_title')"
        :prompt="__('two-factor.email.code_prompt')"
        :submit-text="__('two-factor.email.verify')"
        :resend-text="__('two-factor.email.resend')"
        :expire-minutes="$expireMinutes"
        :resend-interval-seconds="$resendIntervalSeconds"
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
@endsection

@section('back_link')
    <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-factor.back_to_login') }}
    </a>
@endsection
