@extends('admin::partials.layout-auth')

@section('title', '生体認証')

@section('content')
<x-two-factor.auth-layout 
    :title="__('auth.two_factor.biometric.title')"
    :subtitle="__('auth.two_factor.biometric.prompt')"
    context="admin"
>
    <x-two-factor.biometric-challenge
        :challenge-action="route('admin.two-factor.biometric.challenge')"
        :verify-action="route('admin.two-factor.biometric.verify')"
        :title="__('auth.two_factor.biometric.waiting_title')"
        :prompt="__('auth.two_factor.biometric.waiting_message')"
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
                    'value' => 'device',
                    'label' => 'デバイス認証',
                    'url' => route('admin.two-factor.device.challenge')
                ]
            ]"
            current-method="biometric"
            context="admin"
        />
    </x-slot>
</x-two-factor.auth-layout>
@endsection
