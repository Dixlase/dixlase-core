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

@extends('admin::partials.layout-auth')
@section('title', __('admin.login.title'))
@section('header', __('admin.login.header'))
@section('description', __('admin.login.description'))

@section('content')
    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        <x-captcha action="admin_login" form-name="admin_login" />

        <!-- メールアドレス -->
        <div class="mt-4">
            <label for="email" class="block font-medium text-sm text-gray-700">{{ __('admin.login.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- パスワード -->
        <div class="mt-4">
            <label for="password" class="block font-medium text-sm text-gray-700">{{ __('admin.login.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>



        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ml-2 text-sm text-gray-600">{{ __('admin.login.remember_me') }}</span>
            </label>
        </div>



        <!-- ボタンとパスワードリセットリンク -->
        <div class="flex items-center justify-between mt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('admin.login.login_button') }}
            </button>


            @if (Route::has('admin.password.request') && ($passwordResetEnabled ?? true))
                <a class="text-sm text-indigo-600 hover:underline" href="{{ route('admin.password.request') }}">
                    {{ __('admin.login.forgot_password') }}
                </a>
            @endif
        </div>


    </form>
@endsection

<!-- CAPTCHA -->
@section('captcha')
    
@endsection
