{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.passkey-device-settings />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

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
    'maxDevices' => 3,
    'disabled' => false,
    'twoFaEnabled' => true,
    'translationPrefix' => 'admin/settings/security/two-fa',
])

<!-- パスキーデバイス管理設定 -->
<section>
    <h2>{{ __($translationPrefix . '.passkey_device_management') }}</h2>
    <p>{{ __($translationPrefix . '.passkey_device_management_description') }}</p>

    <div :class="{ 'opacity-50 pointer-events-none': {{ $disabled ? 'true' : '!twoFaEnabled' }} }">
        @if(!$disabled)
            <input type="hidden" name="two_fa_passkey_max_devices" :value="twoFaEnabled ? null : '{{ $maxDevices }}'" x-show="!twoFaEnabled">
        @endif

        <div class="space-y-4">
            <div>
                <label for="two_fa_passkey_max_devices" class="block text-sm font-medium">
                    {{ __($translationPrefix . '.passkey_max_devices') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_passkey_max_devices"
                        name="two_fa_passkey_max_devices"
                        :value="old('two_fa_passkey_max_devices', $maxDevices)"
                        :min="1"
                        :max="10"
                        :step="1"
                        class="input-common input-sm"
                        :disabled="$disabled"
                        ::disabled="$disabled ? null : '!twoFaEnabled'"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __($translationPrefix . '.devices_unit') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __($translationPrefix . '.passkey_max_devices_help') }}
                </p>
            </div>
        </div>
    </div>
</section>
