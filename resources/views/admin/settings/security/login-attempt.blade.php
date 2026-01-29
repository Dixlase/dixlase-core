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

            <fieldset>
                <legend>{{ __('admin/settings/security/login-attempt.basic_settings') }}</legend>
                
                <!-- 有効/無効 -->
                <x-form.toggle
                    :label="__('admin/settings/security/login-attempt.enabled')"
                    id="login_attempt_limit_enabled_default"
                    name="login_attempt_limit_enabled_default"
                    :checked="old('login_attempt_limit_enabled_default', $settings['login_attempt_limit_enabled_default'])"
                />
                
                <!-- 最大試行回数 -->
                <x-form.text
                    :label="__('admin/settings/security/login-attempt.max_attempts')"
                    type="number"
                    id="login_attempt_max_attempts_default"
                    name="login_attempt_max_attempts_default"
                    :value="old('login_attempt_max_attempts_default', $settings['login_attempt_max_attempts_default'])"
                    min="1"
                    max="100"
                    required
                    :help="__('admin/settings/security/login-attempt.max_attempts_help')"
                />
                
                <!-- IP最大試行回数 -->
                <x-form.text
                    :label="__('admin/settings/security/login-attempt.max_attempts_ip')"
                    type="number"
                    id="login_attempt_max_attempts_ip_default"
                    name="login_attempt_max_attempts_ip_default"
                    :value="old('login_attempt_max_attempts_ip_default', $settings['login_attempt_max_attempts_ip_default'])"
                    min="1"
                    max="200"
                    required
                    :help="__('admin/settings/security/login-attempt.max_attempts_ip_help')"
                />
                
                <!-- 時間窓 -->
                <x-form.text
                    :label="__('admin/settings/security/login-attempt.time_window')"
                    type="number"
                    id="login_attempt_time_window_default"
                    name="login_attempt_time_window_default"
                    :value="old('login_attempt_time_window_default', $settings['login_attempt_time_window_default'])"
                    min="1"
                    max="1440"
                    required
                    :help="__('admin/settings/security/login-attempt.time_window_help')"
                />
                
                <!-- ロックアウト時間 -->
                <x-form.text
                    :label="__('admin/settings/security/login-attempt.lockout_duration')"
                    type="number"
                    id="login_attempt_lockout_duration_default"
                    name="login_attempt_lockout_duration_default"
                    :value="old('login_attempt_lockout_duration_default', $settings['login_attempt_lockout_duration_default'])"
                    min="1"
                    max="10080"
                    required
                    :help="__('admin/settings/security/login-attempt.lockout_duration_help')"
                />
                
                <!-- ロックアウト通知 -->
                <x-form.toggle
                    :label="__('admin/settings/security/login-attempt.lockout_notification_enabled')"
                    id="login_attempt_lockout_notification_enabled_default"
                    name="login_attempt_lockout_notification_enabled_default"
                    :checked="old('login_attempt_lockout_notification_enabled_default', $settings['login_attempt_lockout_notification_enabled_default'])"
                    :help="__('admin/settings/security/login-attempt.lockout_notification_help')"
                />
            </fieldset>

            <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-2">
                    <i class="fas fa-lightbulb text-yellow-500 mt-0.5"></i>
                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                        <p>{{ __('admin/settings/security/login-attempt.plugin_custom_hint') }}</p>
                    </div>
                </div>
            </div>
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
