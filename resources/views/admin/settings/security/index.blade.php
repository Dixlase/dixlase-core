{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')

<div x-data="{
    enableAllowedIPs: {{ $settings['enable_allowed_admin_ips'] ? 'true' : 'false' }},
    blockedAdminIps: {{ $settings['enable_blocked_admin_ips'] ? 'true' : 'false' }},
    captchaEnabled: {{ $settings['captcha_enabled'] ? 'true' : 'false' }},
    captchaDriver: '{{ $settings['captcha_driver'] }}',
    captchaVersion: '{{ $settings['captcha_google_version'] }}'
}">
    <form id="security-settings-form" method="POST" action="{{ route('admin.settings.security.update') }}">
        @csrf
        @method('POST')

        <!-- 基本セキュリティ設定 -->
        <div>
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.security.basic_security_settings') }}</h2>
            
            <div>
                @include('components::form.label', [
                    'for' => 'admin_url',
                    'text' => __('admin.settings.security.admin_url'),
                ])
                @include('components::form.text', [
                    'id' => 'admin_url',
                    'name' => 'admin_url',
                    'value' => old('admin_url', $settings['admin_url']),
                    'required' => true,
                ])
            </div>

            <div class="mt-4">
                @include('components::form.checkbox', [
                    'label' => __('admin.settings.security.force_ssl'),
                    'id' => 'force_ssl',
                    'name' => 'force_ssl',
                    'value' => old('force_ssl', $settings['force_ssl']),
                ])
            </div>
        </div>

        <!-- IPアクセス制御設定 -->
        <div class="mt-8 border-t pt-6">
            <h2 class="{{ config('admin.appearance_class.heading.h2') }}">{{ __('admin.settings.security.ip_access_control') }}</h2>

            @include('components::form.checkbox', [
                'label' => __('admin.settings.security.enable_allowed_admin_ips'),
                'id' => 'enable_allowed_admin_ips',
                'name' => 'enable_allowed_admin_ips',
                'value' => old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']),
                'xModel' => 'enableAllowedIPs' // Alpine.jsに状態をバインド
            ])

            @include('components::form.textarea', [
                'id' => 'allowed_admin_ips',
                'name' => 'allowed_admin_ips',
                'value' => $settings['allowed_admin_ips'],
                'rows' => 10,
                'placeholder' => '',
                'class' => '',
                'readonly' => !$settings['enable_allowed_admin_ips'], // 初期状態
                'xBindReadonly' => '!enableAllowedIPs', // Alpine.jsでreadonlyを動的に管理
                'xBindClass' => "{ 'bg-gray-100': !enableAllowedIPs, 'bg-white': enableAllowedIPs }", //readonlyの有無でクラスを切り替え
            ])

            @include('components::form.checkbox', [
                'label' => __('admin.settings.security.enable_blocked_admin_ips'),
                'id' => 'enable_blocked_admin_ips',
                'name' => 'enable_blocked_admin_ips',
                'value' => old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']),
                'xModel' => 'blockedAdminIps' // Alpine.jsに状態をバインド
            ])

            @include('components::form.textarea', [
                'id' => 'blocked_admin_ips',
                'name' => 'blocked_admin_ips',
                'value' => $settings['blocked_admin_ips'],
                'rows' => 10,
                'placeholder' => '',
                'required' => false,
                'class' => '',
                'readonly' => !$settings['blocked_admin_ips'], //初期状態
                'xBindReadonly' => '!blockedAdminIps', // Alpine.jsでreadonlyを動的に管理
                'xBindClass' => "{ 'bg-gray-100': !enableAllowedIPs, 'bg-white': blockedAdminIps }", //readonlyの有無でクラスを切り替え

            ])
        </div>

        <!-- reCAPTCHA設定 -->
        <div class="mt-8 pt-6">
            <h2 class="{{ config('admin.appearance_class.heading.h2') }}">{{ __('admin.settings.security.recaptcha_settings') }}</h2>
            
            @include('components::form.checkbox', [
                'label' => __('admin.settings.security.captcha_enabled'),
                'id' => 'captcha_enabled',
                'name' => 'captcha_enabled',
                'value' => old('captcha_enabled', $settings['captcha_enabled']),
                'xModel' => 'captchaEnabled'
            ])

            <div class="mt-4 space-y-4">
                @include('components::form.label', [
                    'for' => 'captcha_driver',
                    'text' => __('admin.settings.security.captcha_driver'),
                ])
                @include('components::form.select', [
                    'id' => 'captcha_driver',
                    'name' => 'captcha_driver',
                    'value' => old('captcha_driver', $settings['captcha_driver']),
                    'options' => [
                        'google' => 'Google reCAPTCHA',
                        'turnstile' => 'Cloudflare Turnstile',
                    ],
                    'xModel' => 'captchaDriver'
                ])

                <!-- Google reCAPTCHA Settings -->
                <div x-show="captchaDriver === 'google'">

                    @include('components::form.label', [
                        'for' => 'captcha_google_version',
                        'text' => __('admin.settings.security.captcha_google_version'),
                    ])
                    @include('components::form.select', [
                        'id' => 'captcha_google_version',
                        'name' => 'captcha_google_version',
                        'value' => old('captcha_google_version', $settings['captcha_google_version']),
                        'options' => __('admin.settings.security.captcha_version_options'),
                        'xModel' => 'captchaVersion'
                    ])

                    <div x-show="captchaVersion === 'v3'" class="mt-4">
                        @include('components::form.label', [
                            'for' => 'captcha_google_min_score',
                            'text' => __('admin.settings.security.captcha_google_min_score'),
                        ])
                        @include('components::form.text', [
                            'id' => 'captcha_google_min_score',
                            'name' => 'captcha_google_min_score',
                            'value' => old('captcha_google_min_score', $settings['captcha_google_min_score']),
                            'placeholder' => '0.5',
                            'type' => 'number',
                            'step' => '0.1',
                            'min' => '0',
                            'max' => '1',
                        ])
                        <p class="text-sm text-gray-200 mt-1">
                            {{ __('admin.settings.security.captcha_min_score_description') }}
                        </p>
                    </div>
                    
                    @include('components::form.label', [
                        'for' => 'captcha_google_site_key',
                        'text' => __('admin.settings.security.captcha_google_site_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_site_key',
                        'name' => 'captcha_google_site_key',
                        'value' => old('captcha_google_site_key', $settings['captcha_google_site_key']),
                        'placeholder' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
                        'type' => 'password',
                    ])

                    @include('components::form.label', [
                        'for' => 'captcha_google_secret_key',
                        'text' => __('admin.settings.security.captcha_google_secret_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_secret_key',
                        'name' => 'captcha_google_secret_key',
                        'value' => old('captcha_google_secret_key', $settings['captcha_google_secret_key']),
                        'placeholder' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
                        'type' => 'password',
                    ])

                    
                </div>

                <!-- Cloudflare Turnstile Settings -->
                <div x-show="captchaDriver === 'turnstile'">
                    @include('components::form.label', [
                        'for' => 'captcha_turnstile_site_key',
                        'text' => __('admin.settings.security.captcha_turnstile_site_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_turnstile_site_key',
                        'name' => 'captcha_turnstile_site_key',
                        'value' => old('captcha_turnstile_site_key', $settings['captcha_turnstile_site_key']),
                        'placeholder' => '0x4AAAAAAABkMYinukE_XyJO',
                        'type' => 'password',
                    ])

                    @include('components::form.label', [
                        'for' => 'captcha_turnstile_secret_key',
                        'text' => __('admin.settings.security.captcha_turnstile_secret_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_turnstile_secret_key',
                        'name' => 'captcha_turnstile_secret_key',
                        'value' => old('captcha_turnstile_secret_key', $settings['captcha_turnstile_secret_key']),
                        'placeholder' => '0x4AAAAAAABkMYinukE_XyJN',
                        'type' => 'password',
                    ])
                </div>

                <div class="mt-6">
                    <h4 class="text-md font-medium mb-3">{{ __('admin.settings.security.captcha_form_settings') }}</h4>
                    <div class="space-y-2">
                        @foreach($captchaFormSettings as $formSetting)
                            @include('components::form.checkbox', [
                                'label' => __('admin.settings.security.captcha_forms.' . $formSetting->key),
                                'id' => 'captcha_form_' . $formSetting->key,
                                'name' => 'captcha_form_' . $formSetting->key,
                                'value' => old('captcha_form_' . $formSetting->key, $formSetting->enabled),
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>





    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleInput(checkbox, textarea) {
        textarea.disabled = !checkbox.checked;
    }

    document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        const textarea = checkbox.parentElement.nextElementSibling;
        toggleInput(checkbox, textarea);

        checkbox.addEventListener('change', function() {
            toggleInput(checkbox, textarea);
        });
    });
});
</script>

@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.security.save_button'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.security.save_confirmation_title'),
        'message' => __('admin.settings.security.save_confirmation_message'),
        'confirm_label' => __('admin.settings.security.save_button'),
        'cancel_label' => __('admin.settings.security.back_button'),
        'form' => 'security-settings-form',
    ])
@endsection
