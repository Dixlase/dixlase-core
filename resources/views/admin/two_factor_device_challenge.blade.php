@extends('admin::partials.layout-auth')

@section('title', 'デバイス認証')

@section('content')
<x-two-factor.auth-layout 
    :title="__('auth.two_factor.device.title')"
    :subtitle="__('auth.two_factor.device.prompt')"
    context="admin"
>
    <x-two-factor.device-challenge
        :challenge-action="route('admin.two-factor.device.challenge')"
        :verify-action="route('admin.two-factor.device.verify')"
        :title="__('auth.two_factor.device.waiting_title')"
        :prompt="__('auth.two_factor.device.waiting_message')"
        context="admin"
    />

    <x-slot name="alternatives">
        <x-two-factor.alternative-methods
            :methods="[
                [
                    'value' => 'email',
                    'label' => 'メール認証',
                    'url' => route('admin.two-factor.challenge')
                ],
                [
                    'value' => 'biometric',
                    'label' => '生体認証',
                    'url' => route('admin.two-factor.biometric.challenge')
                ]
            ]"
            current-method="device"
            context="admin"
        />
    </x-slot>
</x-two-factor.auth-layout>
@endsection
