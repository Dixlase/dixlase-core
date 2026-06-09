{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.two-fa-general-settings />

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
    'twoFaModeName' => 'two_fa_force_mode',
    'twoFaModeValue' => '3',
    'twoFaPasskeyModeName' => 'two_fa_passkey_mode',
    'twoFaPasskeyModeValue' => '2',
    'columns' => 4,
])

{{-- Two-factor authentication settings component for general settings screen --}}
<div>
    {{-- 1. Two-factor authentication mode --}}
    <x-two-fa.mode-selector
        :name="$twoFaModeName"
        :value="old($twoFaModeName, (string) $twoFaModeValue)"
        :globalSetting="null"
        :excludeUseProfileSetting="false"
        :columns="$columns"
        xModel="twoFaMode"
    />

    {{-- 2. Two-factor authentication methods (email authentication and passkey settings) --}}
    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
        {{-- Email authentication is always enabled --}}
        <fieldset>
            <legend>{{ __('components/security/two-fa-general-settings.method_label') }}</legend>

            <div class="space-y-6">
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                            <span class="text-sm font-medium">{{ __('components/security/two-fa-general-settings.email_always_enabled') }}</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('components/security/two-fa-general-settings.email_always_enabled') }}</span>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- Passkey settings (for general settings) --}}
        <fieldset>
            <legend>{{ __('common.passkey_mode.label') }}</legend>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('common.passkey_mode.help.global_toggle') }}
            </p>
            
            <x-form-toggle
                :name="$twoFaPasskeyModeName"
                :label="__('common.passkey_mode.options.enabled')"
                :checked="old($twoFaPasskeyModeName, $twoFaPasskeyModeValue) == '1'"
                xModel="passkeyMode"
            />
            
            @error($twoFaPasskeyModeName)
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>
</div>
