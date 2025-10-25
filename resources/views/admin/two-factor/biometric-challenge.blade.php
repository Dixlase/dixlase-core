@extends('layouts.auth')

@section('title', '生体認証')

@section('content')
<x-two-factor.auth-layout 
    :title="__('two-factor.biometric.title')"
    :subtitle="__('two-factor.biometric.prompt')"
    context="admin"
>
    <x-two-factor.biometric-challenge
        :challenge-action="route('admin.two-factor.biometric.challenge')"
        :verify-action="route('admin.two-factor.biometric.verify')"
        :title="__('two-factor.biometric.waiting_title')"
        :prompt="__('two-factor.biometric.waiting_message')"
        context="admin"
    />

    @if(!empty($availableMethods))
        <x-slot name="alternatives">
            <x-two-factor.alternative-methods
                :methods="$availableMethods"
                :current-method="$currentMethod"
                context="admin"
            />
        </x-slot>
    @endif

    <x-slot name="footer">
        <div class="text-center">
            <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                ← {{ __('two-factor.back_to_login') }}
            </a>
        </div>
    </x-slot>
</x-two-factor.auth-layout>
@endsection
