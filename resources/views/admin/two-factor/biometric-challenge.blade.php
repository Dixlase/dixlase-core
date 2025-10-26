@extends('layouts.auth')

@section('title', '生体認証')
@section('icon', 'fas fa-fingerprint')
@section('header', '生体認証')
@section('description', '生体認証を使用してログインしてください')

@section('content')
    <x-two-factor.biometric-challenge
        :challenge-action="route('admin.two-factor.biometric.challenge')"
        :verify-action="route('admin.two-factor.biometric.verify')"
        :title="'生体認証待機中'"
        :prompt="'Touch ID、Face ID、または指紋認証を使用してください。'"
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
        ← ログイン画面に戻る
    </a>
@endsection
