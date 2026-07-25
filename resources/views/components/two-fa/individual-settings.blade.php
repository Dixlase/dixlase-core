{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-two-fa.individual-settings />

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
    // 新しいパラメータ名（individual-auth-selectorとの互換性）
    'name' => null,
    'value' => null,
    'globalSetting' => null,
    'excludeUseProfileSetting' => false,
    'twoFaPasskeyGloballyEnabled' => false,
    'twoFaDefaultMethod' => '0',
    
    // 既存のパラメータ名（後方互換性のため保持）
    'twoFaModeName' => null,
    'twoFaModeValue' => null,
    'twoFaGlobalSetting' => null,
    'twoFaPasskeyEnabled' => true,
    'twoFaPasskeyMode' => '2',
    'columns' => 3,
    'globalSettingsUrl' => null,
    'isTwoFaEditable' => null,
    'isPasskeyEditable' => null,
    'forcedPasskeyValue' => null,
    'twoFaModeOptionsWithIcons' => null,
    'twoFaGlobalModeName' => null,
])

@php
    // パラメータの統合（新しい名前を優先、なければ既存の名前を使用）
    $twoFaModeName = $name ?? $twoFaModeName ?? 'two_fa_mode';
    $twoFaModeValue = $value ?? $twoFaModeValue ?? '0';
    $twoFaGlobalSetting = $globalSetting ?? $twoFaGlobalSetting;
@endphp

@php
    // 事前計算されたpropsがない場合はフォールバック（プラグインとの後方互換性）
    if ($isTwoFaEditable === null || $isPasskeyEditable === null || $forcedPasskeyValue === null) {
        $twoFaForceMode = (int) $twoFaGlobalSetting;
        $isTwoFaEditable = $isTwoFaEditable ?? ($twoFaForceMode === \App\Enums\AuthenticationMode::UseProfileSetting->value);
        $passkeyModeVal = (int) $twoFaPasskeyMode;
        $isPasskeyEditable = $isPasskeyEditable ?? \App\Enums\PasskeyMode::isProfileEditable($passkeyModeVal);
        $forcedPasskeyValue = $forcedPasskeyValue ?? \App\Enums\PasskeyMode::getForcedProfileValue($passkeyModeVal);
    }
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
    {{-- 1. Two-factor authentication mode --}}
    @if($isTwoFaEditable)
        {{-- When following profile settings: editable --}}
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
        {{-- When enforced by global settings: displayed as selected and disabled --}}
        <fieldset>
            <legend>{{ __('components/security/two-fa-general-settings.mode_label') }}</legend>
            
            {{-- Explanation that it is fixed by global settings --}}
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('components/security/two-fa-general-settings.global_setting_fixed') }}
                </p>
            </div>
            
            {{-- Selected and disabled radio card --}}
            <div class="opacity-50 pointer-events-none">
                @php
                    // 事前計算されたpropsがない場合はフォールバック（プラグインとの後方互換性）
                    if ($twoFaModeOptionsWithIcons === null) {
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
                    } else {
                        $modeOptions = $twoFaModeOptionsWithIcons;
                    }
                @endphp
                <x-form-radio-card-group
                    :name="$twoFaModeName"
                    :options="$modeOptions"
                    :value="(string) $twoFaGlobalSetting"
                    :columns="$columns"
                />
            </div>
            
            {{-- Explanation of current settings value --}}
            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <strong>{{ __('components/security/two-fa-general-settings.authentication_mode.' . ($twoFaGlobalModeName ?? strtolower(\App\Enums\AuthenticationMode::tryFrom((int) $twoFaGlobalSetting)?->name ?? 'disabled'))) }}</strong>
                </p>
            </div>
        </fieldset>
    @endif

</div>
