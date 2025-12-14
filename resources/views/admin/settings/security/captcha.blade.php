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
<div x-data="{
    captchaEnabled: {{ old('captcha_enabled', $settings['captcha_enabled']) ? 'true' : 'false' }},
    captchaDriver: '{{ old('captcha_driver', $settings['captcha_driver']) }}',
    captchaVersion: '{{ old('captcha_google_version', $settings['captcha_google_version']) }}',
    captchaSiteKey: '{{ old('captcha_site_key', $settings['captcha_site_key']) }}',
    captchaSecretKey: '{{ old('captcha_secret_key', $settings['captcha_secret_key']) }}',
    captchaProjectId: '{{ old('captcha_google_project_id', $settings['captcha_google_project_id']) }}'
}">
    <form id="security-captcha-form" method="POST" action="{{ route('admin.settings.security.captcha.update') }}">
        @csrf
        
        <!-- Hidden field to track CAPTCHA authentication result -->
        <input type="hidden" id="captcha-authentication-result" name="captcha_authentication_result" value="{{ $captchaTestResult ? '1' : '0' }}">
        
        <!-- CAPTCHA設定 -->
        <section>
            <h2>{{ __('admin.settings.security.captcha.title') }}</h2>
            <p>{{ __('admin.settings.security.captcha.description') }}</p>
            
            <!-- CAPTCHA test required notice for enabled CAPTCHA -->
            @if($settings['captcha_enabled'] && !$captchaTestResult)
                <x-message
                    type="warning"
                    :message="__('admin.settings.security.captcha.test_required')"
                />
            @endif
            
            <x-form.toggle
                :label="__('admin.settings.security.captcha.enabled')"
                id="captcha_enabled"
                name="captcha_enabled"
                :checked="old('captcha_enabled', $settings['captcha_enabled'])"
                xModel="captchaEnabled"
            />

            <div class="mt-4 space-y-4" :class="{ 'opacity-50 pointer-events-none': !captchaEnabled }">
                <x-form.label
                    for="captcha_driver"
                    :text="__('admin.settings.security.captcha.driver')"
                />
                <x-form.select
                    :label="__('admin.settings.security.captcha.driver')"
                    id="captcha_driver"
                    name="captcha_driver"
                    :value="old('captcha_driver', $settings['captcha_driver'])"
                    :options="[
                        'google' => 'Google reCAPTCHA (v2/v3)',
                        'google_enterprise' => 'Google reCAPTCHA Enterprise',
                        'turnstile' => 'Cloudflare Turnstile'
                    ]"
                    xModel="captchaDriver"
                    x-bind:disabled="!captchaEnabled"
                    class="input-common input-xl"
                />

                <!-- Common CAPTCHA Settings -->
                <x-form.label
                    for="captcha_site_key"
                    :text="__('admin.settings.security.captcha.site_key')"
                />
                <x-form.text
                    id="captcha_site_key"
                    name="captcha_site_key"
                    :value="old('captcha_site_key', $settings['captcha_site_key'] ?? '')"
                    type="password"
                    xModel="captchaSiteKey"
                    autocomplete="off"
                    x-bind:disabled="!captchaEnabled"
                    class="input-common input-xl"
                />

                <label for="captcha_secret_key" class="form-label text-lg" 
                       x-text="captchaDriver === 'google_enterprise' ? '{{ __("admin.settings.security.captcha.google_enterprise_secret_key") }}' : '{{ __("admin.settings.security.captcha.secret_key") }}'">
                    {{ __('admin.settings.security.captcha.secret_key') }}
                </label>
                <x-form.text
                    id="captcha_secret_key"
                    name="captcha_secret_key"
                    :value="old('captcha_secret_key', $settings['captcha_secret_key'] ?? '')"
                    type="password"
                    xModel="captchaSecretKey"
                    autocomplete="off"
                    x-bind:disabled="!captchaEnabled"
                    class="input-common input-xl"
                />

                <!-- Google reCAPTCHA Settings -->
                <div x-show="captchaDriver === 'google'">
                    <x-form.label
                        for="captcha_google_version"
                        :text="__('admin.settings.security.captcha.google_version')"
                    />
                    <x-form.select
                        id="captcha_google_version"
                        name="captcha_google_version"
                        :value="old('captcha_google_version', $settings['captcha_google_version'])"
                        :options="__('admin.settings.security.captcha.version_options')"
                        xModel="captchaVersion"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-xl"
                    />
                </div>

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
                    <x-form.label
                        for="captcha_google_project_id"
                        :text="__('admin.settings.security.captcha.google_project_id')"
                    />
                    <x-form.text
                        id="captcha_google_project_id"
                        name="captcha_google_project_id"
                        :value="old('captcha_google_project_id', $settings['captcha_google_project_id'])"
                        placeholder="your-gcp-project-id"
                        xModel="captchaProjectId"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-xl"
                    />
                </div>

                <!-- Min Score for Google v3 and Enterprise -->
                <div x-show="(captchaDriver === 'google' && captchaVersion === 'v3') || captchaDriver === 'google_enterprise'" class="mt-4">
                    <x-form.label
                        for="captcha_google_min_score"
                        :text="__('admin.settings.security.captcha.google_min_score')"
                    />
                    <x-form.text
                        id="captcha_google_min_score"
                        name="captcha_google_min_score"
                        :value="old('captcha_google_min_score', $settings['captcha_google_min_score'])"
                        type="number"
                        step="0.1"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-sm"
                    />
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ __('admin.settings.security.captcha.min_score_description') }}
                    </p>
                </div>

                <!-- CAPTCHA認証テストセクション -->
                <div class="mt-6" x-show="captchaEnabled" id="captcha-test-section">
                    <div class="p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('admin.settings.security.captcha.live_validation') }}</h3>
                        
                        @if($captchaTestResult)
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div class="flex items-center text-green-600 dark:text-green-400">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <span>{{ __('admin.settings.security.captcha.validation_success') }}</span>
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-3">
                                <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <span>{{ __('admin.settings.security.captcha.test_required_title') }}</span>
                                </div>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                    {{ __('admin.settings.security.captcha.test_required_description') }}
                                </p>
                            </div>
                            
                            <!-- CAPTCHA Widget Container -->
                            <div id="captcha-widget-container" class="mb-4"></div>
                            
                            <!-- Test Result Message -->
                            <div id="captcha-test-result" class="mb-4 p-3 border rounded-lg" style="display: none;">
                                <div class="flex items-center">
                                    <i id="captcha-test-icon" class="mr-3"></i>
                                    <div>
                                        <h4 id="captcha-test-title" class="font-semibold"></h4>
                                        <p id="captcha-test-message" class="text-sm mt-1"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Test Button -->
                            <button type="button" 
                                    id="captcha-validate-button"
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                    onclick="validateCaptchaWidget()"
                                    x-show="captchaEnabled && captchaDriver && captchaSiteKey && captchaSecretKey"
                                    :disabled="!captchaEnabled || !captchaDriver || !captchaSiteKey || !captchaSecretKey">
                                {{ __('admin.settings.security.captcha.validate_button') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </section>

    </form>
</div>
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
        form="security-captcha-form"
    />
@endsection

@push('scripts')
<script>
// CAPTCHA validation functions
function validateCaptchaWidget() {
    const driver = document.getElementById('captcha_driver').value;
    const siteKey = document.getElementById('captcha_site_key').value;
    const secretKey = document.getElementById('captcha_secret_key').value;
    
    if (!driver || !siteKey || !secretKey) {
        showTestResult('error', '{{ __("admin.settings.security.captcha.required_fields_empty") }}');
        return;
    }
    
    // Load and execute CAPTCHA based on driver
    if (driver === 'google') {
        loadGoogleRecaptcha(siteKey);
    } else if (driver === 'turnstile') {
        loadTurnstile(siteKey);
    } else if (driver === 'google_enterprise') {
        loadGoogleEnterprise(siteKey);
    }
}

function loadGoogleRecaptcha(siteKey) {
    const version = document.getElementById('captcha_google_version').value;
    const container = document.getElementById('captcha-widget-container');
    
    // Remove existing scripts
    const existingScript = document.getElementById('recaptcha-script');
    if (existingScript) existingScript.remove();
    
    const script = document.createElement('script');
    script.id = 'recaptcha-script';
    
    if (version === 'v3') {
        script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.onload = () => {
            grecaptcha.ready(() => {
                grecaptcha.execute(siteKey, {action: 'test'}).then(token => {
                    validateToken(token);
                });
            });
        };
    } else {
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.onload = () => {
            container.innerHTML = '<div id="recaptcha-widget"></div>';
            grecaptcha.ready(() => {
                grecaptcha.render('recaptcha-widget', {
                    sitekey: siteKey,
                    callback: validateToken
                });
            });
        };
    }
    
    document.head.appendChild(script);
}

function loadTurnstile(siteKey) {
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = '<div class="cf-turnstile" data-sitekey="' + siteKey + '" data-callback="validateToken"></div>';
    
    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    document.head.appendChild(script);
}

function loadGoogleEnterprise(siteKey) {
    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/enterprise.js?render=${siteKey}`;
    script.onload = () => {
        grecaptcha.enterprise.ready(() => {
            grecaptcha.enterprise.execute(siteKey, {action: 'test'}).then(token => {
                validateToken(token);
            });
        });
    };
    document.head.appendChild(script);
}

function validateToken(token) {
    fetch('{{ route("admin.settings.security.captcha.validate-widget") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            token: token,
            driver: document.getElementById('captcha_driver').value,
            secret_key: document.getElementById('captcha_secret_key').value,
            min_score: document.getElementById('captcha_google_min_score')?.value || '0.5'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTestResult('success', data.message);
            document.getElementById('captcha-authentication-result').value = '1';
        } else {
            showTestResult('error', data.message);
        }
    })
    .catch(error => {
        showTestResult('error', '{{ __("admin.settings.security.captcha.validation_error") }}');
    });
}

function showTestResult(type, message) {
    const resultDiv = document.getElementById('captcha-test-result');
    const icon = document.getElementById('captcha-test-icon');
    const title = document.getElementById('captcha-test-title');
    const msg = document.getElementById('captcha-test-message');
    
    resultDiv.style.display = 'block';
    
    if (type === 'success') {
        resultDiv.className = 'mb-4 p-3 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
        icon.className = 'fas fa-check-circle text-green-500 text-xl mr-3';
        title.className = 'font-semibold text-green-700 dark:text-green-300';
        title.textContent = '{{ __("admin.settings.security.captcha.authentication_success_title") }}';
    } else {
        resultDiv.className = 'mb-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        icon.className = 'fas fa-times-circle text-red-500 text-xl mr-3';
        title.className = 'font-semibold text-red-700 dark:text-red-300';
        title.textContent = '{{ __("admin.settings.security.captcha.authentication_failed_title") }}';
    }
    
    msg.textContent = message;
}
</script>
@endpush
