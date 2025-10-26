@extends('layouts.auth')

@section('title', 'デバイス認証')
@section('icon')
["fas fa-mobile-alt", "fas fa-laptop"]
@endsection
@section('header', __('two-factor.title'))
@section('description')
    {!! __('two-factor.device.prompt') !!}
@endsection

@section('content')
    <x-two-factor.device-challenge
        :challenge-action="route('admin.device-auth.check')"
        :verify-action="route('admin.device-auth.check')"
        :resend-action="route('admin.device-auth.resend')"
        :title="__('two-factor.device.waiting_title')"
        :prompt="__('two-factor.device.waiting_message')"
        :auto-start="true"
        :poll-interval="5000"
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
