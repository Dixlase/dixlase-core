{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-security.session-settings />

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
    'sessionLifetime' => '120',
    'sessionEncrypt' => false,
    'disabled' => false,
    'translationPrefix' => 'admin/settings/security/session',
])

<!-- セッション管理設定 -->
<section>
    <h2>{{ __($translationPrefix . '.session_management') }}</h2>
    <p>{{ __($translationPrefix . '.session_management_description') }}</p>

    <!-- セッション暗号化 -->
    <fieldset>
        <x-form-toggle
            name="session_encrypt"
            :label="__($translationPrefix . '.session_encrypt')"
            :checked="old('session_encrypt', $sessionEncrypt)"
            :disabled="$disabled"
        />
        <p class="mt-2">{{ __($translationPrefix . '.session_encrypt_help') }}</p>
    </fieldset>

    <!-- デフォルトセッション有効時間 -->
    <fieldset>
        <legend>{{ __($translationPrefix . '.session_lifetime') }}</legend>
        
        <div class="flex items-center space-x-3 mt-2">
            <x-form-text
                id="session_lifetime"
                name="session_lifetime"
                type="number"
                :min="1"
                :max="43200"
                :value="old('session_lifetime', $sessionLifetime)"
                class="input-common input-sm"
                aria-describedby="session_lifetime_unit session_lifetime_help"
                :disabled="$disabled"
            />
            <span id="session_lifetime_unit" class="text-sm text-gray-700 dark:text-gray-300">
                {{ __('common.minutes') }}
            </span>
        </div>
        
        <p id="session_lifetime_help">{{ __($translationPrefix . '.session_lifetime_help') }}</p>
    </fieldset>
</section>
