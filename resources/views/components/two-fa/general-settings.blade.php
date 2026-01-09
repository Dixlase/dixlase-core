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
    'twoFaModeName' => 'two_fa_force_mode',
    'twoFaModeValue' => '3',
    'twoFaPasskeyModeName' => 'two_fa_passkey_mode',
    'twoFaPasskeyModeValue' => '2',
    'twoFaDefaultMethodName' => 'two_fa_default_method',
    'twoFaDefaultMethodValue' => '0',
    'columns' => 4,
])

{{-- 全体設定画面用の二段階認証設定コンポーネント --}}
<div x-data="{
    twoFaMode: '{{ old($twoFaModeName, (string) $twoFaModeValue) }}',
    passkeyMode: '{{ old($twoFaPasskeyModeName, (string) $twoFaPasskeyModeValue) }}',
    defaultMethod: '{{ old($twoFaDefaultMethodName, (string) $twoFaDefaultMethodValue) }}',
    get twoFaEnabled() {
        return this.twoFaMode !== '0';
    },
    get passkeyEnabled() {
        return this.passkeyMode !== '0';
    },
    init() {
        // パスキーモードの変更を監視
        this.$watch('passkeyMode', value => {
            // パスキーが無効になった場合、デフォルト認証方法を強制的にメール（0）に変更
            if (value === '0') {
                this.defaultMethod = '0';
            }
        });
    }
}">
    {{-- 1. 二段階認証モード --}}
    <x-two-fa.mode-selector
        :name="$twoFaModeName"
        :value="old($twoFaModeName, (string) $twoFaModeValue)"
        :globalSetting="null"
        :excludeUseProfileSetting="false"
        :columns="$columns"
        xModel="twoFaMode"
    />

    {{-- 2. 二段階認証方法（メール認証・パスキー設定） --}}
    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
        <x-two-fa.method-selector
            :name="$twoFaPasskeyModeName"
            :value="old($twoFaPasskeyModeName, (string) $twoFaPasskeyModeValue)"
            :columns="3"
            xModel="passkeyMode"
        />
    </div>

    {{-- 3. デフォルトの認証方法 --}}
    <fieldset>
        <legend>{{ __('components.two_fa.default_method') }}</legend>
        
        {{-- パスキーが無効の場合の情報メッセージ --}}
        <div x-show="!passkeyEnabled" x-transition class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('components.two_fa.passkey_disabled_default_email_only') }}
            </p>
        </div>
        
        {{-- ラジオカードグループ（無効化条件を適用） --}}
        <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled || !passkeyEnabled }">
            <x-two-fa.default-method
                :twoFaPasskeyEnabled="old($twoFaPasskeyModeName, $twoFaPasskeyModeValue) != '0'"
                :twoFaDefaultMethod="old($twoFaDefaultMethodName, (string) $twoFaDefaultMethodValue)"
                :columns="2"
                xModel="defaultMethod"
            />
        </div>
    </fieldset>
</div>
