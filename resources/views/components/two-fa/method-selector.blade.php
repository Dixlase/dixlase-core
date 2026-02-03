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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'name' => 'two_fa_passkey_mode',
    'value' => '2',
    'columns' => 3,
    'globalSettingsUrl' => null,
    'xModel' => null,
    'isProfile' => false,
    'isPasskeyEditable' => false,
    'forcedPasskeyValue' => null,
    'currentPasskeyEnabled' => false,
])

@php
    use App\Enums\PasskeyMode;
    $passkeyModeOptions = PasskeyMode::getGlobalOptions();
    $currentPasskeyMode = old($name, $value);
@endphp

{{-- メール認証は常に有効 --}}
<fieldset>
    <legend>{{ __('components.two_fa.method_label') }}</legend>

    <div class="space-y-6">
        <div class="space-y-3">
            <div class="flex items-center space-x-3">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                    <span class="text-sm font-medium">{{ __('components.two_fa.email_always_enabled') }}</span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('components.two_fa.email_always_enabled') }}</span>
            </div>
        </div>

        @if($globalSettingsUrl)
            <div class="space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('components.two_fa.method_note') }}
                    <a href="{{ $globalSettingsUrl }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                        {{ __('components.two_fa.change_in_global_settings') }}
                    </a>
                </p>
            </div>
        @endif
    </div>
</fieldset>

{{-- パスキー設定 --}}
@if($isProfile)
    {{-- プロフィール設定の場合 --}}
    <fieldset>
        <legend>{{ __('common.passkey_mode.label') }}</legend>
        
        @if($isPasskeyEditable)
            {{-- プロフィール設定に従う場合：トグルで編集可能 --}}
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('common.passkey_mode.help.profile_editable') }}
            </p>
            <x-form-toggle
                name="two_fa_passkey_enabled"
                :label="__('common.passkey_mode.options.enabled')"
                :checked="old('two_fa_passkey_enabled', $currentPasskeyEnabled ? '1' : '0') == '1'"
                :xModel="$xModel"
            />
        @elseif($forcedPasskeyValue !== null)
            {{-- 全体設定で強制されている場合：トグルを表示したまま操作不可 --}}
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                @if($forcedPasskeyValue === false)
                    {{ __('common.passkey_mode.help.profile_forced_disabled') }}
                @else
                    {{ __('common.passkey_mode.help.profile_forced_enabled') }}
                @endif
            </p>
            <div class="opacity-50 pointer-events-none">
                <x-form-toggle
                    name="two_fa_passkey_enabled"
                    :label="__('common.passkey_mode.options.enabled')"
                    :checked="$forcedPasskeyValue"
                    :disabled="true"
                />
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('common.global_setting_fixed.two_fa', ['account_type' => __('common.account_types.member')]) }}
            </p>
        @endif
    </fieldset>
@else
    {{-- 全体設定の場合 --}}
    <fieldset>
        <legend>{{ __('common.passkey_mode.label') }}</legend>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('common.passkey_mode.help.global_toggle') }}
        </p>
        
        <x-form-toggle
            :name="$name"
            :label="__('common.passkey_mode.options.enabled')"
            :checked="old($name, $value) == '1'"
            :xModel="$xModel"
        />
        
        @error($name)
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </fieldset>
@endif
