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
    <form method="POST" action="{{ route('admin.members.settings.auth.update') }}" id="member-settings-form">
        @csrf
        <input type="hidden" name="settings_section" value="auth">

        <!-- ログイン通知設定 -->
        <section>
            <h2>{{ __('common.login_notification_mode.label') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <fieldset>
                <legend>{{ __('admin/members/settings/auth.login_notification_global_setting') }}</legend>
                <x-login-notification-selector
                    name="login_notification_mode"
                    :value="old('login_notification_mode', (string) $loginNotification)"
                    :globalSetting="null"
                    :excludeUseProfileSetting="false"
                    :columns="4"
                />
            </fieldset>
        </section>

        <!-- ログイン試行制限設定 -->
        <section>
            <h2>{{ __('admin/members/settings/auth.login_attempt_limit_settings') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <x-login-attempt-limit-settings
                :enabled="$loginAttemptLimitEnabled"
                :maxAttempts="$loginAttemptMaxAttempts"
                :maxAttemptsIp="$loginAttemptMaxAttemptsIp"
                :timeWindow="$loginAttemptTimeWindow"
                :lockoutDuration="$loginAttemptLockoutDuration"
                :notificationEnabled="$lockoutNotificationEnabled"
                :showIpBasedAttempts="true"
            />
        </section>

        <!-- 二段階認証設定 -->
        <section>
            <h2>{{ __('auth.two_fa_settings') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            
            <div x-data="{
                twoFaMode: '{{ old('two_fa_force_mode', (string) $twoFaForceMode) }}',
                passkeyMode: '{{ old('two_fa_passkey_mode', (string) $twoFaPasskeyMode) }}',
                defaultMethod: '{{ old('two_fa_default_method', (string) $twoFaDefaultMethod) }}',
                get twoFaEnabled() {
                    return this.twoFaMode !== '0';
                },
                get passkeyEnabled() {
                    return this.passkeyMode !== '0';
                },
                init() {
                    this.$watch('passkeyMode', value => {
                        if (value === '0') {
                            this.defaultMethod = '0';
                        }
                    });
                }
            }">
                {{-- 全体設定用の二段階認証設定コンポーネント --}}
                <x-two-fa.general-settings
                    twoFaModeName="two_fa_force_mode"
                    :twoFaModeValue="(string) $twoFaForceMode"
                    twoFaPasskeyModeName="two_fa_passkey_mode"
                    :twoFaPasskeyModeValue="(string) $twoFaPasskeyMode"
                    twoFaDefaultMethodName="two_fa_default_method"
                    :twoFaDefaultMethodValue="(string) $twoFaDefaultMethod"
                    :columns="4"
                />

                <x-two-fa.detailed-settings
                    :expireMinutes="$twoFaExpireMinutes"
                    :resendIntervalSeconds="$twoFaResendIntervalSeconds"
                    :maxAttempts="$twoFaMaxAttempts"
                    :attemptWindow="$twoFaAttemptWindow"
                    :lockoutDuration="$twoFaLockoutDuration"
                    :lockoutNotificationEnabled="$twoFaLockoutNotificationEnabled"
                    :recoveryCodesCount="$twoFaRecoveryCodesCount ?? 5"
                    :recoveryCodeRegenerateInterval="$twoFaRecoveryCodeRegenerateInterval ?? 24"
                />
            </div>
        </section>

        <!-- CAPTCHA設定（管理画面ログイン用） -->
        <section>
            <h2>{{ __('admin/members/settings/auth.captcha_admin_login_settings') }}</h2>
            <p class="mb-2">{{ __('admin/members/settings/auth.captcha_admin_login_settings_description') }}</p>

            <x-captcha-settings
                :captchaEnabled="$captchaEnabled"
                :captchaAuthenticationResult="$captchaAuthenticationResult"
                :settingsUrl="route('admin.settings.security.captcha')"
                :screens="[
                    [
                        'name' => 'captcha_admin_login_enabled',
                        'label' => __('admin/members/settings/auth.captcha_admin_login_enabled'),
                        'value' => $captchaAdminLoginEnabled,
                    ],
                    [
                        'name' => 'captcha_password_reset_enabled',
                        'label' => __('admin/members/settings/auth.captcha_password_reset_enabled'),
                        'value' => $captchaPasswordResetEnabled,
                    ],
                ]"
            />
        </section>
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
        form="member-settings-form"
    />
@endsection

@section('scripts')
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('[id$="Modal"]');

            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            const confirmationModal = document.getElementById('confirmationModal');

            if (confirmationModal) {
                const confirmButton = confirmationModal.querySelector('button[type="submit"]');
                if (confirmButton) {
                    confirmButton.addEventListener('click', () => {
                        document.getElementById('member-settings-form').submit();
                    });
                }
            }
        });
    </script>
@endsection
