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
@section('title', __('admin.auth.forgot_password.title'))
@section('header', __('admin.auth.forgot_password.header'))
@section('description')
    {!! __('admin.auth.forgot_password.description') !!}
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.password.email') }}">
        @csrf
        
        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-700">{{ __('admin.auth.forgot_password.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button and Back Link -->
        <div class="flex items-center flex-col justify-between mt-4">
            <button type="submit" class="my-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('admin.auth.forgot_password.send_reset_link') }}
            </button>

            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('admin.login') }}">
                {{ __('admin.auth.forgot_password.back_to_login') }}
            </a>
        </div>
    </form>
@endsection
