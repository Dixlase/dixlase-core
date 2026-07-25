{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-auth.forgot-password />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
    'route',
    'loginRoute' => null,
    'captchaEnabled' => false,
    'captchaWidget' => null,
])

<form method="POST" action="{{ $route }}">
    @csrf

    <x-captcha
        :enabled="$captchaEnabled"
        :widget="$captchaWidget"
    />
    
    <section>
        <fieldset>
            <legend class="sr-only">{{ __('admin/auth.forgot_password.email_label') }}</legend>
            
            <x-form-text
                type="email"
                id="email"
                name="email"
                :value="old('email')"
                :required="true"
                :autofocus="true"
                autocomplete="username"
            />
            
            <x-form-error
                :messages="$errors->get('email')"
            />
        </fieldset>
    </section>

    <section class="flex items-center flex-col justify-between mt-6">
        <x-form-button
            type="submit"
            variant="primary"
            size="md"
            :label="__('common.send_password_reset_link')"
            class="w-full"
        />
        
    </section>

    @section('back_link')
        <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline mt-4" href="{{ $loginRoute }}">
            {{ __('admin/auth.forgot_password.back_to_login') }}
        </a>
    @endsection

</form>
