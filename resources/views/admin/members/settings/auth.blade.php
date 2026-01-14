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
            <fieldset>
                <x-form.toggle
                    name="login_attempt_limit_enabled"
                    :label="__('admin/members/settings/auth.login_attempt_limit_enabled')"
                    :checked="old('login_attempt_limit_enabled', $loginAttemptLimitEnabled)"
                />
                <p class="mt-2">
                    {{ __('admin/members/settings/auth.login_attempt_limit_help') }}
                </p>
            </fieldset>

            <div>
                <fieldset>
                    <legend>{{ __('admin/members/settings/auth.login_attempt_max_attempts') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_max_attempts"
                        :value="old('login_attempt_max_attempts', $loginAttemptMaxAttempts)"
                        :min="1"
                        :max="100"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin/members/settings/auth.login_attempt_max_attempts_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/members/settings/auth.login_attempt_max_attempts_ip') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_max_attempts_ip"
                        :value="old('login_attempt_max_attempts_ip', $loginAttemptMaxAttemptsIp)"
                        :min="1"
                        :max="100"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin/members/settings/auth.login_attempt_max_attempts_ip_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/members/settings/auth.login_attempt_time_window') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_time_window"
                        :value="old('login_attempt_time_window', $loginAttemptTimeWindow)"
                        :min="1"
                        :max="1440"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin/members/settings/auth.login_attempt_time_window_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/members/settings/auth.login_attempt_lockout_duration') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_lockout_duration"
                        :value="old('login_attempt_lockout_duration', $loginAttemptLockoutDuration)"
                        :min="1"
                        :max="10080"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin/members/settings/auth.login_attempt_lockout_duration_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <x-form.toggle
                        name="login_attempt_lockout_notification_enabled"
                        :label="__('admin/members/settings/auth.lockout_notification_enabled')"
                        :checked="old('login_attempt_lockout_notification_enabled', $lockoutNotificationEnabled)"
                    />
                    <p class="mt-2">
                        {!! __('admin/members/settings/auth.lockout_notification_help') !!}
                    </p>
                </fieldset>
            </div>
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

            <fieldset>
                <legend>{{ __('admin/members/settings/auth.two_fa_expire_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="two_fa_expire_minutes" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.two_fa_expire_minutes') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_expire_minutes"
                                name="two_fa_expire_minutes"
                                :value="old('two_fa_expire_minutes', $twoFaExpireMinutes)"
                                :min="1"
                                :max="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_expire_minutes_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="two_fa_resend_interval_seconds" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.two_fa_resend_interval_seconds') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_resend_interval_seconds"
                                name="two_fa_resend_interval_seconds"
                                :value="old('two_fa_resend_interval_seconds', $twoFaResendIntervalSeconds)"
                                :min="60"
                                :max="600"
                                :step="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.seconds') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_resend_interval_seconds_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin/members/settings/auth.two_fa_attempt_limit_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="two_fa_max_attempts" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.two_fa_max_attempts') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_max_attempts"
                                name="two_fa_max_attempts"
                                :value="old('two_fa_max_attempts', $twoFaMaxAttempts)"
                                :min="1"
                                :max="10"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.times') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_max_attempts_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="two_fa_attempt_window" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.two_fa_attempt_window') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_attempt_window"
                                name="two_fa_attempt_window"
                                :value="old('two_fa_attempt_window', $twoFaAttemptWindow)"
                                :min="5"
                                :max="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_attempt_window_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="two_fa_lockout_duration" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.two_fa_lockout_duration') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_lockout_duration"
                                name="two_fa_lockout_duration"
                                :value="old('two_fa_lockout_duration', $twoFaLockoutDuration)"
                                :min="5"
                                :max="1440"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_lockout_duration_help') }}
                        </p>
                    </div>

                    <div>
                        <x-form.toggle
                            name="two_fa_lockout_notification_enabled"
                            :label="__('admin/members/settings/auth.two_fa_lockout_notification_enabled')"
                            :checked="old('two_fa_lockout_notification_enabled', $twoFaLockoutNotificationEnabled)"
                        />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.two_fa_lockout_notification_enabled_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin/members/settings/auth.recovery_code_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="two_fa_recovery_codes_count" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.recovery_codes_count') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_recovery_codes_count"
                                name="two_fa_recovery_codes_count"
                                :value="old('two_fa_recovery_codes_count', $twoFaRecoveryCodesCount ?? 5)"
                                :min="1"
                                :max="10"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.codes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.recovery_codes_count_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="two_fa_recovery_code_regenerate_interval" class="block text-sm font-medium">
                            {{ __('admin/members/settings/auth.recovery_code_regenerate_interval') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_fa_recovery_code_regenerate_interval"
                                name="two_fa_recovery_code_regenerate_interval"
                                :value="old('two_fa_recovery_code_regenerate_interval', $twoFaRecoveryCodeRegenerateInterval ?? 24)"
                                :min="1"
                                :max="168"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.hours') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin/members/settings/auth.recovery_code_regenerate_interval_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>
        </section>

        <!-- CAPTCHA設定（管理画面ログイン用） -->
        <section>
            <h2>{{ __('admin/members/settings/auth.captcha_admin_login_settings') }}</h2>
            <p class="mb-2">{{ __('admin/members/settings/auth.captcha_admin_login_settings_description') }}</p>

            @if(!$captchaEnabled)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings/auth.captcha_not_enabled', ['url' => route('admin.settings.security.captcha')])"
                />
            @elseif(!$captchaAuthenticationResult)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings/auth.captcha_not_authenticated', ['url' => route('admin.settings.security.captcha')])"
                />
            @endif

            <fieldset>
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('admin/members/settings/auth.captcha_screens') }}
                </legend>
                <div class="space-y-3">
                    @if(!$captchaAvailable)
                        <x-form.hidden
                            name="captcha_admin_login_enabled"
                            :value="$captchaAdminLoginEnabled ? '1' : '0'"
                        />
                        <x-form.hidden
                            name="captcha_password_reset_enabled"
                            :value="$captchaPasswordResetEnabled ? '1' : '0'"
                        />
                    @endif
                    <x-form.toggle
                        name="captcha_admin_login_enabled"
                        :label="__('admin/members/settings/auth.captcha_admin_login_enabled')"
                        :checked="old('captcha_admin_login_enabled', $captchaAdminLoginEnabled)"
                        :disabled="!$captchaAvailable"
                    />
                    <x-form.toggle
                        name="captcha_password_reset_enabled"
                        :label="__('admin/members/settings/auth.captcha_password_reset_enabled')"
                        :checked="old('captcha_password_reset_enabled', $captchaPasswordResetEnabled)"
                        :disabled="!$captchaAvailable"
                    />
                </div>
            </fieldset>
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
    <x-modal
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
