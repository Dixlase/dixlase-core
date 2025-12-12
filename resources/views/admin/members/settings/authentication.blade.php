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
    <form method="POST" action="{{ route('admin.members.settings.authentication.update') }}" id="member-settings-form">
        @csrf
        <input type="hidden" name="settings_section" value="authentication">

        <!-- ログイン通知設定 -->
        <section>
            <h2>{{ __('common.login_notification_mode.label') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <fieldset>
                <legend>{{ __('admin.members.settings.auth.login_notification_global_setting') }}</legend>
                <x-form.radio-card-group
                    name="login_notification_mode"
                    :options="$loginNotificationGlobalOptions"
                    :value="old('login_notification_mode', (string) $loginNotification)"
                    :columns="4"
                />
            </fieldset>
        </section>

        <!-- ログイン試行制限設定 -->
        <section>
            <h2>{{ __('admin.members.settings.auth.login_attempt_limit_settings') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <fieldset>
                <x-form.toggle
                    name="login_attempt_limit_enabled"
                    :label="__('admin.members.settings.auth.login_attempt_limit_enabled')"
                    :checked="old('login_attempt_limit_enabled', $loginAttemptLimitEnabled)"
                />
                <p class="mt-2">
                    {{ __('admin.members.settings.auth.login_attempt_limit_help') }}
                </p>
            </fieldset>

            <div>
                <fieldset>
                    <legend>{{ __('admin.members.settings.auth.login_attempt_max_attempts') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_max_attempts"
                        :value="old('login_attempt_max_attempts', $loginAttemptMaxAttempts)"
                        :min="1"
                        :max="100"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin.members.settings.auth.login_attempt_max_attempts_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin.members.settings.auth.login_attempt_time_window') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_time_window"
                        :value="old('login_attempt_time_window', $loginAttemptTimeWindow)"
                        :min="1"
                        :max="1440"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin.members.settings.auth.login_attempt_time_window_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin.members.settings.auth.login_attempt_lockout_duration') }}</legend>
                    <x-form.text
                        type="number"
                        name="login_attempt_lockout_duration"
                        :value="old('login_attempt_lockout_duration', $loginAttemptLockoutDuration)"
                        :min="1"
                        :max="10080"
                        class="input-common input-sm"
                    />
                    <p>
                        {{ __('admin.members.settings.auth.login_attempt_lockout_duration_help') }}
                    </p>
                </fieldset>

                <fieldset>
                    <x-form.toggle
                        name="lockout_notification_enabled"
                        :label="__('admin.members.settings.auth.lockout_notification_enabled')"
                        :checked="old('lockout_notification_enabled', $lockoutNotificationEnabled)"
                    />
                    <p class="mt-2">
                        {!! __('admin.members.settings.auth.lockout_notification_help') !!}
                    </p>
                </fieldset>
            </div>
        </section>

        <!-- 二段階認証設定 -->
        <section>
            <h2>{{ __('common.two_factor_settings') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <fieldset>
                <legend>{{ __('admin.members.settings.auth.two_factor_mode_global_setting') }}</legend>
                <x-form.radio-card-group
                    name="force_2fa"
                    :options="$twoFactorGlobalOptions"
                    :value="old('force_2fa', (string) $force2fa)"
                    :columns="4"
                />
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.members.settings.auth.enabled_two_factor_methods_label') }}</legend>

                <div class="space-y-6">
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                                <span class="text-sm font-medium">{{ __('common.two_factor_method.numbered_options.0') }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">（常に有効）</span>
                        </div>

                        <div class="flex items-center space-x-3">
                            <x-form.toggle
                                name="enabled_2fa_passkey"
                                :label="__('common.two_factor_method.numbered_options.1')"
                                :checked="old('enabled_2fa_passkey', $passkeyEnabled ?? false)"
                            />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.enabled_two_factor_methods_help') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.email_always_enabled_note') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.members.settings.auth.two_factor_expire_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="two_factor_expire_minutes" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.two_factor_expire_minutes') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_factor_expire_minutes"
                                name="two_factor_expire_minutes"
                                :value="old('two_factor_expire_minutes', $twoFactorExpireMinutes)"
                                :min="1"
                                :max="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.two_factor_expire_minutes_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="two_factor_resend_interval_seconds" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.two_factor_resend_interval_seconds') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="two_factor_resend_interval_seconds"
                                name="two_factor_resend_interval_seconds"
                                :value="old('two_factor_resend_interval_seconds', $twoFactorResendIntervalSeconds)"
                                :min="60"
                                :max="600"
                                :step="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.seconds') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.two_factor_resend_interval_seconds_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.members.settings.auth.2fa_attempt_limit_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="2fa_max_attempts" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.2fa_max_attempts') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="2fa_max_attempts"
                                name="2fa_max_attempts"
                                :value="old('2fa_max_attempts', $twoFaMaxAttempts)"
                                :min="1"
                                :max="10"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.times') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.2fa_max_attempts_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="2fa_attempt_window" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.2fa_attempt_window') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="2fa_attempt_window"
                                name="2fa_attempt_window"
                                :value="old('2fa_attempt_window', $twoFaAttemptWindow)"
                                :min="5"
                                :max="60"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.2fa_attempt_window_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="2fa_lockout_duration" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.2fa_lockout_duration') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="2fa_lockout_duration"
                                name="2fa_lockout_duration"
                                :value="old('2fa_lockout_duration', $twoFaLockoutDuration)"
                                :min="5"
                                :max="1440"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.2fa_lockout_duration_help') }}
                        </p>
                    </div>

                    <div>
                        <x-form.toggle
                            name="2fa_lockout_notification_enabled"
                            :label="__('admin.members.settings.auth.2fa_lockout_notification_enabled')"
                            :checked="old('2fa_lockout_notification_enabled', $twoFaLockoutNotificationEnabled)"
                        />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.2fa_lockout_notification_enabled_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.members.settings.auth.recovery_code_settings') }}</legend>

                <div class="space-y-4">
                    <div>
                        <label for="recovery_codes_count" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.recovery_codes_count') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="recovery_codes_count"
                                name="recovery_codes_count"
                                :value="old('recovery_codes_count', $recoveryCodesCount ?? 5)"
                                :min="1"
                                :max="10"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.codes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.recovery_codes_count_help') }}
                        </p>
                    </div>

                    <div>
                        <label for="recovery_code_regenerate_interval" class="block text-sm font-medium">
                            {{ __('admin.members.settings.auth.recovery_code_regenerate_interval') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <x-form.text
                                type="number"
                                id="recovery_code_regenerate_interval"
                                name="recovery_code_regenerate_interval"
                                :value="old('recovery_code_regenerate_interval', $recoveryCodeRegenerateInterval ?? 24)"
                                :min="1"
                                :max="168"
                                class="input-common input-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.members.settings.hours') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.members.settings.auth.recovery_code_regenerate_interval_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>
        </section>

        <!-- CAPTCHA設定（管理画面ログイン用） -->
        <section>
            <h2>{{ __('admin.members.settings.auth.captcha_admin_login_settings') }}</h2>
            <p class="mb-2">{{ __('admin.members.settings.auth.captcha_admin_login_settings_description') }}</p>

            @if(!$captchaEnabled)
                <x-message
                    type="warning"
                    :message="__('admin.members.settings.auth.captcha_not_enabled', ['url' => route('admin.settings.security.captcha')])"
                />
            @elseif(!$captchaAuthenticationResult)
                <x-message
                    type="warning"
                    :message="__('admin.members.settings.auth.captcha_not_authenticated', ['url' => route('admin.settings.security.captcha')])"
                />
            @endif

            <fieldset>
                @if(!$captchaAvailable)
                    <x-form.hidden
                        name="captcha_admin_login_enabled"
                        :value="$captchaAdminLoginEnabled ? '1' : '0'"
                    />
                @endif
                <x-form.toggle
                    name="captcha_admin_login_enabled"
                    :label="__('admin.members.settings.auth.captcha_admin_login_enabled')"
                    :checked="old('captcha_admin_login_enabled', $captchaAdminLoginEnabled)"
                    :disabled="!$captchaAvailable"
                />
                <p class="mt-2">
                    {{ __('admin.members.settings.auth.captcha_admin_login_help') }}
                </p>
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
    <script>
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
