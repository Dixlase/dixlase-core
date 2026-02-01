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
<div class="max-w-7xl mx-auto">
    <form method="POST" action="{{ route('admin.settings.security.two-fa.update') }}" id="two-fa-settings-form">
        @csrf

        <div x-data="{
            twoFaMode: '{{ old('two_fa_mode', (string) $twoFaMode) }}',
            passkeyMode: '{{ old('two_fa_passkey_mode', (string) $twoFaPasskeyMode) }}',
            get twoFaEnabled() {
                return this.twoFaMode !== '0';
            },
            get passkeyEnabled() {
                return this.passkeyMode !== '0';
            }
        }">
            <!-- 二段階認証基本設定 -->
            <section>
                <h2>{{ __('admin/settings/security/two-fa.two_fa_basic_settings') }}</h2>
                <p>{{ __('admin/settings/security/two-fa.two_fa_basic_settings_description') }}</p>
                
                @if(!$isMailServerTested)
                    <x-message
                        type="warning"
                        :message="__('admin/settings/security/two-fa.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                    />
                @endif
                
                {{-- 全体設定用の二段階認証設定コンポーネント --}}
                <x-two-fa.general-settings
                    twoFaModeName="two_fa_mode"
                    :twoFaModeValue="(string) $twoFaMode"
                    twoFaPasskeyModeName="two_fa_passkey_mode"
                    :twoFaPasskeyModeValue="(string) $twoFaPasskeyMode"
                    :columns="4"
                />
            </section>

            <!-- パスキーデバイス管理設定 -->
            <section>
                <h2>{{ __('admin/settings/security/two-fa.passkey_device_management') }}</h2>
                <p>{{ __('admin/settings/security/two-fa.passkey_device_management_description') }}</p>

                <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
                    <input type="hidden" name="two_fa_passkey_max_devices" :value="twoFaEnabled ? null : '{{ $twoFaPasskeyMaxDevices }}'" x-show="!twoFaEnabled">

                    <div class="space-y-4">
                        <div>
                            <label for="two_fa_passkey_max_devices" class="block text-sm font-medium">
                                {{ __('admin/settings/security/two-fa.passkey_max_devices') }}
                            </label>
                            <div class="mt-1 flex items-center space-x-2">
                                <x-form.text
                                    type="number"
                                    id="two_fa_passkey_max_devices"
                                    name="two_fa_passkey_max_devices"
                                    :value="old('two_fa_passkey_max_devices', $twoFaPasskeyMaxDevices)"
                                    :min="1"
                                    :max="10"
                                    :step="1"
                                    class="input-common input-sm"
                                />
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/security/two-fa.devices_unit') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/security/two-fa.passkey_max_devices_help') }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 二段階認証詳細設定 -->
            <section>
                <h2>{{ __('admin/settings/security/two-fa.two_fa_detailed_settings') }}</h2>
                <p>{{ __('admin/settings/security/two-fa.two_fa_detailed_settings_description') }}</p>

                <x-two-fa.detailed-settings
                    :expireMinutes="$twoFaExpireMinutes"
                    :resendIntervalSeconds="$twoFaResendIntervalSeconds"
                    :maxAttempts="$twoFaMaxAttempts"
                    :attemptWindow="$twoFaAttemptWindow"
                    :lockoutDuration="$twoFaLockoutDuration"
                    :lockoutNotificationEnabled="$twoFaLockoutNotificationEnabled"
                    :recoveryCodesCount="$twoFaRecoveryCodesCount"
                    :recoveryCodeRegenerateInterval="$twoFaRecoveryCodeRegenerateInterval"
                />
            </section>
        </div>
    </form>
</div>
@endsection

@section('save')
    <x-form.button
        type="button"
        :label="__('common.update')"
        class="button-save"
        onclick="openModal('confirmationModal')"
    />
@endsection

@section('modals')
    <x-ui.modal
        id="confirmationModal"
        :title="__('common.update_confirmation_title')"
        :message="__('common.update_confirmation_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="two-fa-settings-form"
    />
@endsection
