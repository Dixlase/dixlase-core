@extends('admin::partials.layout-auth')

@section('title', '二段階認証')

@section('content')
<x-two-factor.auth-layout 
    :title="__('auth.two_factor.title')"
    :subtitle="__('auth.two_factor.prompt')"
    context="admin"
>
    <x-two-factor.email-challenge
        :action="route('admin.two-factor.verify')"
        :resend-action="route('admin.two-factor.resend')"
        :title="__('auth.two_factor.code_title')"
        :prompt="__('auth.two_factor.code_prompt')"
        :submit-text="__('auth.two_factor.verify')"
        :resend-text="__('auth.two_factor.resend')"
        context="admin"
    />

    <x-slot name="alternatives">
        <x-two-factor.alternative-methods
            :methods="[
                [
                    'value' => 'device',
                    'label' => 'デバイス認証',
                    'url' => route('admin.two-factor.device.challenge')
                ],
                [
                    'value' => 'biometric',
                    'label' => '生体認証',
                    'url' => route('admin.two-factor.biometric.challenge')
                ]
            ]"
            current-method="email"
            context="admin"
        />
    </x-slot>
</x-two-factor.auth-layout>
@endsection