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
    'twoFaModeName' => 'two_fa_mode',
    'twoFaModeValue' => '0',
    'twoFaGlobalSetting' => null,
    'twoFaPasskeyEnabled' => true,
    'twoFaPasskeyMode' => '2',
    'columns' => 3,
    'globalSettingsUrl' => null,
])

@php
    use App\Enums\AuthenticationMode;
    use App\Enums\PasskeyMode;
    
    // 全体設定の二段階認証モード
    $twoFaForceMode = (int) $twoFaGlobalSetting;
    $isTwoFaEditable = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value;
    
    // 全体設定のパスキーモード
    $passkeyMode = (int) $twoFaPasskeyMode;
    $isPasskeyEditable = PasskeyMode::isProfileEditable($passkeyMode);
    $forcedPasskeyValue = PasskeyMode::getForcedProfileValue($passkeyMode);
    $currentPasskeyEnabled = $forcedPasskeyValue ?? $twoFaPasskeyEnabled;
@endphp

<div x-data="{
    twoFaMode: '{{ old($twoFaModeName, (string) $twoFaModeValue) }}',
    passkeyEnabled: {{ $currentPasskeyEnabled ? 'true' : 'false' }},
    isPasskeyEditable: {{ $isPasskeyEditable ? 'true' : 'false' }},
    get twoFaEnabled() {
        return this.twoFaMode !== '0';
    },
    get isPasskeyActuallyEnabled() {
        // 編集可能な場合はpasskeyEnabledの値を使用、編集不可の場合は強制値を使用
        return this.isPasskeyEditable ? this.passkeyEnabled : {{ $currentPasskeyEnabled ? 'true' : 'false' }};
    }
}">
    {{-- 1. 二段階認証モード --}}
    @if($isTwoFaEditable)
        {{-- プロフィール設定に従う場合：編集可能 --}}
        <x-two-fa.mode-selector
            :name="$twoFaModeName"
            :value="old($twoFaModeName, (string) $twoFaModeValue)"
            :globalSetting="$twoFaGlobalSetting"
            :excludeUseProfileSetting="true"
            :columns="$columns"
            :isProfile="true"
            :isTwoFaEditable="$isTwoFaEditable"
            xModel="twoFaMode"
        />
    @else
        {{-- 全体設定で強制されている場合：選択済み・操作不能で表示 --}}
        <fieldset>
            <legend>{{ __('components.two_fa.mode_label') }}</legend>
            
            {{-- 全体設定により固定されている旨の説明 --}}
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('components.two_fa.global_setting_fixed') }}
                </p>
            </div>
            
            {{-- 選択済み・操作不能のラジオカード --}}
            <div class="opacity-50 pointer-events-none">
                @php
                    $forcedMode = \App\Enums\AuthenticationMode::tryFrom((int) $twoFaGlobalSetting);
                    $modeOptions = [];
                    foreach (\App\Enums\AuthenticationMode::forProfile() as $case) {
                        $modeOptions[] = [
                            'value' => (string) $case->value,
                            'label' => $case->twoFactorLabel(),
                            'icon' => match($case) {
                                \App\Enums\AuthenticationMode::Disabled => 'fas fa-ban',
                                \App\Enums\AuthenticationMode::DifferentDevice => 'fas fa-shield-alt',
                                \App\Enums\AuthenticationMode::Always => 'fas fa-lock',
                                default => 'fas fa-cog',
                            },
                        ];
                    }
                @endphp
                <x-form.radio-card-group
                    :name="$twoFaModeName"
                    :options="$modeOptions"
                    :value="(string) $twoFaGlobalSetting"
                    :columns="$columns"
                />
            </div>
            
            {{-- 現在の設定値の説明 --}}
            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <strong>{{ __('components.two_fa.authentication_mode.' . strtolower(\App\Enums\AuthenticationMode::tryFrom((int) $twoFaGlobalSetting)?->name ?? 'disabled')) }}</strong>
                </p>
            </div>
        </fieldset>
    @endif

    {{-- 2. 二段階認証方法（メール認証・パスキー設定） --}}
    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
        {{-- メール認証は常に有効 --}}
        <fieldset>
            <legend>{{ __('components.two_fa.method_label') }}</legend>
            <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-md mb-4">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                <span class="text-sm font-medium text-green-800 dark:text-green-200">
                    {{ __('components.two_fa.email_always_enabled') }}
                </span>
            </div>
        </fieldset>

        {{-- パスキー設定 --}}
        <fieldset>
            <legend>{{ __('components.two_fa.passkey_mode.label') }}</legend>
            
            @if($isPasskeyEditable)
                {{-- プロフィール設定に従う場合：トグルで編集可能 --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('components.two_fa.passkey_mode.help.profile_editable') }}
                </p>
                <x-form.toggle
                    name="two_fa_passkey_enabled"
                    :label="__('components.two_fa.passkey_mode.options.enabled')"
                    :checked="old('two_fa_passkey_enabled', $currentPasskeyEnabled)"
                    xModel="passkeyEnabled"
                />
            @else
                {{-- 全体設定で強制されている場合：トグルを表示したまま操作不可 --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    @if($forcedPasskeyValue === false)
                        {{ __('components.two_fa.passkey_mode.help.profile_forced_disabled') }}
                    @else
                        {{ __('components.two_fa.passkey_mode.help.profile_forced_enabled') }}
                    @endif
                </p>
                <div class="opacity-50 pointer-events-none">
                    <x-form.toggle
                        name="two_fa_passkey_enabled"
                        :label="__('components.two_fa.passkey_mode.options.enabled')"
                        :checked="$forcedPasskeyValue"
                        :disabled="true"
                    />
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('components.two_fa.global_setting_fixed') }}
                </p>
            @endif
        </fieldset>
    </div>
</div>
