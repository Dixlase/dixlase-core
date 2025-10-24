{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.auth')
@section('title', __('admin.login.title'))
@section('header', __('admin.login.header'))
@section('description', __('admin.login.description'))

@section('content')
    {{-- メール認証待ちメッセージ --}}
    @if(session('email_verification_pending') || session('info'))
        @include('components.message', [
            'type' => 'info',
            'message' => session('info') ?? __('auth.verify_email_login_required')
        ])
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        @include('components.captcha', [
            'enabled' => $captchaEnabled ?? false,
            'widget' => $captchaWidget ?? null
        ])

        <!-- メールアドレス -->
        @include('components.auth.login-field', [
            'id' => 'email',
            'type' => 'email',
            'name' => 'email',
            'label' => __('common.email'),
            'value' => old('email'),
            'required' => true,
            'autofocus' => true,
            'autocomplete' => 'username'
        ])

        <!-- パスワード -->
        @include('components.auth.login-field', [
            'id' => 'password',
            'type' => 'password',
            'name' => 'password',
            'label' => __('common.password'),
            'value' => '',
            'required' => true,
            'autofocus' => false,
            'autocomplete' => 'current-password'
        ])

        <!-- Remember Me -->
        @include('components.form.checkbox', [
            'id' => 'remember_me',
            'name' => 'remember',
            'label' => 'admin.login.remember_me',
            'class' => 'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-500 dark:bg-gray-800 dark:text-indigo-400'
        ])



        <!-- ボタンとパスワードリセットリンク -->
        <div class="flex items-center justify-between mt-4">
            @include('components.form.button', [
                'type' => 'submit',
                'variant' => 'primary',
                'label' => __('common.login'),
                'class' => 'dark:focus:ring-offset-gray-800'
            ])

            @if (Route::has('admin.password.request') && ($passwordResetEnabled ?? true))
                <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ route('admin.password.request') }}">
                    {{ __('admin.login.forgot_password') }}
                </a>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-center text-center text-sm">
            <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ route('welcome') }}">
                {{ __('admin.login.back_to_welcome') }}
            </a>
        </div>


    </form>
@endsection

<!-- CAPTCHA -->
@section('captcha')
    
@endsection
