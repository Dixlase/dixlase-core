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

    @include('components::form.hidden', [
        'name' => 'token',
        'value' => $token
    ])

    <section>
        <fieldset>
            <legend class="sr-only">{{ $emailLabel }}</legend>
            
            @include('components::form.text', [
                'type' => 'email',
                'id' => 'email',
                'name' => 'email',
                'value' => old('email', $email),
                'required' => true,
                'autocomplete' => 'username',
                'ariaLabel' => $emailLabel,
                'placeholder' => $emailLabel
            ])
            
            @include('components::form.error', [
                'messages' => $errors->get('email')
            ])
        </fieldset>
    </section>

    <section class="mt-6">
        <fieldset>
            <legend class="block font-medium text-sm text-gray-700 dark:text-gray-200 mb-2">{{ $passwordLabel }}</legend>
            
            <x-password-tools 
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
            
            @include('components::form.error', [
                'messages' => $errors->get('password')
            ])
            
            @include('components::form.error', [
                'messages' => $errors->get('password_confirmation')
            ])
        </fieldset>
    </section>

    <section class="flex items-center justify-center mt-6">
        @include('components::form.button', [
            'type' => 'submit',
            'variant' => 'primary',
            'size' => 'md',
            'label' => $submitText,
            'class' => 'w-full'
        ])
    </section>
</form>
