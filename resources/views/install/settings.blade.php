{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

@extends('layouts.install')

@section('title', __('install/step1.settings_title'))
@section('header', __('install/step1.settings_header'))
@section('description', __('install/step1.settings_description'))

@section('content')

<form action="{{ route('install.settings.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- サイト基本情報 -->
    <section aria-labelledby="site-info-heading">
        <h2 id="site-info-heading" class="sr-only">{{ __('install/step1.site_information') }}</h2>
        
        <fieldset class="space-y-4">
            <div>
                <x-form-label for="site_name" :text="__('install/step1.site_name')" :required="true" />
                <x-form-text
                    name="site_name"
                    id="site_name"
                    :value="old('site_name', session('install_data.site_name', ''))"
                    :required="true"
                    class="input-full"
                />
            </div>
        </fieldset>
    </section>

    <!-- 管理者アカウント情報 -->
    <section aria-labelledby="admin-account-heading">
        <h2 id="admin-account-heading" class="sr-only">{{ __('install/step1.admin_account_information') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install/step1.admin_account_details') }}</legend>
            
            <div>
                <x-form-label for="admin_account_name" :text="__('install/step1.admin_account_name')" :required="true" />
                <x-form-text
                    name="admin_account_name"
                    id="admin_account_name"
                    :value="old('admin_account_name', session('install_data.admin_account_name', ''))"
                    pattern="^[a-zA-Z0-9]+$"
                    minlength="3"
                    maxlength="20"
                    :required="true"
                    :placeholder="__('install/step1.admin_account_name_placeholder')"
                    x-on:invalid="$el.setCustomValidity('{{ __('install/step1.validation.admin_account_name_required') }}')"
                    x-on:input="$el.setCustomValidity('')"
                    ariaDescribedby="admin_account_name_help"
                    class="input-full"
                />
                <x-form-help-text :text="__('install/step1.admin_account_name_requirements')" id="admin_account_name_help" />
            </div>

            <div>
                <x-form-label for="admin_display_name" :text="__('install/step1.admin_display_name')" />
                <x-form-text
                    name="admin_display_name"
                    id="admin_display_name"
                    :value="old('admin_display_name', session('install_data.admin_display_name', ''))"
                    maxlength="255"
                    :placeholder="__('install/step1.admin_display_name_placeholder')"
                    ariaDescribedby="admin_display_name_help"
                    class="input-full"
                />
                <x-form-help-text :text="__('install/step1.admin_display_name_requirements')" id="admin_display_name_help" />
            </div>

            <div>
                <x-form-label for="admin_email" :text="__('install/step1.admin_email')" :required="true" />
                <x-form-text
                    type="email"
                    name="admin_email"
                    id="admin_email"
                    :value="old('admin_email', session('install_data.admin_email', ''))"
                    :required="true"
                    autocomplete="username"
                    class="input-full"
                />
            </div>
        </fieldset>
    </section>

    <!-- パスワード設定 -->
    <section aria-labelledby="password-heading">
        <h2 id="password-heading" class="sr-only">{{ __('install/step1.password_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install/step1.password_setup') }}</legend>
            
            <div>
                <x-form-label for="admin_password" :text="__('install/step1.admin_password')" :required="true" />
                <x-form-password-tools
                    name="admin_password"
                    id="admin_password"
                    :required="false"
                    :showConfirmation="true"
                    :disableConfirmationCopyPaste="true"
                    :minLength="8"
                    :requireUppercase="true"
                    :requireLowercase="true"
                    :requireNumber="true"
                    :requireSymbol="true"
                    :recommendedLength="12"
                />
            </div>
        </fieldset>
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install/common.form_navigation') }}" class="flex justify-center mt-6">
        <a href="{{ route('install.mode') }}"
           class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 mx-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install/common.back') }}
        </a>
        <x-form-button
            type="submit"
            variant="primary"
            :label="__('install/common.next')"
            class="mx-4"
        />
    </nav>
</form>

@endsection