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
    <form id="security-password-form" method="POST" action="{{ route('admin.settings.security.password.update') }}">
        @csrf
        
        <!-- 共通設定 -->
        <section>
            <h2>{{ __('admin/settings/security/password.common_settings') }}</h2>
            <p>{{ __('admin/settings/security/password.common_settings_description') }}</p>

            <!-- パスワード漏洩チェック -->
            <fieldset>
                <legend>{{ __('admin/settings/security/password.pwned_password_check') }}</legend>
                <p>{{ __('admin/settings/security/password.pwned_password_check_help') }}</p>
                
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
                            <p>{{ __('admin/settings/security/password.pwned_password_api_info') }}</p>
                        </div>
                    </div>
                </div>
            </fieldset>
        </section>

        <!-- デフォルトパスワードポリシー -->
        <section>
            <h2>{{ __('admin/settings/security/password.default_password_policy') }}</h2>
            <p>{{ __('admin/settings/security/password.default_password_policy_description') }}</p>

            <fieldset>
                <legend>{{ __('admin/settings/security/password.password_requirements') }}</legend>
                
                <!-- 最小文字数 -->
                <x-form.input
                    :label="__('admin/settings/security/password.min_length')"
                    type="number"
                    id="password_min_length_default"
                    name="password_min_length_default"
                    :value="old('password_min_length_default', $settings['password_min_length_default'])"
                    min="4"
                    max="128"
                    required
                />
                
                <!-- 大文字要求 -->
                <x-form.toggle
                    :label="__('admin/settings/security/password.require_uppercase')"
                    id="password_require_uppercase_default"
                    name="password_require_uppercase_default"
                    :checked="old('password_require_uppercase_default', $settings['password_require_uppercase_default'])"
                />
                
                <!-- 数字要求 -->
                <x-form.toggle
                    :label="__('admin/settings/security/password.require_number')"
                    id="password_require_number_default"
                    name="password_require_number_default"
                    :checked="old('password_require_number_default', $settings['password_require_number_default'])"
                />
                
                <!-- 記号要求 -->
                <x-form.toggle
                    :label="__('admin/settings/security/password.require_symbol')"
                    id="password_require_symbol_default"
                    name="password_require_symbol_default"
                    :checked="old('password_require_symbol_default', $settings['password_require_symbol_default'])"
                />
                
                <!-- パスワードリセット有効化 -->
                <x-form.toggle
                    :label="__('admin/settings/security/password.reset_enabled')"
                    id="password_reset_enabled_default"
                    name="password_reset_enabled_default"
                    :checked="old('password_reset_enabled_default', $settings['password_reset_enabled_default'])"
                />
            </fieldset>

            <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-2">
                    <i class="fas fa-lightbulb text-yellow-500 mt-0.5"></i>
                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                        <p>{{ __('admin/settings/security/password.plugin_custom_hint') }}</p>
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
        form="security-password-form"
    />
@endsection
