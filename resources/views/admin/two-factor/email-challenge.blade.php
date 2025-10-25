@extends('layouts.auth')

@section('title', __('auth.two_factor.title'))
@section('icon', 'fas fa-envelope')
@section('header', __('auth.two_factor.title'))
@section('description', __('auth.two_factor.prompt'))

@section('content')
    <x-two-factor.email-challenge
        :action="route('admin.two-factor.confirm')"
        :resend-action="route('admin.two-factor.resend')"
        :title="__('auth.two_factor.code_title')"
        :prompt="__('auth.two_factor.code_prompt')"
        :submit-text="__('auth.two_factor.verify')"
        :resend-text="__('auth.two_factor.resend')"
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
