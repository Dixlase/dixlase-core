{{--
This file is part of MySoftware.

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
            'label' => __('admin.login.email'),
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
                'label' => __('common.login_button'),
                'class' => 'dark:focus:ring-offset-gray-800'
            ])

            @if (Route::has('admin.password.request') && ($passwordResetEnabled ?? true))
                <a class="text-sm" href="{{ route('admin.password.request') }}">
                    {{ __('admin.login.forgot_password') }}
                </a>
            @endif
        </div>


    </form>
@endsection

<!-- CAPTCHA -->
@section('captcha')
    
@endsection
