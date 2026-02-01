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
        
        

        <!-- デフォルトパスワードポリシー -->
        <section>
            <h2>{{ __('admin/settings/security/password.default_password_policy') }}</h2>
            <p>{{ __('admin/settings/security/password.default_password_policy_description') }}</p>

            <fieldset>
                <legend>{{ __('admin/settings/security/password.min_length') }}</legend>
                <x-form.radio-card-group
                    name="password_min_length"
                    :options="$minLengthOptions"
                    :value="old('password_min_length', (string) $settings['password_min_length'])"
                    :columns="3"
                    class="mb-4"
                />
            </fieldset>

            <!-- 大文字 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_uppercase"
                    :label="__('admin/settings/security/password.require_uppercase')"
                    :checked="old('password_require_uppercase', $settings['password_require_uppercase'])"
                />
            </fieldset>

            <!-- 数字 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_number"
                    :label="__('admin/settings/security/password.require_number')"
                    :checked="old('password_require_number', $settings['password_require_number'])"
                />
            </fieldset>

            <!-- 記号 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_symbol"
                    :label="__('admin/settings/security/password.require_symbol')"
                    :checked="old('password_require_symbol', $settings['password_require_symbol'])"
                />
            </fieldset>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                {{ __('admin/settings/security/password.security_warning') }}
            </p>
        </section>

        <!-- パスワードリセット機能 -->
        <section>
            <h2>{{ __('admin/settings/security/password.password_reset_feature') }}</h2>
            <p>{{ __('admin/settings/security/password.password_reset_feature_description') }}</p>

            <fieldset>
                <x-form.toggle
                    name="password_reset_enabled"
                    :label="__('admin/settings/security/password.reset_enabled')"
                    :checked="old('password_reset_enabled', $settings['password_reset_enabled'])"
                />
            </fieldset>
        </section>

        <!-- 共通設定 -->
        <section>
            <h2>{{ __('admin/settings/security/password.pwned_password_check') }}</h2>
            <p>{{ __('admin/settings/security/password.pwned_password_check_description') }}</p>

            <!-- パスワード漏洩チェック -->
            <fieldset>
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
