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
            <p>{{ __('admin/members/settings/auth.login_attempt_limit_description') }}</p>
            
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                            {{ __('admin/members/settings/auth.managed_in_security_settings') }}
                        </p>
                        <a href="{{ route('admin.settings.security.login-attempt') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                            <i class="fas fa-external-link-alt"></i>
                            {{ __('admin/members/settings/auth.go_to_security_settings') }}
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin/members/settings/auth.current_settings') }}
                </h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• {{ __('admin/members/settings/auth.status') }}: {{ $loginAttemptLimitEnabled ? __('common.enabled') : __('common.disabled') }}</li>
                    @if($loginAttemptLimitEnabled)
                        <li>• {{ __('admin/members/settings/auth.max_attempts') }}: {{ $loginAttemptMaxAttempts }}{{ __('admin/members/settings/auth.times') }}</li>
                        <li>• {{ __('admin/members/settings/auth.time_window') }}: {{ $loginAttemptTimeWindow }}{{ __('admin/members/settings/auth.minutes') }}</li>
                        <li>• {{ __('admin/members/settings/auth.lockout_duration') }}: {{ $loginAttemptLockoutDuration }}{{ __('admin/members/settings/auth.minutes') }}</li>
                    @endif
                </ul>
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
            </div>
            
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                            {{ __('admin/members/settings/auth.two_fa_detailed_settings_in_security') }}
                        </p>
                        <a href="{{ route('admin.settings.security.login-attempt') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                            <i class="fas fa-external-link-alt"></i>
                            {{ __('admin/members/settings/auth.go_to_security_settings') }}
                        </a>
                    </div>
                </div>
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
