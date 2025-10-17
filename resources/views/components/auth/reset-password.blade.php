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

@props([
    'action',
    'token',
    'email' => '',
    'emailLabel',
    'passwordLabel',
    'submitText',
    'passwordMinLength' => 8,
    'passwordRequireUppercase' => true,
    'passwordRequireSymbol' => false
])

<form method="POST" action="{{ $action }}">
    @csrf

    <!-- Password Reset Token -->
    <input type="hidden" name="token" value="{{ $token }}">

    <!-- Email Address -->
    <div>
        <label for="email" class="block font-medium text-sm text-gray-700">{{ $emailLabel }}</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        @error('email')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div class="mt-4">
        <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-200">{{ $passwordLabel }}</label>
        <x-form.password-tools 
            name="password" 
            id="password" 
            :required="true"
            :minLength="$passwordMinLength"
            :requireUppercase="$passwordRequireUppercase"
            :requireLowercase="true"
            :requireNumber="true"
            :requireSymbol="$passwordRequireSymbol"
            :showConfirmation="true"
        />
        @error('password')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        @error('password_confirmation')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-center mt-4">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ $submitText }}
        </button>
    </div>
</form>
