@extends('layouts.install')

@section('title', __('install.settings_title'))
@section('header', __('install.settings_header'))
@section('description', __('install.settings_description'))

@section('content')

<form action="{{ route('install.settings.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- サイト基本情報 -->
    <section aria-labelledby="site-info-heading">
        <h2 id="site-info-heading" class="sr-only">{{ __('install.site_information') }}</h2>
        
        <fieldset class="space-y-4">
            <div>
                <x-form.label for="site_name" :text="__('install.site_name')" :required="true" />
                <x-form.text
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
        <h2 id="admin-account-heading" class="sr-only">{{ __('install.admin_account_information') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install.admin_account_details') }}</legend>
            
            <div>
                <x-form.label for="admin_account_name" :text="__('install.admin_account_name')" :required="true" />
                <x-form.text
                    name="admin_account_name"
                    id="admin_account_name"
                    :value="old('admin_account_name', session('install_data.admin_account_name', ''))"
                    pattern="^[a-zA-Z0-9]+$"
                    minlength="3"
                    maxlength="20"
                    :required="true"
                    :placeholder="__('install.admin_account_name_placeholder')"
                    oninvalid="setCustomValidity('{{ __('install.validation.admin_account_name_required') }}')"
                    oninput="setCustomValidity('')"
                    ariaDescribedby="admin_account_name_help"
                    class="input-full"
                />
                <x-form.help_text :text="__('install.admin_account_name_requirements')" id="admin_account_name_help" />
            </div>

            <div>
                <x-form.label for="admin_display_name" :text="__('install.admin_display_name')" />
                <x-form.text
                    name="admin_display_name"
                    id="admin_display_name"
                    :value="old('admin_display_name', session('install_data.admin_display_name', ''))"
                    maxlength="255"
                    :placeholder="__('install.admin_display_name_placeholder')"
                    ariaDescribedby="admin_display_name_help"
                    class="input-full"
                />
                <x-form.help_text :text="__('install.admin_display_name_requirements')" id="admin_display_name_help" />
            </div>

            <div>
                <x-form.label for="admin_email" :text="__('install.admin_email')" :required="true" />
                <x-form.text
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
        <h2 id="password-heading" class="sr-only">{{ __('install.password_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install.password_setup') }}</legend>
            
            <div>
                <x-form.label for="admin_password" :text="__('install.admin_password')" :required="true" />
                <x-password-tools
                    name="admin_password"
                    id="admin_password"
                    :required="false"
                    :showConfirmation="true"
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
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between mt-6">
        <a href="{{ route('install.index') }}"
           class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install.back') }}
        </a>
        <x-form.button
            type="submit"
            variant="primary"
            :label="__('install.next')"
        />
    </nav>
</form>

<script @cspNonce>
    // パスワード確認欄でコピー＆ペーストを禁止
    document.addEventListener('DOMContentLoaded', function() {
        const confirmInput = document.getElementById("admin_password_confirmation");
        
        if (confirmInput) {
            confirmInput.addEventListener("paste", function(e) {
                e.preventDefault();
                alert("{{ __('install.password_paste_error') }}");
            });

            confirmInput.addEventListener("copy", function(e) {
                e.preventDefault();
            });

            confirmInput.addEventListener("cut", function(e) {
                e.preventDefault();
            });

            confirmInput.addEventListener("contextmenu", function(e) {
                e.preventDefault();
            });
        }
    });
</script>

@endsection