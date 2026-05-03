{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
    'captchaEnabled' => false,
    'captchaAuthenticationResult' => false,
    'captchaAvailable' => false,
    'settingsUrl' => '',
    'screens' => [],
])

@php
    $captchaAvailable = $captchaEnabled && $captchaAuthenticationResult;
@endphp

<div>
    @if(!$captchaEnabled)
        <x-ui-message
            type="warning"
            :message="__('components/security/captcha-settings.not_enabled', ['url' => $settingsUrl])"
        />
    @elseif(!$captchaAuthenticationResult)
        <x-ui-message
            type="warning"
            :message="__('components/security/captcha-settings.not_authenticated', ['url' => $settingsUrl])"
        />
    @endif

    <fieldset>
        <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            {{ __('components/security/captcha-settings.screens') }}
        </legend>
        <div class="space-y-3">
            @if(!$captchaAvailable)
                @foreach($screens as $screen)
                    <x-form-hidden
                        :name="$screen['name']"
                        :value="$screen['value'] ? '1' : '0'"
                    />
                @endforeach
            @endif
            @foreach($screens as $screen)
                <x-form-toggle
                    :name="$screen['name']"
                    :label="$screen['label']"
                    :checked="old($screen['name'], $screen['value'])"
                    :disabled="!$captchaAvailable"
                />
            @endforeach
        </div>
    </fieldset>
</div>
