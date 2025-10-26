@extends('layouts.auth')

@section('title', '生体認証')
@section('icon')
["fas fa-fingerprint", "fas fa-face-smile"]
@endsection
@section('header', '生体認証')
@section('description', '生体認証(Touch ID、Face ID、Windows Hello、または指紋認証など)を使用してログインしてください')

@section('content')
    <x-two-factor.biometric-challenge
        :challenge-action="route('admin.two-factor.biometric.challenge')"
        :verify-action="route('admin.two-factor.biometric.verify')"
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
