{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <form id="security-login-attempt-form" method="POST" action="{{ route('admin.settings.security.login-attempt.update') }}">
        @csrf
        
        <!-- デフォルトログイン試行制限設定 -->
        <section>
            <h2>{{ __('admin/settings/security/login-attempt.default_login_attempt_settings') }}</h2>
            <p>{{ __('admin/settings/security/login-attempt.default_login_attempt_description') }}</p>

            <div x-data="{ enabled: {{ old('login_attempt_limit_enabled_default', $settings['login_attempt_limit_enabled_default']) ? 'true' : 'false' }} }">
                <fieldset>
                    <x-form.toggle
                        name="login_attempt_limit_enabled_default"
                        :label="__('admin/settings/security/login-attempt.enabled')"
                        :checked="old('login_attempt_limit_enabled_default', $settings['login_attempt_limit_enabled_default'])"
                        x-model="enabled"
                    />
                    <p class="mt-2">
                        {{ __('admin/settings/security/login-attempt.enabled_help') }}
                    </p>
                </fieldset>

                <div :class="{ 'opacity-50 pointer-events-none': !enabled }">
                    <input type="hidden" name="login_attempt_max_attempts_default" :value="enabled ? null : '{{ $settings['login_attempt_max_attempts_default'] }}'" x-show="!enabled">
                    <input type="hidden" name="login_attempt_max_attempts_ip_default" :value="enabled ? null : '{{ $settings['login_attempt_max_attempts_ip_default'] }}'" x-show="!enabled">
                    <input type="hidden" name="login_attempt_time_window_default" :value="enabled ? null : '{{ $settings['login_attempt_time_window_default'] }}'" x-show="!enabled">
                    <input type="hidden" name="login_attempt_lockout_duration_default" :value="enabled ? null : '{{ $settings['login_attempt_lockout_duration_default'] }}'" x-show="!enabled">
                    <input type="hidden" name="login_attempt_lockout_notification_enabled_default" :value="enabled ? null : '{{ $settings['login_attempt_lockout_notification_enabled_default'] ? '1' : '0' }}'" x-show="!enabled">

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login-attempt.max_attempts') }}</legend>
                        <x-form.text
                            type="number"
                            name="login_attempt_max_attempts_default"
                            :value="old('login_attempt_max_attempts_default', $settings['login_attempt_max_attempts_default'])"
                            :min="1"
                            :max="100"
                            class="input-common input-sm"
                            ::disabled="!enabled"
                        />
                        <p>
                            {{ __('admin/settings/security/login-attempt.max_attempts_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login-attempt.max_attempts_ip') }}</legend>
                        <x-form.text
                            type="number"
                            name="login_attempt_max_attempts_ip_default"
                            :value="old('login_attempt_max_attempts_ip_default', $settings['login_attempt_max_attempts_ip_default'])"
                            :min="1"
                            :max="200"
                            class="input-common input-sm"
                            ::disabled="!enabled"
                        />
                        <p>
                            {{ __('admin/settings/security/login-attempt.max_attempts_ip_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login-attempt.time_window') }}</legend>
                        <x-form.text
                            type="number"
                            name="login_attempt_time_window_default"
                            :value="old('login_attempt_time_window_default', $settings['login_attempt_time_window_default'])"
                            :min="1"
                            :max="1440"
                            class="input-common input-sm"
                            ::disabled="!enabled"
                        />
                        <p>
                            {{ __('admin/settings/security/login-attempt.time_window_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login-attempt.lockout_duration') }}</legend>
                        <x-form.text
                            type="number"
                            name="login_attempt_lockout_duration_default"
                            :value="old('login_attempt_lockout_duration_default', $settings['login_attempt_lockout_duration_default'])"
                            :min="1"
                            :max="10080"
                            class="input-common input-sm"
                            ::disabled="!enabled"
                        />
                        <p>
                            {{ __('admin/settings/security/login-attempt.lockout_duration_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <x-form.toggle
                            name="login_attempt_lockout_notification_enabled_default"
                            :label="__('admin/settings/security/login-attempt.lockout_notification_enabled')"
                            :checked="old('login_attempt_lockout_notification_enabled_default', $settings['login_attempt_lockout_notification_enabled_default'])"
                            ::disabled="!enabled"
                        />
                        <p class="mt-2">
                            {!! __('admin/settings/security/login-attempt.lockout_notification_help') !!}
                        </p>
                    </fieldset>
                </div>
            </div>
        </section>

        <!-- 二段階認証の詳細設定 -->
        <section>
            <h2>{{ __('admin/settings/security/login-attempt.two_fa_detailed_settings') }}</h2>
            <p>{{ __('admin/settings/security/login-attempt.two_fa_detailed_settings_description') }}</p>

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
        </section>

    </form>
</div>
@endsection

@section('save')
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-login-attempt-form"
    />
@endsection
