{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
<div class="mx-auto">
<div x-data="{
    captchaEnabled: {{ old('captcha_enabled', $settings['captcha_enabled']) ? 'true' : 'false' }},
    captchaDriver: '{{ old('captcha_driver', $settings['captcha_driver']) }}',
    captchaVersion: '{{ old('captcha_google_version', $settings['captcha_google_version']) }}',
    captchaSiteKey: '{{ old('captcha_site_key', $settings['captcha_site_key']) }}',
    captchaSecretKey: '{{ old('captcha_secret_key', $settings['captcha_secret_key']) }}',
    captchaProjectId: '{{ old('captcha_google_project_id', $settings['captcha_google_project_id']) }}',
    providerKeys: {{ Js::from($settings['provider_keys']) }},
    savedDriver: '{{ $settings['captcha_driver'] }}',
    savedVersion: '{{ $settings['captcha_google_version'] }}',
    savedMinScore: '{{ $settings['captcha_google_min_score'] }}',
    captchaMinScore: '{{ old('captcha_google_min_score', $settings['captcha_google_min_score']) }}',
    savedProjectId: '{{ $settings['captcha_google_project_id'] }}',
    captchaSettingsChanged: false,
    testedSnapshot: null,
    init() {
        this.$watch('captchaDriver', (newDriver, oldDriver) => {
            if (newDriver !== oldDriver) {
                this.switchProviderKeys(newDriver)
            }
        })
        this.$watch('captchaSiteKey', () => this.checkSettingsChanged())
        this.$watch('captchaSecretKey', () => this.checkSettingsChanged())
        this.$watch('captchaVersion', () => this.checkSettingsChanged())
        this.$watch('captchaMinScore', () => this.checkSettingsChanged())
        this.$watch('captchaProjectId', () => this.checkSettingsChanged())
        window.addEventListener('captcha-test-passed', () => this.markTested())
    },
    switchProviderKeys(newDriver) {
        const keys = this.providerKeys[newDriver] || { site_key: '', secret_key: '' }
        this.captchaSiteKey = keys.site_key || ''
        this.captchaSecretKey = keys.secret_key || ''
        this.resetAuthenticationStatus()
        this.captchaSettingsChanged = true
    },
    currentSnapshot() {
        return [
            this.captchaDriver, this.captchaSiteKey, this.captchaSecretKey,
            this.captchaVersion, this.captchaMinScore, this.captchaProjectId,
        ].join('\u001f')
    },
    savedSnapshot() {
        const savedKeys = this.providerKeys[this.savedDriver] || { site_key: '', secret_key: '' }
        return [
            this.savedDriver, savedKeys.site_key || '', savedKeys.secret_key || '',
            this.savedVersion, this.savedMinScore, this.savedProjectId,
        ].join('\u001f')
    },
    markTested() {
        // Values that just passed the widget test; editing any of them afterwards
        // drops the passed status again (the server enforces the same rule).
        this.testedSnapshot = this.currentSnapshot()
    },
    checkSettingsChanged() {
        const baseline = this.testedSnapshot ?? this.savedSnapshot()
        if (this.currentSnapshot() === baseline) {
            return
        }
        this.captchaSettingsChanged = true
        const authInput = document.getElementById('captcha-authentication-result')
        if (authInput && authInput.value === '1') {
            this.resetAuthenticationStatus()
        }
    },
    resetAuthenticationStatus() {
        this.testedSnapshot = null
        const authInput = document.getElementById('captcha-authentication-result')
        if (authInput) {
            authInput.value = '0'
        }
        const successDisplay = document.getElementById('captcha-success-display')
        const requiredNotice = document.getElementById('auth-test-required-notice')
        if (successDisplay) successDisplay.style.display = 'none'
        if (requiredNotice) requiredNotice.style.display = 'block'
        const testResult = document.getElementById('captcha-test-result')
        if (testResult) testResult.style.display = 'none'
        
        // CAPTCHAウィジェットをクリア
        if (typeof clearCaptchaWidget === 'function') {
            clearCaptchaWidget()
        }
    }
}">
    <form id="security-captcha-form" method="POST" action="{{ route('admin.settings.security.captcha.update') }}">
        @csrf
        
        <!-- Hidden field to track CAPTCHA authentication result -->
        <input type="hidden" id="captcha-authentication-result" name="captcha_authentication_result" value="{{ $captchaTestResult ? '1' : '0' }}">
        
        <!-- CAPTCHA設定 -->
        <section>
            <h2>{{ __('admin/settings/security/captcha.title') }}</h2>
            <p>{{ __('admin/settings/security/captcha.description') }}</p>
            
            <!-- CAPTCHA test required notice for enabled CAPTCHA -->
            @if($settings['captcha_enabled'] && !$captchaTestResult)
                <x-ui-message
                    type="warning"
                    :message="__('admin/settings/security/captcha.test_required')"
                />
            @endif
            
            <x-form-toggle
                :label="__('admin/settings/security/captcha.enabled')"
                id="captcha_enabled"
                name="captcha_enabled"
                :checked="old('captcha_enabled', $settings['captcha_enabled'])"
                xModel="captchaEnabled"
            />

            <div class="mt-4 space-y-4" :class="{ 'opacity-50 pointer-events-none': !captchaEnabled }">
                <x-form-label
                    for="captcha_driver"
                    :text="__('admin/settings/security/captcha.driver')"
                />
                <x-form-select
                    :label="__('admin/settings/security/captcha.driver')"
                    id="captcha_driver"
                    name="captcha_driver"
                    :value="old('captcha_driver', $settings['captcha_driver'])"
                    :options="[
                        'turnstile' => 'Cloudflare Turnstile',
                        'google' => 'Google reCAPTCHA (v2/v3)',
                        'google_enterprise' => 'Google reCAPTCHA Enterprise',
                    ]"
                    xModel="captchaDriver"
                    x-bind:disabled="!captchaEnabled"
                    class="input-common input-xl"
                />

                <!-- Common CAPTCHA Settings -->
                <x-form-label
                    for="captcha_site_key"
                    :text="__('admin/settings/security/captcha.site_key')"
                />
                <x-form-text
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
                       x-text="captchaDriver === 'google_enterprise' ? '{{ __("admin/settings/security/captcha.google_enterprise_secret_key") }}' : '{{ __("admin/settings/security/captcha.secret_key") }}'">
                    {{ __('admin/settings/security/captcha.secret_key') }}
                </label>
                <x-form-text
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
                    <x-form-label
                        for="captcha_google_version"
                        :text="__('admin/settings/security/captcha.google_version')"
                    />
                    <x-form-select
                        id="captcha_google_version"
                        name="captcha_google_version"
                        :value="old('captcha_google_version', $settings['captcha_google_version'])"
                        :options="__('admin/settings/security/captcha.version_options')"
                        xModel="captchaVersion"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-xl"
                    />
                </div>

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
                    <x-form-label
                        for="captcha_google_project_id"
                        :text="__('admin/settings/security/captcha.google_project_id')"
                    />
                    <x-form-text
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
                    <x-form-label
                        for="captcha_google_min_score"
                        :text="__('admin/settings/security/captcha.google_min_score')"
                    />
                    <x-form-text
                        id="captcha_google_min_score"
                        name="captcha_google_min_score"
                        :value="old('captcha_google_min_score', $settings['captcha_google_min_score'])"
                        type="number"
                        step="0.1"
                        xModel="captchaMinScore"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-sm"
                    />
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ __('admin/settings/security/captcha.min_score_description') }}
                    </p>
                </div>

                <!-- CAPTCHA認証テストセクション -->
                <div class="mt-6" x-show="captchaEnabled" id="captcha-test-section">
                    <div class="p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('admin/settings/security/captcha.live_validation') }}</h3>
                        
                        <!-- 認証成功時の詳細表示 -->
                        <div id="captcha-success-display" class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-3" style="{{ $captchaTestResult ? '' : 'display: none;' }}">
                            <div class="flex items-center text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span class="font-semibold">{{ __('admin/settings/security/captcha.validation_success') }}</span>
                            </div>
                            @if($captchaTestDetails && isset($captchaTestDetails['tested_at']))
                                <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ __('admin/settings/security/captcha.tested_at') }}: 
                                    {{ \Carbon\Carbon::parse($captchaTestDetails['tested_at'])->format('Y-m-d H:i:s') }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- 認証テスト必要通知 -->
                        <div id="auth-test-required-notice" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-3" style="{{ $captchaTestResult ? 'display: none;' : '' }}">
                            <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span class="font-medium">{{ __('admin/settings/security/captcha.test_required_title') }}</span>
                            </div>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                {{ __('admin/settings/security/captcha.test_required_description') }}
                            </p>
                        </div>
                        
                        <!-- CAPTCHA Widget Container -->
                        <div id="captcha-widget-container" class="mb-4"></div>
                        
                        <!-- Test Result Message (動的に表示) -->
                        <div id="captcha-test-result" class="mb-4 p-3 border rounded-lg" style="display: none;">
                            <div class="flex items-center">
                                <i id="captcha-test-icon" class="mr-3 text-xl"></i>
                                <div>
                                    <h4 id="captcha-test-title" class="font-semibold"></h4>
                                    <p id="captcha-test-message" class="text-sm mt-1"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Button -->
                        <button type="button" 
                                id="captcha-validate-button"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed mb-3"
                                @click="validateCaptchaWidget()"
                                :disabled="!captchaEnabled || !captchaDriver"
                                x-text="captchaSettingsChanged ? '{{ __('admin/settings/security/captcha.validate_button') }}' : '{{ $captchaTestResult ? __('admin/settings/security/captcha.revalidate_button') : __('admin/settings/security/captcha.validate_button') }}'">
                        </button>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            {{ __('admin/settings/security/captcha.live_validation_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- フォームごとのCAPTCHA設定 -->
        <section class="mt-8">
            <h2>{{ __('admin/settings/security/captcha.form_settings_title') }}</h2>
            <p>{{ __('admin/settings/security/captcha.form_settings_description') }}</p>

            @foreach($formsByCategory as $category => $forms)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-4">{{ __("admin/settings/security/captcha.categories.$category") }}</h3>
                    
                    @foreach($forms as $formKey => $form)
                        <fieldset class="mb-4 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <x-form-toggle
                                        name="forms[{{ $formKey }}]"
                                        :label="__($form['name'])"
                                        :checked="old('forms.' . $formKey, $enabledForms[$formKey] ?? false)"
                                        x-bind:disabled="!captchaEnabled"
                                    />
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">{{ __('admin/settings/security/captcha.route') }}:</span> 
                                        <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">{{ $form['route'] }}</code>
                                        @if(isset($form['plugin']))
                                            <span class="ml-3 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                                {{ __('admin/settings/security/captcha.plugin') }}: {{ $form['plugin'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @endforeach

            @if(empty($formsByCategory))
                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/security/captcha.no_forms_available') }}
                    </p>
                </div>
            @endif
        </section>

    </form>
</div>
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
        form="security-captcha-form"
    />
@endsection

@push('scripts')
<div id="captcha-config"
     data-validate-url="{{ route('admin.settings.security.captcha.validate-widget') }}"
     data-msg-required-fields-empty="{{ __('admin/settings/security/captcha.required_fields_empty') }}"
     data-msg-validation-error="{{ __('admin/settings/security/captcha.validation_error') }}"
     data-msg-success-title="{{ __('admin/settings/security/captcha.authentication_success_title') }}"
     data-msg-failed-title="{{ __('admin/settings/security/captcha.authentication_failed_title') }}"
     style="display:none;"></div>
@endpush
