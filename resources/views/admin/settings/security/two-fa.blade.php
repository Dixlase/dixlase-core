{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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

@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto">
    @if($modeData['isPartial'] ?? false)
        <x-admin.mode-partial-notice />
    @endif

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
                    <x-ui-message
                        type="warning"
                        :message="__('admin/settings/security/two-fa.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                    />
                @endif
                
                {{-- 全体設定用の二段階認証設定コンポーネント --}}
                <x-security.two-fa-general-settings
                    twoFaModeName="two_fa_mode"
                    :twoFaModeValue="(string) $twoFaMode"
                    twoFaPasskeyModeName="two_fa_passkey_mode"
                    :twoFaPasskeyModeValue="(string) $twoFaPasskeyMode"
                    :columns="4"
                />
            </section>

            @if(!($modeData['isPartial'] ?? false))
            <x-security.passkey-device-settings
                :maxDevices="$twoFaPasskeyMaxDevices"
                :twoFaEnabled="true"
            />

            <!-- 二段階認証詳細設定 -->
            <section>
                <h2>{{ __('admin/settings/security/two-fa.two_fa_detailed_settings') }}</h2>
                <p>{{ __('admin/settings/security/two-fa.two_fa_detailed_settings_description') }}</p>

                <x-security.two-fa-detailed-settings
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
            @endif
        </div>
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button
        :label="__('common.update')"
        :title="__('common.update_confirmation_title')"
        :message="__('common.update_confirmation_message')"
        :confirm_label="__('common.update')"
        form="two-fa-settings-form"
    />
@endsection
