{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-security.password-settings />

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
    'minLength' => '8',
    'requireUppercase' => false,
    'requireNumber' => false,
    'requireSymbol' => false,
    'resetEnabled' => true,
    'pwnedCheckEnabled' => false,
    'showPwnedCheck' => true,
    'minLengthOptions' => null,
    'isMailServerTested' => true,
    'disabled' => false,
    'useAlpineDisabled' => false,
    'translationPrefix' => 'admin/settings/security/password',
])

@php
    $defaultMinLengthOptions = [
        ['value' => '8', 'label' => __('common.characters', ['count' => 8])],
        ['value' => '12', 'label' => __('common.characters', ['count' => 12])],
        ['value' => '16', 'label' => __('common.characters', ['count' => 16])],
    ];
    $minLengthOptions = $minLengthOptions ?? $defaultMinLengthOptions;
@endphp

<!-- パスワード条件設定 -->
<section>
    <h2>{{ __($translationPrefix . '.conditions') }}</h2>
    
    <fieldset>
        <legend>{{ __($translationPrefix . '.min_length') }}</legend>
        <x-form-radio-card-group
            name="password_min_length"
            :options="$minLengthOptions"
            :value="old('password_min_length', (string) $minLength)"
            :columns="3"
            class="mb-4"
            :disabled="$disabled"
        />
    </fieldset>

    <!-- 大文字 -->
    <fieldset>
        <x-form-toggle
            name="password_require_uppercase"
            :label="__($translationPrefix . '.require_uppercase')"
            :checked="old('password_require_uppercase', $requireUppercase)"
            :disabled="$disabled"
        />
    </fieldset>

    <!-- 数字 -->
    <fieldset>
        <x-form-toggle
            name="password_require_number"
            :label="__($translationPrefix . '.require_number')"
            :checked="old('password_require_number', $requireNumber)"
            :disabled="$disabled"
        />
    </fieldset>

    <!-- 記号 -->
    <fieldset>
        <x-form-toggle
            name="password_require_symbol"
            :label="__($translationPrefix . '.require_symbol')"
            :checked="old('password_require_symbol', $requireSymbol)"
            :disabled="$disabled"
        />
    </fieldset>
    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
        {{ __($translationPrefix . '.security_warning') }}
    </p>
</section>

<!-- パスワードリセット機能設定 -->
<section>
    <h2>{{ __($translationPrefix . '.reset_settings') }}</h2>
    @if(!$isMailServerTested)
        <x-ui-message
            type="warning"
            :message="__($translationPrefix . '.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
        />
    @endif
    <fieldset>
        <x-form-toggle
            name="password_reset_enabled"
            :label="__($translationPrefix . '.reset_enabled')"
            :checked="old('password_reset_enabled', $resetEnabled)"
            :disabled="$disabled"
        />
        <p class="mt-2">
            {!! __($translationPrefix . '.reset_help') !!}
        </p>
    </fieldset>
</section>

@if($showPwnedCheck)
    <!-- パスワード漏洩チェック -->
    <section>
        <h2>{{ __($translationPrefix . '.pwned_settings') }}</h2>
        <p>{{ __($translationPrefix . '.pwned_password_check_description') }}</p>

        <fieldset>
            <x-form-toggle
                :label="__('common.enabled')"
                id="pwned_password_check_enabled"
                name="pwned_password_check_enabled"
                :checked="old('pwned_password_check_enabled', $pwnedCheckEnabled)"
                :disabled="$disabled"
            />
            
            <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p>{{ __($translationPrefix . '.pwned_password_api_info') }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </section>
@endif
