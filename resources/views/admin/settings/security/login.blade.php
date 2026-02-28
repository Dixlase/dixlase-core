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
    <form id="security-login-form" method="POST" action="{{ route('admin.settings.security.login.update') }}">
        @csrf
        
        <!-- ログイン識別子モード設定 -->
        <section>
            <h2>{{ __('admin/settings/security/login.login_identifier_mode_settings') }}</h2>
            <p>{{ __('admin/settings/security/login.login_identifier_mode_description') }}</p>

            <x-security.login-identifier-mode-selector
                name="login_identifier_mode"
                :value="old('login_identifier_mode', (string) $loginIdentifierMode)"
                :columns="3"
            />
        </section>

        <!-- ログイン通知設定 -->
        <section>
            <h2>{{ __('admin/settings/security/login.login_notification_settings') }}</h2>
            <p>{{ __('admin/settings/security/login.login_notification_description') }}</p>

            <fieldset>
                <legend>{{ __('admin/settings/security/login.login_notification_mode') }}</legend>
                <x-security.login-notification-selector
                    name="login_notification_mode"
                    :value="old('login_notification_mode', (string) $loginNotificationMode)"
                    :globalSetting="null"
                    :excludeUseProfileSetting="false"
                    :columns="4"
                />
            </fieldset>
        </section>

        <!-- デフォルトログイン試行制限設定 -->
        <section>
            <h2>{{ __('admin/settings/security/login.default_login_attempt_settings') }}</h2>
            <p>{{ __('admin/settings/security/login.default_login_attempt_description') }}</p>

            <div x-data="{ enabled: '{{ old('login_attempt_limit_enabled', $settings['login_attempt_limit_enabled']) ? '1' : '0' }}' }">
                <fieldset>
                    <x-form-toggle
                        name="login_attempt_limit_enabled"
                        :label="__('admin/settings/security/login.enabled')"
                        :checked="old('login_attempt_limit_enabled', $settings['login_attempt_limit_enabled'])"
                        x-model="enabled"
                    />
                    <p class="mt-2">
                        {{ __('admin/settings/security/login.enabled_help') }}
                    </p>
                </fieldset>

                <div :class="{ 'opacity-50 pointer-events-none': enabled === '0' }">
                    <input type="hidden" name="login_attempt_max_attempts" :value="enabled === '1' ? null : '{{ $settings['login_attempt_max_attempts'] }}'" x-show="enabled === '0'">
                    <input type="hidden" name="login_attempt_max_attempts_ip" :value="enabled === '1' ? null : '{{ $settings['login_attempt_max_attempts_ip'] }}'" x-show="enabled === '0'">
                    <input type="hidden" name="login_attempt_time_window" :value="enabled === '1' ? null : '{{ $settings['login_attempt_time_window'] }}'" x-show="enabled === '0'">
                    <input type="hidden" name="login_attempt_lockout_duration" :value="enabled === '1' ? null : '{{ $settings['login_attempt_lockout_duration'] }}'" x-show="enabled === '0'">
                    <input type="hidden" name="login_attempt_lockout_notification_enabled" :value="enabled === '1' ? null : '{{ $settings['login_attempt_lockout_notification_enabled'] ? '1' : '0' }}'" x-show="enabled === '0'">

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login.max_attempts') }}</legend>
                        <x-form-text
                            type="number"
                            name="login_attempt_max_attempts"
                            :value="old('login_attempt_max_attempts', $settings['login_attempt_max_attempts'])"
                            :min="1"
                            :max="100"
                            class="input-common input-sm"
                            ::disabled="enabled === '0'"
                        />
                        <p>
                            {{ __('admin/settings/security/login.max_attempts_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login.max_attempts_ip') }}</legend>
                        <x-form-text
                            type="number"
                            name="login_attempt_max_attempts_ip"
                            :value="old('login_attempt_max_attempts_ip', $settings['login_attempt_max_attempts_ip'])"
                            :min="1"
                            :max="200"
                            class="input-common input-sm"
                            ::disabled="enabled === '0'"
                        />
                        <p>
                            {{ __('admin/settings/security/login.max_attempts_ip_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login.time_window') }}</legend>
                        <x-form-text
                            type="number"
                            name="login_attempt_time_window"
                            :value="old('login_attempt_time_window', $settings['login_attempt_time_window'])"
                            :min="1"
                            :max="1440"
                            class="input-common input-sm"
                            ::disabled="enabled === '0'"
                        />
                        <p>
                            {{ __('admin/settings/security/login.time_window_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <legend>{{ __('admin/settings/security/login.lockout_duration') }}</legend>
                        <x-form-text
                            type="number"
                            name="login_attempt_lockout_duration"
                            :value="old('login_attempt_lockout_duration', $settings['login_attempt_lockout_duration'])"
                            :min="1"
                            :max="10080"
                            class="input-common input-sm"
                            ::disabled="enabled === '0'"
                        />
                        <p>
                            {{ __('admin/settings/security/login.lockout_duration_help') }}
                        </p>
                    </fieldset>

                    <fieldset>
                        <x-form-toggle
                            name="login_attempt_lockout_notification_enabled"
                            :label="__('admin/settings/security/login.lockout_notification_enabled')"
                            :checked="old('login_attempt_lockout_notification_enabled', $settings['login_attempt_lockout_notification_enabled'])"
                            ::disabled="enabled === '0'"
                        />
                        <p class="mt-2">
                            {!! __('admin/settings/security/login.lockout_notification_help') !!}
                        </p>
                    </fieldset>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-login-form"
    />
@endsection
