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
    <form id="security-auth-form" method="POST" action="{{ route('admin.settings.security.auth.update') }}">
        @csrf
        
        <!-- セッション管理設定 -->
        <section>
            <h2>{{ __('admin/settings/security/auth.session_management') }}</h2>
            <p>{{ __('admin/settings/security/auth.session_management_description') }}</p>

            <!-- セッション暗号化 -->
            <fieldset>
                <x-form.toggle
                    name="session_encrypt"
                    :label="__('admin/settings/security/auth.session_encrypt')"
                    :checked="old('session_encrypt', $settings['session_encrypt'])"
                />
                <p class="mt-2">{{ __('admin/settings/security/auth.session_encrypt_help') }}</p>
            </fieldset>

            <!-- デフォルトセッション有効時間 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/auth.session_lifetime') }}</legend>
                
                <div class="flex items-center space-x-3 mt-2">
                    <x-form.text
                        id="session_lifetime"
                        name="session_lifetime"
                        type="number"
                        :min="1"
                        :max="43200"
                        :value="old('session_lifetime', $settings['session_lifetime'])"
                        class="input-common input-sm"
                        aria-describedby="session_lifetime_unit session_lifetime_help"
                    />
                    <span id="session_lifetime_unit" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('common.minutes') }}
                    </span>
                </div>
                
                <p id="session_lifetime_help">{{ __('admin/settings/security/auth.session_lifetime_help') }}</p>
            </fieldset>
        </section>

        <!-- パスワードセキュリティ設定 -->
        <section>
            <h2>{{ __('admin/settings/security/auth.password_security_settings') }}</h2>
            <p>{{ __('admin/settings/security/auth.password_security_description') }}</p>

            <!-- パスワード漏洩チェック -->
            <fieldset>
                <legend>{{ __('admin/settings/security/auth.pwned_password_check') }}</legend>
                <p>{{ __('admin/settings/security/auth.pwned_password_check_help') }}</p>
                
                <x-form.toggle
                    :label="__('common.enabled')"
                    id="pwned_password_check_enabled"
                    name="pwned_password_check_enabled"
                    :checked="old('pwned_password_check_enabled', $settings['pwned_password_check_enabled'])"
                />
                
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p>{{ __('admin/settings/security/auth.pwned_password_api_info') }}</p>
                        </div>
                    </div>
                </div>
            </fieldset>
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
        form="security-auth-form"
    />
@endsection
