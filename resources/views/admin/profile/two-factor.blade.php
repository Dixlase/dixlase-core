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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')

    @if(!$isMailServerTested)
        <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ __('admin/profile.two_factor_requires_mail_server') }}
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.profile.two-factor.update') }}" id="profile-two-factor-form">
            @csrf

            @php
                $member = Auth::guard('member')->user();
                $twoFaPasskeyGloballyEnabled = in_array(\App\Enums\TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods ?? []));
                $isTwoFaEditable = $twoFaForceMode === \App\Enums\AuthenticationMode::UseProfileSetting->value;
            @endphp
            
            @if(!$canEnableTwoFa)
                <div class="mb-6">
                    <x-message 
                        type="warning" 
                        :message="__('admin/profile/two-factor.two_fa_cannot_enable_warning')"
                    />
                </div>
            @endif
            
            <div x-data="{
                twoFaMode: '{{ old('two_fa_mode', (string) ($twoFaMode?->value ?? 0)) }}',
                passkeyEnabled: {{ $currentPasskeyEnabled ? 'true' : 'false' }},
                get twoFaEnabled() {
                    return this.twoFaMode !== '0';
                }
            }" id="two-fa-settings-wrapper">
                {{-- Passkeyが有効だがデバイスが未登録の場合の警告 --}}
                @if($currentPasskeyEnabled && $twoFaPasskeyDevices->isEmpty())
                    <div class="mb-6">
                        <x-message 
                            type="warning" 
                            :message="__('admin/profile.passkey_no_devices_notice', ['url' => route('admin.profile.two-factor-management')])"
                        />
                    </div>
                @endif

                <section class="transition-colors-unified">
                    <h2>{{ __('admin/profile/two-factor.two_fa_settings') }}</h2>

                    {{-- 1. 二段階認証モード --}}
                    <x-two-fa.mode-selector
                        name="two_fa_mode"
                        :value="old('two_fa_mode', (string) ($twoFaMode?->value ?? 0))"
                        :globalSetting="$twoFaForceMode"
                        :excludeUseProfileSetting="true"
                        :columns="3"
                        :isProfile="true"
                        :isTwoFaEditable="$isTwoFaEditable"
                        xModel="twoFaMode"
                    />

                    {{-- 2. 二段階認証方法（メール認証・パスキー設定） --}}
                    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
                        <x-two-fa.method-selector
                            name="two_fa_passkey_enabled"
                            :value="(string) ($currentPasskeyEnabled ? '1' : '0')"
                            :columns="3"
                            :isProfile="true"
                            :isPasskeyEditable="$isPasskeyEditable ?? false"
                            :forcedPasskeyValue="$forcedPasskeyValue ?? null"
                            :currentPasskeyEnabled="$currentPasskeyEnabled ?? false"
                            xModel="passkeyEnabled"
                        />
                    </div>

                    {{-- 3. デフォルトの認証方法 --}}
                    <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled || !passkeyEnabled }">
                        <x-two-fa.default-method
                            :twoFaPasskeyEnabled="$currentPasskeyEnabled"
                            :twoFaDefaultMethod="(string) (Auth::guard('member')->user()->two_fa_default_method ?? $twoFaDefaultMethod)"
                            :columns="2"
                        />
                    </div>
                </section>
            </div>

        </form>

        {{-- セッションベースのモーダル --}}
        @if(session('auto_generated_recovery_codes'))
            @include('two-fa.partials.recovery-codes-modal', [
                'modalId' => 'profileAutoGeneratedRecoveryCodesModal',
                'title' => __('two_fa.recovery_codes.auto_generated_title'),
                'codes' => session('auto_generated_recovery_codes'),
                'isAutoGenerated' => true,
                'autoOpen' => true,
                'nextModal' => session('prompt_passkey_registration') ? 'passkeyPromptModal' : null
            ])
        @endif

        @if(session('recovery_code_error'))
            @include('two-fa.partials.recovery-codes-modal', [
                'modalId' => 'recoveryCodeErrorModal',
                'error' => session('recovery_code_error'),
                'autoOpen' => true
            ])
        @endif
        
        {{-- パスキー登録促進モーダル --}}
        @if(session('prompt_passkey_registration'))
            <x-ui.modal 
                id="passkeyPromptModal"
                :title="__('admin/profile/two-factor.passkey_prompt_title')"
                icon-type="info"
                :dismissible="true"
                data-passkey-prompt="true"
                data-has-recovery-modal="{{ session('auto_generated_recovery_codes') ? 'true' : 'false' }}">
                
                <p class="mb-4">{{ __('admin/profile/two-factor.passkey_prompt_message') }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/profile/two-factor.passkey_prompt_description') }}
                </p>
                
                <x-slot name="footer">
                    <a href="{{ route('admin.profile.two-factor-management') }}" 
                       class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 px-4 py-2 text-sm">
                        <i class="fas fa-key mr-2"></i>
                        {{ __('admin/profile/two-factor.go_to_passkey_registration') }}
                    </a>
                    <button type="button" 
                            @click="close()"
                            class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 bg-gray-200 dark:bg-gray-500 text-gray-900 dark:text-white hover:bg-gray-700 focus:ring-gray-500 px-4 py-2 text-sm">
                        {{ __('common.close') }}
                    </button>
                </x-slot>
            </x-ui.modal>
        @endif
    @endif

@endsection

@section('save')
    @if($isMailServerTested)
        <x-save
            id_confirmation="confirmProfileTwoFactorModal"
            :label="__('common.update')"
            :title="__('admin/profile.confirm_title')"
            :message="__('admin/profile.confirm_message')"
            :confirm_label="__('common.update')"
            :cancel_label="__('common.cancel')"
            form="profile-two-factor-form"
        />
    @endif
@endsection
