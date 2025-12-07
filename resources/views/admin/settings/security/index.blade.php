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
    enableAllowedIPs: {{ old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']) ? 'true' : 'false' }},
    blockedAdminIps: {{ old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']) ? 'true' : 'false' }},
    enableAllowedFrontIPs: {{ old('enable_allowed_front_ips', $settings['enable_allowed_front_ips']) ? 'true' : 'false' }},
    enableBlockedFrontIps: {{ old('enable_blocked_front_ips', $settings['enable_blocked_front_ips']) ? 'true' : 'false' }},
    captchaEnabled: {{ old('captcha_enabled', $settings['captcha_enabled']) ? 'true' : 'false' }},
    captchaEnabledSaved: {{ $settings['captcha_enabled'] ? 'true' : 'false' }},
    captchaDriver: '{{ old('captcha_driver', $settings['captcha_driver']) }}',
    captchaVersion: '{{ old('captcha_google_version', $settings['captcha_google_version']) }}',
    captchaSiteKey: '{{ old('captcha_site_key', $settings['captcha_site_key']) }}',
    captchaSecretKey: '{{ old('captcha_secret_key', $settings['captcha_secret_key']) }}',
    captchaProjectId: '{{ old('captcha_google_project_id', $settings['captcha_google_project_id']) }}',
    captchaSettingsChanged: false,
    $watch: {
        captchaVersion() {
            console.log('DEBUG: Alpine.js captchaVersion watcher triggered:', this.captchaVersion);
        },
        captchaSettingsChanged() {
            console.log('DEBUG: Alpine.js captchaSettingsChanged watcher triggered:', this.captchaSettingsChanged);
        }
    }
}">
    <form id="security-settings-form" method="POST" action="{{ route('admin.settings.security.update') }}" onsubmit="debugFormSubmission(event)">
        @csrf
        @method('POST')
        
        <!-- Hidden field to track CAPTCHA authentication result -->
        <input type="hidden" id="captcha-authentication-result" name="captcha_authentication_result" value="{{ $captchaTestResult ? '1' : '0' }}">
        <input type="hidden" id="captcha_validation_token" name="captcha_validation_token" value="{{ session('captcha_just_saved') ? 'saved_token' : '' }}">
        
        <!-- システムエラー通知設定 -->
        <section x-data="{ notificationEnabled: {{ ($settings['notification_enabled'] ?? 0) ? 'true' : 'false' }} }">
            <h2>{{ __('admin.settings.security.error_notification_settings') }}</h2>
            <p>{{ __('admin.settings.security.error_notification_settings_description') }}</p>

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4">
                    <x-message
                        type="warning"
                        :message="__('admin.settings.security.error_notification_mail_test_required', ['url' => route('admin.settings.base')])"
                    />
                </div>
            @endif

            <!-- エラー通知機能の有効/無効 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.notification_enabled') }}</legend>
                
                <x-form.hidden
                    name="notification_enabled"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.notification_enabled')"
                    id="notification_enabled"
                    name="notification_enabled"
                    :checked="$settings['notification_enabled'] ?? false"
                    xModel="notificationEnabled"
                />
                
                <p>{{ __('admin.settings.security.notification_enabled_help') }}</p>
            </fieldset>

            <!-- 通知するログレベル -->
            <fieldset>
                <legend>{{ __('admin.settings.security.notification_log_levels') }}</legend>
                
                <div class="my-3" :class="{ 'opacity-50': !notificationEnabled }">
                    @php
                        $logLevelOptions = [];
                        foreach (\App\Enums\LogLevel::getNotificationLevels() as $level) {
                            $levelString = \App\Enums\LogLevel::from($level)->toString();
                            $logLevelOptions[$level] = 'admin.settings.security.log_levels.' . $levelString;
                        }
                    @endphp
                    
                    <x-form.toggle-group
                        name="notification_log_levels"
                        :options="$logLevelOptions"
                        :values="$settings['notification_log_levels'] ?? \App\Enums\LogLevel::getDefaultNotificationLevels()"
                        xBindDisabled="!notificationEnabled"
                        flexDirection="col"
                    />
                </div>
                
                <p>{{ __('admin.settings.security.notification_log_levels_help') }}</p>
            </fieldset>
        </section>


        <!-- セッション管理設定 -->
        <section>
            <h2>{{ __('admin.settings.security.session_management') }}</h2>
            <p>{{ __('admin.settings.security.session_management_description') }}</p>

            <!-- セッションドライバー -->
            <fieldset>
                <legend>{{ __('admin.settings.security.session_driver') }}</legend>
                                    
                <x-form.select
                    id="session_driver"
                    name="session_driver"
                    :options="[
                        'file' => __('admin.settings.security.session_driver_file'),
                        'database' => __('admin.settings.security.session_driver_database'),
                        'redis' => __('admin.settings.security.session_driver_redis'),
                        'memcached' => __('admin.settings.security.session_driver_memcached'),
                        'cookie' => __('admin.settings.security.session_driver_cookie'),
                        'array' => __('admin.settings.security.session_driver_array'),
                    ]"
                    :value="old('session_driver', $settings['session_driver'])"
                    class="input-common input-xl"
                />
                
                <p>{{ __('admin.settings.security.session_driver_help') }}</p>
            </fieldset>

            <!-- セッション暗号化 -->
            <fieldset>
                <x-form.toggle
                    name="session_encrypt"
                    :label="__('admin.settings.security.session_encrypt')"
                    :checked="old('session_encrypt', $settings['session_encrypt'])"
                />
                <p class="mt-2">{{ __('admin.settings.security.session_encrypt_help') }}</p>
            </fieldset>

            <!-- デフォルトセッション有効時間 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.session_lifetime') }}</legend>
                
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
                
                <p id="session_lifetime_help">{{ __('admin.settings.security.session_lifetime_help') }}</p>
            </fieldset>
        </section>

        <!-- パスワードセキュリティ設定 -->
        <section>
            <h2>{{ __('admin.settings.security.password_security_settings') }}</h2>
            <p>{{ __('admin.settings.security.password_security_description') }}</p>

            <!-- パスワード漏洩チェック -->
            <fieldset>
                <legend>{{ __('admin.settings.security.pwned_password_check') }}</legend>
                <p>{{ __('admin.settings.security.pwned_password_check_help') }}</p>
                
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
                            <p>{{ __('admin.settings.security.pwned_password_api_info') }}</p>
                        </div>
                    </div>
                </div>
            </fieldset>
        </section>

        <!-- CAPTCHA設定 -->
        <section>
            <h2>{{ __('admin.settings.security.recaptcha_settings') }}</h2>
            <p>{{ __('admin.settings.security.recaptcha_settings_description') }}</p>
            
            <!-- CAPTCHA test required notice for enabled CAPTCHA -->
            @if($settings['captcha_enabled'] && !$captchaTestResult)
                <x-message
                    type="warning"
                    :message="__('admin.settings.security.captcha_test_required')"
                />
            @endif
            <x-form.toggle
                :label="__('admin.settings.security.captcha_enabled')"
                id="captcha_enabled"
                name="captcha_enabled"
                xModel="captchaEnabled"
            />
            

            <div class="mt-4 space-y-4" :class="{ 'opacity-50 pointer-events-none': !captchaEnabled }">
                <x-form.label
                    for="captcha_driver"
                    :text="__('admin.settings.security.captcha_driver')"
                />
                <x-form.select
                    :label="__('admin.settings.security.captcha_driver')"
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
                    :text="__('admin.settings.security.captcha_site_key')"
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
                       x-text="captchaDriver === 'google_enterprise' ? '{{ __("admin.settings.security.captcha_google_enterprise_secret_key") }}' : '{{ __("admin.settings.security.captcha_secret_key") }}'">
                    {{ __('admin.settings.security.captcha_secret_key') }}
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
                        :text="__('admin.settings.security.captcha_google_version')"
                    />
                    <x-form.select
                        id="captcha_google_version"
                        name="captcha_google_version"
                        :value="old('captcha_google_version', $settings['captcha_google_version'])"
                        :options="__('admin.settings.security.captcha_version_options')"
                        xModel="captchaVersion"
                        x-bind:disabled="!captchaEnabled"
                        class="input-common input-xl"
                    />
                </div>

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
                    <x-form.label
                        for="captcha_google_project_id"
                        :text="__('admin.settings.security.captcha_google_project_id')"
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
                        :text="__('admin.settings.security.captcha_google_min_score')"
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
                    <p class="text-sm text-gray-200 mt-1">
                        {{ __('admin.settings.security.captcha_min_score_description') }}
                    </p>
                </div>

                <!-- CAPTCHA認証テストセクション -->
                <div class="mt-6" x-show="captchaEnabled" id="captcha-test-section">
                    <!-- CAPTCHA Test Section (Only shown when CAPTCHA is enabled) -->
                    <div class="mt-4 p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">

                        <!-- CAPTCHA Test Success Display (from DB) -->
                        
                        <!-- CAPTCHA Widget Container -->
                        <div id="captcha-widget-container" class="mb-4">
                            <!-- Dynamic CAPTCHA widget will be loaded here -->
                        </div>
                        
                        
                        <!-- Authentication Test Required Notice -->
                        <div id="auth-test-required-notice" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span class="text-sm font-medium">{{ __('admin.settings.security.captcha_test_required_title') }}</span>
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                {{ __('admin.settings.security.captcha_test_required_description') }}
                            </div>
                            <div class="mt-2 pt-2 border-t border-yellow-200 dark:border-yellow-700">
                                <div class="flex items-center text-xs">
                                    <i class="fas fa-external-link-alt mr-1 text-yellow-500"></i>
                                    <span class="text-yellow-600 dark:text-yellow-400 mr-2">{{ __('admin.settings.security.captcha_provider_setup_link') }}:</span>
                                    <a id="provider-setup-link" href="#" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        <span id="provider-name">CAPTCHA Provider</span>{{ __('admin.settings.security.captcha_provider_settings_check') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CAPTCHA Test Result Message (Unified) -->
                        <div id="captcha-test-result" class="mb-4 p-3 border rounded-lg" style="display: none;">
                            <div class="flex items-center">
                                <i id="captcha-test-icon" class="mr-3"></i>
                                <div>
                                    <h4 id="captcha-test-title" class="font-semibold"></h4>
                                    <p id="captcha-test-message" class="text-sm mt-1"></p>
                                    <p id="captcha-test-hint" class="text-sm mt-2" style="display: none;"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Required Notice -->
                        <div id="save-required-notice" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded" style="display: none;">
                            <div class="flex items-center text-blue-600 dark:text-blue-400">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span class="text-sm font-medium">設定を保存してください</span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                必須項目をすべて入力し、設定を保存後に認証テストを実行してください。
                            </div>
                        </div>
                        
                        <!-- Test Button -->
                        <button type="button" 
                                id="captcha-validate-button"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                onclick="validateCaptchaWidget()"
                                x-show="captchaEnabled && captchaDriver && captchaSiteKey && captchaSecretKey"
                                :disabled="!captchaEnabled || !captchaDriver || !captchaSiteKey || !captchaSecretKey">
                            {{ __('admin.settings.security.captcha_validate_button') }}
                        </button>
                    </div>

                </div>
            </div>
        </section>


        <!-- IPアクセス制御設定 -->
        <section>
            <h2>{{ __('admin.settings.security.ip_access_control') }}</h2>
            <p class="mb-2">{{ __('admin.settings.security.ip_access_control_description') }}</p>
            <!-- 管理画面IP制御 -->
            <section>
                <h3>{{ __('admin.settings.security.admin_ip_access_control') }}</h3>
                <!-- 許可IP設定 -->
                <fieldset>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.enable_allowed_admin_ips')"
                        id="enable_allowed_admin_ips"
                        name="enable_allowed_admin_ips"
                        :checked="old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips'])"
                        xModel="enableAllowedIPs"
                    />

                    <div :class="{ 'opacity-50': !enableAllowedIPs }">
                        <x-form.label
                            for="allowed_admin_ips"
                            :text="__('admin.settings.security.allowed_admin_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form.textarea
                            id="allowed_admin_ips"
                            name="allowed_admin_ips"
                            :value="$settings['allowed_admin_ips']"
                            :rows="8"
                            :placeholder="__('admin.settings.security.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableAllowedIPs"
                        />
                        
                        <p class="mb-3">{{ __('admin.settings.security.admin_ip_help') }}</p>
                    </div>
                </fieldset>

                <!-- ブロックIP設定 -->
                <fieldset>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.enable_blocked_admin_ips')"
                        id="enable_blocked_admin_ips"
                        name="enable_blocked_admin_ips"
                        :checked="old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips'])"
                        xModel="blockedAdminIps"
                    />

                    <div :class="{ 'opacity-50': !blockedAdminIps }">
                        <x-form.label
                            for="blocked_admin_ips"
                            :text="__('admin.settings.security.blocked_admin_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form.textarea
                            id="blocked_admin_ips"
                            name="blocked_admin_ips"
                            :value="$settings['blocked_admin_ips']"
                            :rows="8"
                            :placeholder="__('admin.settings.security.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!blockedAdminIps"
                        />
                        
                        <p>{{ __('admin.settings.security.admin_ip_help') }}</p>
                    </div>
                </fieldset>
            </section>

            <!-- フロントエンドIP制御 -->
            <section>
                <h3>{{ __('admin.settings.security.front_ip_access_control') }}</h3>
                <!-- 許可IP設定 -->
                <fieldset>

                    <x-form.toggle
                        :label="__('admin.settings.security.enable_allowed_front_ips')"
                        id="enable_allowed_front_ips"
                        name="enable_allowed_front_ips"
                        :checked="old('enable_allowed_front_ips', $settings['enable_allowed_front_ips'])"
                        xModel="enableAllowedFrontIPs"
                    />

                    <div :class="{ 'opacity-50': !enableAllowedFrontIPs }">
                        <x-form.label
                            for="allowed_front_ips"
                            :text="__('admin.settings.security.allowed_front_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form.textarea
                            id="allowed_front_ips"
                            name="allowed_front_ips"
                            :value="$settings['allowed_front_ips']"
                            :rows="8"
                            :placeholder="__('admin.settings.security.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableAllowedFrontIPs"
                        />
                        
                        <p class="mb-3">{{ __('admin.settings.security.front_ip_help') }}</p>
                    </div>
                </fieldset>

                <!-- ブロックIP設定 -->
                <fieldset>

                    <x-form.toggle
                        :label="__('admin.settings.security.enable_blocked_front_ips')"
                        id="enable_blocked_front_ips"
                        name="enable_blocked_front_ips"
                        :checked="old('enable_blocked_front_ips', $settings['enable_blocked_front_ips'])"
                        xModel="enableBlockedFrontIps"
                    />

                    <div :class="{ 'opacity-50': !enableBlockedFrontIps }">
                        <x-form.label
                            for="blocked_front_ips"
                            :text="__('admin.settings.security.blocked_front_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form.textarea
                            id="blocked_front_ips"
                            name="blocked_front_ips"
                            :value="$settings['blocked_front_ips']"
                            :rows="8"
                            :placeholder="__('admin.settings.security.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableBlockedFrontIps"
                        />
                        
                        <p>{{ __('admin.settings.security.front_ip_help') }}</p>
                    </div>
                </fieldset>
            </section>
        </section>

        <!-- 拡張機能セキュリティ設定 -->
        <section x-data="{
            preset: '{{ old('extension_security_preset', $settings['extension_security_preset']) }}',
            requireSignature: {{ old('extension_require_signature', $settings['extension_require_signature']) ? 'true' : 'false' }},
            requirePermissionDefinition: {{ old('extension_require_permission_definition', $settings['extension_require_permission_definition']) ? 'true' : 'false' }},
            allowUndefinedPermissions: {{ old('extension_allow_undefined_permissions', $settings['extension_allow_undefined_permissions']) ? 'true' : 'false' }},
            pluginMaxHealthLevel: {{ old('extension_plugin_max_health_level', $settings['extension_plugin_max_health_level']) }},
            themeMaxHealthLevel: {{ old('extension_theme_max_health_level', $settings['extension_theme_max_health_level']) }},
            allowLogicThemes: {{ old('extension_allow_logic_themes', $settings['extension_allow_logic_themes']) ? 'true' : 'false' }},
            permissionMismatchAction: '{{ old('extension_permission_mismatch_action', $settings['extension_permission_mismatch_action']) }}',
            
            applyPreset(presetValue) {
                const presets = {
                    'strict': {
                        requireSignature: true,
                        requirePermissionDefinition: true,
                        allowUndefinedPermissions: false,
                        pluginMaxHealthLevel: 0,
                        themeMaxHealthLevel: 0,
                        allowLogicThemes: false
                    },
                    'balanced': {
                        requireSignature: false,
                        requirePermissionDefinition: false,
                        allowUndefinedPermissions: true,
                        pluginMaxHealthLevel: 1,
                        themeMaxHealthLevel: 2,
                        allowLogicThemes: true
                    },
                    'development': {
                        requireSignature: false,
                        requirePermissionDefinition: false,
                        allowUndefinedPermissions: true,
                        pluginMaxHealthLevel: 3,
                        themeMaxHealthLevel: 3,
                        allowLogicThemes: true
                    }
                };
                
                if (presets[presetValue]) {
                    const p = presets[presetValue];
                    this.requireSignature = p.requireSignature;
                    this.requirePermissionDefinition = p.requirePermissionDefinition;
                    this.allowUndefinedPermissions = p.allowUndefinedPermissions;
                    this.pluginMaxHealthLevel = p.pluginMaxHealthLevel;
                    this.themeMaxHealthLevel = p.themeMaxHealthLevel;
                    this.allowLogicThemes = p.allowLogicThemes;
                }
            },
            
            getHealthLevelClass(level) {
                const classes = {
                    0: 'text-green-600 dark:text-green-400',
                    1: 'text-yellow-600 dark:text-yellow-400',
                    2: 'text-orange-600 dark:text-orange-400',
                    3: 'text-gray-600 dark:text-gray-400'
                };
                return classes[level] || classes[0];
            }
        }" x-init="$watch('preset', (value) => { if (value !== 'custom') applyPreset(value); })">
            <h2>{{ __('admin.settings.security.extension_security.title') }}</h2>
            <p>{{ __('admin.settings.security.extension_security.description') }}</p>

            <!-- プリセット選択 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_security.preset_label') }}</legend>
                
                <div class="mt-3">
                    <x-form.radio-card-group
                        name="extension_security_preset"
                        :options="\App\Enums\ExtensionSecurityPreset::getRadioCardOptions()"
                        :value="old('extension_security_preset', $settings['extension_security_preset'])"
                        xModel="preset"
                        :columns="2"
                    />
                </div>
                
                <p class="mt-2">{{ __('admin.settings.security.extension_security.preset_help') }}</p>
            </fieldset>

            <!-- カスタム設定（プリセットがcustomの場合のみ編集可能） -->
            <div class="mt-6 space-y-6" :class="{ 'opacity-50 pointer-events-none': preset !== 'custom' }">
                <div class="flex items-center gap-2 mb-4" x-show="preset !== 'custom'">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span class="text-sm text-blue-600 dark:text-blue-400">
                        {{ __('admin.settings.security.extension_security.custom_mode_hint') }}
                    </span>
                </div>

                <!-- 署名要件 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.signature_settings') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extension_security.require_signature')"
                        id="extension_require_signature"
                        name="extension_require_signature"
                        :checked="old('extension_require_signature', $settings['extension_require_signature'])"
                        xModel="requireSignature"
                    />
                    <p>{{ __('admin.settings.security.extension_security.require_signature_help') }}</p>
                </fieldset>

                <!-- 権限定義要件 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.permission_settings') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extension_security.require_permission_definition')"
                        id="extension_require_permission_definition"
                        name="extension_require_permission_definition"
                        :checked="old('extension_require_permission_definition', $settings['extension_require_permission_definition'])"
                        xModel="requirePermissionDefinition"
                    />
                    <p>{{ __('admin.settings.security.extension_security.require_permission_definition_help') }}</p>
                    
                    <div class="mt-4">
                        <x-form.toggle
                            :label="__('admin.settings.security.extension_security.allow_undefined_permissions')"
                            id="extension_allow_undefined_permissions"
                            name="extension_allow_undefined_permissions"
                            :checked="old('extension_allow_undefined_permissions', $settings['extension_allow_undefined_permissions'])"
                            xModel="allowUndefinedPermissions"
                        />
                        <p>{{ __('admin.settings.security.extension_security.allow_undefined_permissions_help') }}</p>
                    </div>
                </fieldset>

                <!-- プラグイン健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.plugin_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form.range
                            id="extension_plugin_max_health_level"
                            name="extension_plugin_max_health_level"
                            :value="old('extension_plugin_max_health_level', $settings['extension_plugin_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="\App\Enums\ExtensionSecurityLevel::getRangeLabels()"
                            :labelColors="\App\Enums\ExtensionSecurityLevel::getRangeLabelColors()"
                            xModel="pluginMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(pluginMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-puzzle-piece"></i>
                            <span class="font-medium">{{ __('admin.settings.security.extension_security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin.settings.security.extension_security.health_level.healthy') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.warning') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.needs_attention') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.not_verified') }}'
                            ][pluginMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin.settings.security.extension_security.health_level_description.healthy') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.warning') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.needs_attention') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.not_verified') }}'
                        ][pluginMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin.settings.security.extension_security.plugin_health_level_help') }}</p>
                </fieldset>

                <!-- テーマ健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.theme_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form.range
                            id="extension_theme_max_health_level"
                            name="extension_theme_max_health_level"
                            :value="old('extension_theme_max_health_level', $settings['extension_theme_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="\App\Enums\ExtensionSecurityLevel::getRangeLabels()"
                            :labelColors="\App\Enums\ExtensionSecurityLevel::getRangeLabelColors()"
                            xModel="themeMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(themeMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-palette"></i>
                            <span class="font-medium">{{ __('admin.settings.security.extension_security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin.settings.security.extension_security.health_level.healthy') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.warning') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.needs_attention') }}',
                                '{{ __('admin.settings.security.extension_security.health_level.not_verified') }}'
                            ][themeMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin.settings.security.extension_security.health_level_description.healthy') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.warning') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.needs_attention') }}',
                            '{{ __('admin.settings.security.extension_security.health_level_description.not_verified') }}'
                        ][themeMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin.settings.security.extension_security.theme_health_level_help') }}</p>
                </fieldset>

                <!-- ロジックを含むテーマ -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.logic_themes') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extension_security.allow_logic_themes')"
                        id="extension_allow_logic_themes"
                        name="extension_allow_logic_themes"
                        :checked="old('extension_allow_logic_themes', $settings['extension_allow_logic_themes'])"
                        xModel="allowLogicThemes"
                    />
                    <p>{{ __('admin.settings.security.extension_security.allow_logic_themes_help') }}</p>
                    
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium">{{ __('admin.settings.security.extension_security.theme_types_title') }}</p>
                                <ul class="mt-1 list-disc list-inside space-y-1">
                                    <li>{{ __('admin.settings.security.extension_security.theme_type_pure') }}</li>
                                    <li>{{ __('admin.settings.security.extension_security.theme_type_logic') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 権限不一致時の動作 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extension_security.permission_mismatch') }}</legend>
                    <p>{{ __('admin.settings.security.extension_security.permission_mismatch_help') }}</p>
                    
                    <div class="mt-3">
                        <x-form.radio-card-group
                            name="extension_permission_mismatch_action"
                            :options="[
                                [
                                    'value' => 'warn',
                                    'label' => __('admin.settings.security.extension_security.mismatch_action.warn'),
                                    'description' => __('admin.settings.security.extension_security.mismatch_action.warn_description'),
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'color' => 'yellow'
                                ],
                                [
                                    'value' => 'block',
                                    'label' => __('admin.settings.security.extension_security.mismatch_action.block'),
                                    'description' => __('admin.settings.security.extension_security.mismatch_action.block_description'),
                                    'icon' => 'fas fa-ban',
                                    'color' => 'red'
                                ]
                            ]"
                            :value="old('extension_permission_mismatch_action', $settings['extension_permission_mismatch_action'])"
                            xModel="permissionMismatchAction"
                            :columns="2"
                        />
                    </div>
                </fieldset>
            </div>
        </section>

        <!-- 拡張機能操作通知設定 -->
        <section>
            <h2>{{ __('admin.settings.security.extension_notification.title') }}</h2>
            <p>{{ __('admin.settings.security.extension_notification.description') }}</p>

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4">
                    <x-message
                        type="warning"
                        :message="__('admin.settings.security.error_notification_mail_test_required', ['url' => route('admin.settings.base')])"
                    />
                </div>
            @endif

            <!-- インストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.notify_on_install') }}</legend>
                
                <x-form.hidden
                    name="extension_notify_on_install"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.notify_on_install')"
                    id="extension_notify_on_install"
                    name="extension_notify_on_install"
                    :checked="old('extension_notify_on_install', $settings['extension_notify_on_install'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extension_notification.notify_on_install_help') }}</p>
            </fieldset>

            <!-- アンインストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.notify_on_uninstall') }}</legend>
                
                <x-form.hidden
                    name="extension_notify_on_uninstall"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.notify_on_uninstall')"
                    id="extension_notify_on_uninstall"
                    name="extension_notify_on_uninstall"
                    :checked="old('extension_notify_on_uninstall', $settings['extension_notify_on_uninstall'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extension_notification.notify_on_uninstall_help') }}</p>
            </fieldset>

            <!-- 有効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.notify_on_enable') }}</legend>
                
                <x-form.hidden
                    name="extension_notify_on_enable"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.notify_on_enable')"
                    id="extension_notify_on_enable"
                    name="extension_notify_on_enable"
                    :checked="old('extension_notify_on_enable', $settings['extension_notify_on_enable'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extension_notification.notify_on_enable_help') }}</p>
            </fieldset>

            <!-- 無効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.notify_on_disable') }}</legend>
                
                <x-form.hidden
                    name="extension_notify_on_disable"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.notify_on_disable')"
                    id="extension_notify_on_disable"
                    name="extension_notify_on_disable"
                    :checked="old('extension_notify_on_disable', $settings['extension_notify_on_disable'] ?? false)"
                />
                <p>{{ __('admin.settings.security.extension_notification.notify_on_disable_help') }}</p>
            </fieldset>

            <!-- 健全性警告を通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.notify_on_unhealthy') }}</legend>
                
                <x-form.hidden
                    name="extension_notify_on_unhealthy"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.notify_on_unhealthy')"
                    id="extension_notify_on_unhealthy"
                    name="extension_notify_on_unhealthy"
                    :checked="old('extension_notify_on_unhealthy', $settings['extension_notify_on_unhealthy'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extension_notification.notify_on_unhealthy_help') }}</p>
            </fieldset>

            <!-- 操作をログに記録 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extension_notification.log_operations') }}</legend>
                
                <x-form.hidden
                    name="extension_log_operations"
                    value="0"
                />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extension_notification.log_operations')"
                    id="extension_log_operations"
                    name="extension_log_operations"
                    :checked="old('extension_log_operations', $settings['extension_log_operations'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extension_notification.log_operations_help') }}</p>
            </fieldset>
        </section>

        <!-- CSP (Content Security Policy) 設定 -->
        <section class="mt-8" x-data="{ cspEnabled: {{ old('csp_enabled', $settings['csp_enabled'] ?? true) ? 'true' : 'false' }} }">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('admin.settings.security.csp.title') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('admin.settings.security.csp.description') }}</p>

            <!-- CSPとは？ -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-blue-800 dark:text-blue-300 mb-2">{{ __('admin.settings.security.csp.what_is_csp') }}</h3>
                <p class="text-sm text-blue-700 dark:text-blue-400">{{ __('admin.settings.security.csp.what_is_csp_description') }}</p>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-2">{{ __('admin.settings.security.csp.nonce_explanation') }}</p>
            </div>

            <!-- CSP有効/無効 -->
            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.enabled') }}</legend>
                
                <x-form.hidden
                    name="csp_enabled"
                    value="0"
                />
                
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           id="csp_enabled" 
                           name="csp_enabled" 
                           value="1"
                           x-model="cspEnabled"
                           {{ old('csp_enabled', $settings['csp_enabled'] ?? true) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('admin.settings.security.csp.enabled') }}</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.settings.security.csp.enabled_help') }}</p>
            </fieldset>

            <!-- CSP詳細設定（CSP有効時のみ操作可能） -->
            <div :class="{ 'opacity-50 pointer-events-none': !cspEnabled }">
                <!-- CSPモード -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.mode') }}</legend>
                    
                    <x-form.radio-card-group
                        name="csp_mode"
                        :options="[
                            [
                                'value' => 'development',
                                'label' => __('admin.settings.security.csp.mode_development'),
                                'description' => __('admin.settings.security.csp.mode_development_desc'),
                                'icon' => 'fas fa-code',
                                'color' => 'blue',
                                'features' => [
                                    __('admin.settings.security.csp.mode_development_feature1'),
                                    __('admin.settings.security.csp.mode_development_feature2'),
                                    __('admin.settings.security.csp.mode_development_feature3'),
                                ],
                            ],
                            [
                                'value' => 'standard',
                                'label' => __('admin.settings.security.csp.mode_standard'),
                                'description' => __('admin.settings.security.csp.mode_standard_desc'),
                                'icon' => 'fas fa-shield-alt',
                                'color' => 'yellow',
                                'badge' => __('admin.settings.security.csp.recommended'),
                                'badgeColor' => 'green',
                                'features' => [
                                    __('admin.settings.security.csp.mode_standard_feature1'),
                                    __('admin.settings.security.csp.mode_standard_feature2'),
                                    __('admin.settings.security.csp.mode_standard_feature3'),
                                ],
                            ],
                            [
                                'value' => 'strict',
                                'label' => __('admin.settings.security.csp.mode_strict'),
                                'description' => __('admin.settings.security.csp.mode_strict_desc'),
                                'icon' => 'fas fa-lock',
                                'color' => 'red',
                                'features' => [
                                    __('admin.settings.security.csp.mode_strict_feature1'),
                                    __('admin.settings.security.csp.mode_strict_feature2'),
                                    __('admin.settings.security.csp.mode_strict_feature3'),
                                ],
                            ],
                        ]"
                        :value="old('csp_mode', $settings['csp_mode'] ?? 'development')"
                        :columns="3"
                    />
                </fieldset>

                <!-- 違反をログに記録 -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.log_violations') }}</legend>
                    
                    <x-form.hidden
                        name="csp_log_violations"
                        value="0"
                    />
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.csp.log_violations')"
                        id="csp_log_violations"
                        name="csp_log_violations"
                        :checked="old('csp_log_violations', $settings['csp_log_violations'] ?? true)"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.settings.security.csp.log_violations_help') }}</p>
                </fieldset>

                <!-- 信頼済みドメイン -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.trusted_domains') }}</legend>
                    
                    <x-form.textarea
                        id="csp_trusted_domains"
                        name="csp_trusted_domains"
                        :value="old('csp_trusted_domains', $settings['csp_trusted_domains'] ?? '')"
                        :placeholder="__('admin.settings.security.csp.trusted_domains_placeholder')"
                        rows="4"
                        class="input-xl"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.settings.security.csp.trusted_domains_help') }}</p>
                </fieldset>

                <!-- 拒否ドメイン -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.denied_domains') }}</legend>
                    
                    <x-form.textarea
                        id="csp_denied_domains"
                        name="csp_denied_domains"
                        :value="old('csp_denied_domains', $settings['csp_denied_domains'] ?? '')"
                        :placeholder="__('admin.settings.security.csp.denied_domains_placeholder')"
                        rows="4"
                        class="input-xl"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{!! __('admin.settings.security.csp.denied_domains_help') !!}</p>

                </fieldset>

                <!-- ブロックリスト照合設定 -->
                <fieldset class="mb-4" x-data="{ blocklistEnabled: {{ old('csp_blocklist_check_enabled', $settings['csp_blocklist_check_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.blocklist_check_title') }}</legend>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{!! __('admin.settings.security.csp.blocklist_check_description') !!}</p>

                    <!-- 有効/無効 -->
                    <div class="mb-4">
                        <x-form.hidden name="csp_blocklist_check_enabled" value="0" />
                        <x-form.toggle
                            :label="__('admin.settings.security.csp.blocklist_check_enabled')"
                            id="csp_blocklist_check_enabled"
                            name="csp_blocklist_check_enabled"
                            :checked="old('csp_blocklist_check_enabled', $settings['csp_blocklist_check_enabled'] ?? false)"
                            xModel="blocklistEnabled"
                        />
                    </div>

                    <!-- 検出時のアクション -->
                    <div class="mb-4 pl-6" :class="{ 'opacity-50 pointer-events-none': !blocklistEnabled }">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.settings.security.csp.blocklist_action_label') }}</p>
                        <x-form.radio-card-group
                            name="csp_blocklist_action"
                            :options="[
                                [
                                    'value' => 'warn',
                                    'label' => __('admin.settings.security.csp.blocklist_action_warn'),
                                    'description' => __('admin.settings.security.csp.blocklist_action_warn_desc'),
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'color' => 'yellow',
                                ],
                                [
                                    'value' => 'block',
                                    'label' => __('admin.settings.security.csp.blocklist_action_block'),
                                    'description' => __('admin.settings.security.csp.blocklist_action_block_desc'),
                                    'icon' => 'fas fa-ban',
                                    'color' => 'red',
                                ],
                            ]"
                            :value="old('csp_blocklist_action', $settings['csp_blocklist_action'] ?? 'warn')"
                            :columns="2"
                        />
                    </div>

                    <!-- カテゴリ選択 -->
                    <div class="space-y-3 pl-6 mb-4" :class="{ 'opacity-50 pointer-events-none': !blocklistEnabled }">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ __('admin.settings.security.csp.blocklist_check_categories') }}</p>
                        @php
                            $enabledCategories = explode(',', old('csp_blocklist_enabled_categories', $settings['csp_blocklist_enabled_categories'] ?? ''));
                            $blocklistSources = config('csp.blocklist_sources', []);
                            $categoryIcons = [
                                'tracking' => 'fas fa-ad',
                                'malware' => 'fas fa-virus',
                                'cryptominer' => 'fas fa-coins',
                            ];
                        @endphp
                        @foreach($blocklistSources as $categoryKey => $categoryData)
                        @php
                            $isChecked = in_array($categoryKey, $enabledCategories);
                            $toggleId = 'csp_blocklist_category_' . $categoryKey;
                        @endphp
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                            <label for="{{ $toggleId }}" class="flex items-center gap-3 cursor-pointer">
                                <div class="relative inline-flex items-center flex-shrink-0">
                                    <input type="checkbox"
                                           id="{{ $toggleId }}"
                                           name="csp_blocklist_categories[]"
                                           value="{{ $categoryKey }}"
                                           {{ $isChecked ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none bg-gray-200 dark:bg-gray-600 peer-checked:bg-indigo-600"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
                                </div>
                                <i class="{{ $categoryIcons[$categoryKey] ?? 'fas fa-list' }} text-gray-500 dark:text-gray-400"></i>
                                <div class="flex-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ app()->getLocale() === 'en' ? ($categoryData['name_en'] ?? $categoryData['name']) : $categoryData['name'] }}</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'en' ? ($categoryData['description_en'] ?? $categoryData['description']) : $categoryData['description'] }}</p>
                                </div>
                            </label>
                            <!-- 取得先URL表示 -->
                            <div class="ml-14 mt-2">
                                <details class="text-xs">
                                    <summary class="text-blue-600 dark:text-blue-400 cursor-pointer hover:underline">{{ __('admin.settings.security.csp.blocklist_sources_show') }}</summary>
                                    <ul class="mt-1 space-y-0.5 text-gray-500 dark:text-gray-400 font-mono text-[10px]">
                                        @foreach($categoryData['lists'] ?? [] as $url)
                                        <li class="truncate" title="{{ $url }}">{{ $url }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </fieldset>

                <!-- カスタムディレクティブ（上級者向け） -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.settings.security.csp.custom_directives') }}</legend>
                    
                    <x-form.textarea
                        id="csp_custom_directives"
                        name="csp_custom_directives"
                        :value="old('csp_custom_directives', $settings['csp_custom_directives'] ?? '')"
                        :placeholder="__('admin.settings.security.csp.custom_directives_placeholder')"
                        rows="3"
                        class="input-xl"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.settings.security.csp.custom_directives_help') }}</p>
                </fieldset>
            </div>
        </section>
    </form>

    <!-- ファイル整合性チェック（フォーム外） -->
    <section class="mt-8" x-data="fileIntegrityChecker()">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('admin.settings.security.file_integrity.title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('admin.settings.security.file_integrity.description') }}</p>

        <!-- ベースライン情報 -->
        <div class="mb-6 p-4 rounded-lg {{ $hasBaseline ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
            <div class="flex items-center gap-2 mb-2">
                @if($hasBaseline)
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                    <span class="font-medium text-green-800 dark:text-green-200">{{ __('admin.settings.security.file_integrity.baseline_exists') }}</span>
                @else
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                    <span class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('admin.settings.security.file_integrity.baseline_not_exists') }}</span>
                @endif
            </div>
            @if($hasBaseline && $baselineMeta)
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <p><span class="font-medium">{{ __('command.integrity.generated_at') }}:</span> {{ $baselineMeta['generated_at'] ?? '-' }}</p>
                    <p><span class="font-medium">{{ __('command.integrity.app_version') }}:</span> {{ $baselineMeta['app_version'] ?? '-' }}</p>
                    <p><span class="font-medium">{{ __('command.integrity.hash_algo') }}:</span> {{ $baselineMeta['hash_algo'] ?? 'sha256' }}</p>
                </div>
            @endif
        </div>

        <!-- 最新スキャン結果 -->
        @if($latestIntegrityAudit)
            <div class="mb-6 p-4 rounded-lg border {{ $latestIntegrityAudit->status === 'ok' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : ($latestIntegrityAudit->status === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800') }}">
                <div class="flex items-center gap-2 mb-2">
                    <i class="{{ $latestIntegrityAudit->getStatusIcon() }} {{ $latestIntegrityAudit->status === 'ok' ? 'text-green-600 dark:text-green-400' : ($latestIntegrityAudit->status === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}"></i>
                    <span class="font-medium {{ $latestIntegrityAudit->status === 'ok' ? 'text-green-800 dark:text-green-200' : ($latestIntegrityAudit->status === 'warning' ? 'text-yellow-800 dark:text-yellow-200' : 'text-red-800 dark:text-red-200') }}">
                        {{ __('admin.settings.security.file_integrity.last_scan_result') }}: {{ __('command.integrity.status_' . $latestIntegrityAudit->status) }}
                    </span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <p><span class="font-medium">{{ __('admin.settings.security.file_integrity.scanned_at') }}:</span> {{ $latestIntegrityAudit->started_at?->format('Y-m-d H:i:s') }}</p>
                    <p><span class="font-medium">{{ __('command.integrity.files_scanned') }}:</span> {{ $latestIntegrityAudit->total_files_scanned }}</p>
                    @if($latestIntegrityAudit->hasIssues())
                        <p class="mt-2">
                            <span class="font-medium">{{ __('command.integrity.summary') }}:</span> {{ $latestIntegrityAudit->summary }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

        <!-- スキャン結果表示エリア（AJAX結果用） -->
        <div x-show="scanResult" x-cloak class="mb-6 p-4 rounded-lg border" :class="getScanResultClass()">
            <div class="flex items-center gap-2 mb-2">
                <i :class="getScanResultIcon()"></i>
                <span class="font-medium" :class="getScanResultTextClass()" x-text="scanResult?.status_label"></span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <p><span class="font-medium">{{ __('command.integrity.files_scanned') }}:</span> <span x-text="scanResult?.total_files_scanned"></span></p>
                <p><span class="font-medium">{{ __('command.integrity.duration') }}:</span> <span x-text="scanResult?.duration_ms + 'ms'"></span></p>
                <template x-if="scanResult?.summary">
                    <p><span class="font-medium">{{ __('command.integrity.summary') }}:</span> <span x-text="scanResult?.summary"></span></p>
                </template>
            </div>

            <!-- 詳細結果 -->
            <template x-if="scanResult?.result && (scanResult.result.changed?.length || scanResult.result.added?.length || scanResult.result.removed?.length || scanResult.result.suspicious?.length)">
                <div class="mt-4 space-y-3">
                    <!-- 変更されたファイル -->
                    <template x-if="scanResult.result.changed?.length">
                        <div>
                            <h4 class="font-medium text-yellow-700 dark:text-yellow-300 mb-1">{{ __('admin.settings.security.file_integrity.changed_files') }} (<span x-text="scanResult.result.changed.length"></span>)</h4>
                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="file in scanResult.result.changed" :key="file.path">
                                    <li class="flex items-center gap-1">
                                        <span class="text-yellow-600">M</span>
                                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded" x-text="file.path"></code>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- 追加されたファイル -->
                    <template x-if="scanResult.result.added?.length">
                        <div>
                            <h4 class="font-medium text-green-700 dark:text-green-300 mb-1">{{ __('admin.settings.security.file_integrity.added_files') }} (<span x-text="scanResult.result.added.length"></span>)</h4>
                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="file in scanResult.result.added" :key="file.path">
                                    <li class="flex items-center gap-1">
                                        <span class="text-green-600">A</span>
                                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded" x-text="file.path"></code>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- 削除されたファイル -->
                    <template x-if="scanResult.result.removed?.length">
                        <div>
                            <h4 class="font-medium text-red-700 dark:text-red-300 mb-1">{{ __('admin.settings.security.file_integrity.removed_files') }} (<span x-text="scanResult.result.removed.length"></span>)</h4>
                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="file in scanResult.result.removed" :key="file.path">
                                    <li class="flex items-center gap-1">
                                        <span class="text-red-600">D</span>
                                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded" x-text="file.path"></code>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- 疑わしいファイル -->
                    <template x-if="scanResult.result.suspicious?.length">
                        <div>
                            <h4 class="font-medium text-red-700 dark:text-red-300 mb-1">{{ __('admin.settings.security.file_integrity.suspicious_files') }} (<span x-text="scanResult.result.suspicious.length"></span>)</h4>
                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="file in scanResult.result.suspicious" :key="file.path">
                                    <li class="flex items-center gap-1">
                                        <span class="text-red-600">!</span>
                                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded" x-text="file.path"></code>
                                        <span class="text-xs text-gray-500" x-text="'(' + file.reason + ')'"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- エラー表示 -->
        <div x-show="errorMessage" x-cloak class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <div class="flex items-center gap-2">
                <i class="fas fa-times-circle text-red-600 dark:text-red-400"></i>
                <span class="text-red-800 dark:text-red-200" x-text="errorMessage"></span>
            </div>
        </div>

        <!-- アクションボタン -->
        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                @click="runScan()"
                :disabled="isScanning"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors"
            >
                <i class="fas fa-search" :class="{ 'animate-spin': isScanning }"></i>
                <span x-text="isScanning ? '{{ __('admin.settings.security.file_integrity.scanning') }}' : '{{ __('admin.settings.security.file_integrity.run_scan') }}'"></span>
            </button>

            <button
                type="button"
                @click="regenerateBaseline()"
                :disabled="isRegenerating"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition-colors"
            >
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': isRegenerating }"></i>
                <span x-text="isRegenerating ? '{{ __('admin.settings.security.file_integrity.regenerating') }}' : '{{ __('admin.settings.security.file_integrity.regenerate_baseline') }}'"></span>
            </button>
        </div>

        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.file_integrity.regenerate_baseline_help') }}</p>
    </section>
</div>
</div>

@endsection



@section('save')
    <!-- 保存ボタンとモーダル -->
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        onclick="validateBeforeSave()"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.back')"
        form="security-settings-form"
    />
@endsection


@push('scripts')
<script>
// Localized messages for JavaScript
const captchaMessages = {
    validationError: @json(__('admin.settings.security.captcha_validation_error')),
    buttonDisabledReason: @json(__('admin.settings.security.captcha_button_disabled_reason')),
    enterSiteKey: @json(__('admin.settings.security.captcha_enter_site_key')),
    invalidV3SiteKey: @json(__('admin.settings.security.captcha_invalid_v3_site_key')),
    requiredFieldsMissing: @json(__('admin.settings.security.captcha_required_fields_missing')),
    requiredFieldsEmpty: @json(__('admin.settings.security.captcha_required_fields_empty')),
    enterpriseProjectIdRequired: @json(__('admin.settings.security.captcha_enterprise_project_id_required')),
    unsupportedVersion: @json(__('admin.settings.security.captcha_unsupported_version')),
    unsupportedProvider: @json(__('admin.settings.security.captcha_unsupported_provider')),
    authenticationSuccess: @json(__('admin.settings.security.captcha_authentication_success')),
    authenticationFailed: @json(__('admin.settings.security.captcha_authentication_failed')),
    v3ExecutionFailed: @json(__('admin.settings.security.captcha_v3_execution_failed')),
    v3ScriptLoadFailed: @json(__('admin.settings.security.captcha_v3_script_load_failed')),
    v2InvisibleError: @json(__('admin.settings.security.captcha_v2_invisible_error')),
    v2ScriptLoadFailed: @json(__('admin.settings.security.captcha_v2_script_load_failed')),
    v2CheckboxInstruction: @json(__('admin.settings.security.captcha_v2_checkbox_instruction')),
    v2CheckboxError: @json(__('admin.settings.security.captcha_v2_checkbox_error')),
    v2WidgetRenderFailed: @json(__('admin.settings.security.captcha_v2_widget_render_failed')),
    turnstileError: @json(__('admin.settings.security.captcha_turnstile_error')),
    turnstileScriptLoadFailed: @json(__('admin.settings.security.captcha_turnstile_script_load_failed')),
    enterpriseExecutionFailed: @json(__('admin.settings.security.captcha_enterprise_execution_failed')),
    enterpriseScriptLoadFailed: @json(__('admin.settings.security.captcha_enterprise_script_load_failed')),
    
    // Additional messages
    testCompletedSuccessfully: @json(__('admin.settings.security.captcha_test_completed_successfully')),
    testCompletedHint: @json(__('admin.settings.security.captcha_test_completed_hint', ['members_url' => route('admin.settings.members.settings')])),
    authenticationSuccessTitle: @json(__('admin.settings.security.captcha_authentication_success_title')),
    authenticationFailedTitle: @json(__('admin.settings.security.captcha_authentication_failed_title')),
    expiredMessage: @json(__('admin.settings.security.captcha_expired_message')),
    siteKeyProjectIdMissing: @json(__('admin.settings.security.captcha_site_key_project_id_missing')),
    serverCommunicationFailed: @json(__('admin.settings.security.captcha_server_communication_failed')),
    providerSettingsCheck: @json(__('admin.settings.security.captcha_provider_settings_check'))
};

// フォーム送信時のデバッグ関数
function debugFormSubmission(event) {
    const form = event.target;
    const formData = new FormData(form);
    
    console.log('=== FORM SUBMISSION DEBUG ===');
    console.log('All form data:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // 最小スコア関連の値を特別にログ
    const visibleMinScore = document.getElementById('captcha_google_min_score')?.value;
    const version = document.getElementById('captcha_google_version')?.value;
    
    console.log('Min Score Debug:');
    console.log('- Visible field value:', visibleMinScore);
    console.log('- Version:', version);
    console.log('- Form data captcha_google_min_score:', formData.get('captcha_google_min_score'));
    console.log('=== END DEBUG ===');
}

// CAPTCHA検証が必要かチェックして保存前に検証
function validateBeforeSave() {
    const captchaEnabled = document.getElementById('captcha_enabled').checked;
    
    if (captchaEnabled && !captchaValidated) {
        // CAPTCHAが有効で未検証の場合は警告を表示
        alert('{{ __("admin.settings.security.captcha_validation_required_before_save") }}');
        return false;
    }
    
    // 検証済みまたはCAPTCHA無効の場合は通常の保存確認モーダルを開く
    openModal('confirmationModal');
}

function testCaptchaConnection(driver) {
    const statusElement = document.getElementById(`captcha-test-status-${driver}`);
    const button = event.target;
    
    // ドライバー名を取得（Enumから）
    const providerLabels = {
        'google': '@php echo \App\Enums\CaptchaProvider::GOOGLE->label(); @endphp',
        'google_enterprise': '@php echo \App\Enums\CaptchaProvider::GOOGLE_ENTERPRISE->label(); @endphp',
        'turnstile': '@php echo \App\Enums\CaptchaProvider::TURNSTILE->label(); @endphp'
    };
    const driverName = providerLabels[driver] || driver;
    
    // テスト中の表示
    statusElement.className = 'my-4 p-3 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
    statusElement.innerHTML = `
        <div class="flex items-center">
            <i class="mr-2 fas fa-spinner fa-spin text-blue-500"></i>
            <span class="text-sm text-blue-700 dark:text-blue-300">
                @lang("admin.settings.security.captcha_test_status.testing")
            </span>
        </div>
    `;
    button.disabled = true;
    button.textContent = '@lang("admin.settings.security.captcha_test_status.testing")';
    
    // フォームデータを取得
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('captcha_driver', driver);
    
    // ドライバー別の設定を取得
    if (driver === 'google') {
        formData.append('captcha_site_key', document.getElementById('captcha_site_key').value);
        formData.append('captcha_secret_key', document.getElementById('captcha_secret_key').value);
    } else if (driver === 'google_enterprise') {
        formData.append('captcha_google_enterprise_site_key', document.getElementById('captcha_google_enterprise_site_key').value);
        formData.append('captcha_google_enterprise_secret_key', document.getElementById('captcha_google_enterprise_secret_key').value);
        formData.append('captcha_google_project_id', document.getElementById('captcha_google_enterprise_project_id').value);
    } else if (driver === 'turnstile') {
        formData.append('captcha_turnstile_site_key', document.getElementById('captcha_turnstile_site_key').value);
        formData.append('captcha_turnstile_secret_key', document.getElementById('captcha_turnstile_secret_key').value);
    }
    
    // テスト実行
    fetch('@php echo route("admin.settings.security.test-captcha"); @endphp', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const currentTime = new Date().toLocaleString('ja-JP');
        if (data.success) {
            statusElement.className = 'my-4 p-3 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            statusElement.innerHTML = `
                <div class="flex items-center">
                    <i class="mr-2 fas fa-check-circle text-green-500"></i>
                    <span class="text-sm text-green-700 dark:text-green-300">
                        @lang('admin.settings.security.captcha_test_status.passed_success')
                    </span>
                </div>
            `;
        } else {
            statusElement.className = 'my-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
            statusElement.innerHTML = `
                <div class="flex items-center">
                    <i class="mr-2 fas fa-times-circle text-red-500"></i>
                    <span class="text-sm text-red-700 dark:text-red-300">
                        @lang('admin.settings.security.captcha_test_status.failed')
                    </span>
                </div>
            `;
        }
    })
    .catch(error => {
        statusElement.className = 'mt-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        statusElement.innerHTML = `
            <div class="flex items-center">
                <i class="mr-2 fas fa-times-circle text-red-500"></i>
                <span class="text-sm text-red-700 dark:text-red-300">
                    @lang('admin.settings.security.captcha_test_status.failed')
                </span>
            </div>
        `;
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = '@lang("admin.settings.security.captcha_test_button")';
    });
}

// プロバイダー情報を取得する関数
function getProviderInfo(driver) {
    const providers = {
        'google': {
            name: 'Google reCAPTCHA',
            url: 'https://www.google.com/recaptcha/admin/create'
        },
        'google_enterprise': {
            name: 'Google reCAPTCHA Enterprise',
            url: 'https://cloud.google.com/recaptcha-enterprise/docs/create-key'
        },
        'turnstile': {
            name: 'Cloudflare Turnstile',
            url: 'https://dash.cloudflare.com/?to=/:account/turnstile'
        }
    };
    return providers[driver] || { name: driver, url: '#' };
}


// CAPTCHAトークンをサーバーに送信して検証
function validateCaptchaToken(token) {
    fetch('{{ route("admin.settings.security.test-captcha") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            'g-recaptcha-response': token,
            captcha_driver: document.getElementById('captcha_driver').value,
            captcha_google_version: document.getElementById('captcha_google_version').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('CAPTCHA validation successful');
            showTestResult('success', data.message);
            onCaptchaValidationSuccess();
            enableValidationButton();
        } else {
            console.error('CAPTCHA validation failed:', data.message);
            showTestResult('error', data.message);
            onCaptchaValidationFailure();
            enableValidationButton();
        }
    })
    .catch(error => {
        console.error('CAPTCHA validation error:', error);
        showTestResult('error', captchaMessages.validationError);
        onCaptchaValidationFailure();
        enableValidationButton();
    });
}

// セッションからテスト結果をクリアする関数
function clearCaptchaTestSession(driver) {
    fetch('@php echo route("admin.settings.security.clear-captcha-test"); @endphp', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            driver: driver
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('CAPTCHA test session cleared:', data);
    })
    .catch(error => {
        console.error('Error clearing CAPTCHA test session:', error);
    });
}

// CAPTCHA Widget Management
let currentCaptchaWidget = null;
let captchaValidated = false;

// CAPTCHAウィジェットを動的に読み込み
function loadCaptchaWidget() {
    const driver = document.getElementById('captcha_driver').value;
    const container = document.getElementById('captcha-widget-container');
    
    if (!container) return;
    
    // 既存のウィジェットをクリア
    container.innerHTML = '';
    currentCaptchaWidget = null;
    captchaValidated = false;
    updateValidationStatus('required');
    
    if (driver === 'google') {
        loadGoogleRecaptchaWidget();
    } else if (driver === 'google_enterprise') {
        loadGoogleEnterpriseWidget();
    } else if (driver === 'turnstile') {
        loadTurnstileWidget();
    }
}

// デバッグ用ログ関数
function debugCaptcha(message, data = {}) {
    const timestamp = new Date().toLocaleTimeString();
    console.log(`[CAPTCHA DEBUG ${timestamp}] ${message}`, data);
}

// 認証ボタンを無効化/有効化する関数
function disableValidationButton(reason) {
    const button = document.querySelector('button[onclick="validateCaptchaWidget()"]');
    if (button) {
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        button.title = reason || captchaMessages.buttonDisabledReason;
        debugCaptcha('Validation button disabled', {reason});
    }
}

function enableValidationButton() {
    const button = document.querySelector('button[onclick="validateCaptchaWidget()"]');
    if (button) {
        button.disabled = false;
        button.classList.remove('opacity-50', 'cursor-not-allowed');
        button.title = '';
        debugCaptcha('Validation button enabled');
    }
}

// Google reCAPTCHAウィジェットを読み込み
function loadGoogleRecaptchaWidget() {
    const siteKeyElement = document.getElementById('captcha_site_key');
    const versionElement = document.getElementById('captcha_google_version');
    const container = document.getElementById('captcha-widget-container');
    
    if (!siteKeyElement || !versionElement || !container) {
        debugCaptcha('Required elements not found', {
            siteKeyElement: !!siteKeyElement,
            versionElement: !!versionElement,
            container: !!container
        });
        return;
    }
    
    const siteKey = siteKeyElement.value;
    const version = versionElement.value;
    
    debugCaptcha('loadGoogleRecaptchaWidget called', {siteKey: siteKey.substring(0, 20) + '...', version});
    
    // 認証ボタンを有効化（新しいウィジェット読み込み時）
    enableValidationButton();
    
    if (!siteKey) {
        container.innerHTML = '<p class="text-sm text-gray-500">' + captchaMessages.enterSiteKey + '</p>';
        return;
    }
    
    // スクリプトを動的に読み込み
    const scriptId = 'recaptcha-script';
    let existingScript = document.getElementById(scriptId);
    if (existingScript) {
        existingScript.remove();
    }
    
    const script = document.createElement('script');
    script.id = scriptId;
    
    if (version === 'v3') {
        script.src = `//www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.onload = () => renderV3Widget(siteKey);
    } else {
        script.src = '//www.google.com/recaptcha/api.js';
        script.onload = () => renderV2Widget(siteKey, version);
    }
    
    document.head.appendChild(script);
}

// v3ウィジェットをレンダリング
function renderV3Widget(siteKey) {
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = `
        <input type="hidden" id="g-recaptcha-response-v3" name="g-recaptcha-response" value="">
    `;
    
    // reCAPTCHAスクリプトエラーを監視
    const errorHandler = function(event) {
        if (event.message && event.message.includes('Invalid site key')) {
            console.error('Caught v3 site key error:', event.message);
            updateValidationStatus('failed');
            container.innerHTML = '<div class="text-red-600 text-sm">' + captchaMessages.invalidV3SiteKey + '<br>現在のキー: ' + siteKey.substring(0, 20) + '...</div>';
            event.preventDefault();
            // エラーハンドラーを削除
            window.removeEventListener('error', errorHandler);
            return true;
        }
    };
    window.addEventListener('error', errorHandler);
    
    try {
        grecaptcha.ready(function() {
            debugCaptcha('v3 widget rendering started', {siteKey: siteKey.substring(0, 20) + '...', keyLength: siteKey.length});
            
            // v3ウィジェットを配置 - キーの有効性に関係なく表示
            currentCaptchaWidget = 'v3';
            container.innerHTML = `
                <input type="hidden" id="g-recaptcha-response-v3" name="g-recaptcha-response" value="">
            `;
            debugCaptcha('v3 widget placed', {siteKey: siteKey.substring(0, 20) + '...'});
        });
    } catch (error) {
        console.error('reCAPTCHA v3 initialization error:', error);
        // エラーが発生してもウィジェットを配置
        currentCaptchaWidget = 'v3';
        container.innerHTML = `
            <input type="hidden" id="g-recaptcha-response-v3" name="g-recaptcha-response" value="">
        `;
        debugCaptcha('v3 widget placed despite initialization error', {error: error.message});
    }
}

// v2ウィジェットをレンダリング
function renderV2Widget(siteKey, version) {
    const container = document.getElementById('captcha-widget-container');
    const widgetId = 'recaptcha-widget-' + Date.now();
    
    if (version === 'v2_invisible') {
        container.innerHTML = `
            <div id="${widgetId}"></div>
            <input type="hidden" id="g-recaptcha-response-v2" name="g-recaptcha-response" value="">
        `;
    } else {
        container.innerHTML = `
            <div id="${widgetId}"></div>
            <input type="hidden" id="g-recaptcha-response-v2" name="g-recaptcha-response" value="">
        `;
    }
    
    grecaptcha.ready(function() {
        try {
            debugCaptcha('v2 widget rendering started', {siteKey: siteKey.substring(0, 20) + '...', version, keyLength: siteKey.length});
            
            // キー形式チェックを削除 - プロバイダー/バージョン選択に基づいてウィジェットを配置
            
            const widgetOptions = {
                'sitekey': siteKey,
                'callback': onRecaptchaSuccess,
                'expired-callback': onRecaptchaExpired,
                'error-callback': function() {
                    debugCaptcha('v2 widget error callback triggered', {version, siteKey: siteKey.substring(0, 20) + '...'});
                    console.error('reCAPTCHA v2 error: Invalid site key or configuration');
                    // エラーが発生してもウィジェットは配置済みなので、テスト時にエラーハンドリング
                }
            };
            
            if (version === 'v2_invisible') {
                widgetOptions['size'] = 'invisible';
            }
            
            currentCaptchaWidget = grecaptcha.render(widgetId, widgetOptions);
        } catch (error) {
            console.error('reCAPTCHA v2 render error:', error);
            // エラーが発生してもウィジェット配置は完了しているので、テスト時にエラーハンドリング
            debugCaptcha('v2 widget placed despite render error', {error: error.message, version});
        }
    });
}

// reCAPTCHA成功コールバック
function onRecaptchaSuccess(token) {
    // バージョンに応じて適切なフィールドに設定
    const v2Field = document.getElementById('g-recaptcha-response-v2');
    const v3Field = document.getElementById('g-recaptcha-response-v3');
    
    if (v2Field) {
        v2Field.value = token;
    } else if (v3Field) {
        v3Field.value = token;
    }
    
    // サーバーでトークンを検証
    validateCaptchaToken(token);
}

// reCAPTCHA期限切れコールバック
function onRecaptchaExpired() {
    const v2Field = document.getElementById('g-recaptcha-response-v2');
    const v3Field = document.getElementById('g-recaptcha-response-v3');
    
    if (v2Field) {
        v2Field.value = '';
    } else if (v3Field) {
        v3Field.value = '';
    }
    
    captchaValidated = false;
    updateValidationStatus('expired');
}

// Turnstileウィジェットを読み込み
function loadTurnstileWidget() {
    const siteKey = document.getElementById('captcha_turnstile_site_key').value;
    const container = document.getElementById('captcha-widget-container');
    
    if (!siteKey) {
        container.innerHTML = '<p class="text-sm text-gray-500">' + captchaMessages.enterSiteKey + '</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="text-sm text-gray-600 mb-2">Cloudflare Turnstile</div>
        <div class="cf-turnstile" data-sitekey="${siteKey}" data-callback="onTurnstileSuccess"></div>
    `;
    
    // Turnstileスクリプトを読み込み
    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    document.head.appendChild(script);
    
    currentCaptchaWidget = 'turnstile';
}

// Turnstile成功コールバック
function onTurnstileSuccess(token) {
    captchaValidated = true;
    updateValidationStatus('success');
}

// 検証ステータスを更新
function updateValidationStatus(status) {
    const statusElement = document.getElementById('captcha-validation-status');
    if (!statusElement) return;
    
    // 隠しフィールドの値も更新
    const hiddenInput = document.getElementById('captcha-validation-status');
    if (hiddenInput && hiddenInput.tagName === 'INPUT') {
        const value = (status === 'success' || status === 'authenticated' || status === 'passed') ? '1' : '0';
        hiddenInput.value = value;
        debugCaptcha('Validation status updated', {status, value});
    }
    
    let html = '';
    switch (status) {
        case 'required':
            html = `
                <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="text-sm">{{ __('admin.settings.security.captcha_validation_required') }}</span>
                </div>
            `;
            break;
        case 'success':
        case 'passed':
            html = `
                <div class="flex items-center text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="text-sm">{{ __('admin.settings.security.validation.captcha_validation_success_fresh') }}</span>
                </div>
            `;
            break;
        case 'authenticated':
            html = `
                <div class="flex items-center text-blue-600 dark:text-blue-400">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span class="text-sm">{{ __('admin.settings.security.validation.captcha_validation_authenticated') }}</span>
                </div>
            `;
            break;
        case 'failed':
            html = `
                <div class="flex items-center text-red-600 dark:text-red-400">
                    <i class="fas fa-times-circle mr-2"></i>
                    <span class="text-sm">{{ __('admin.settings.security.captcha_validation_failed') }}</span>
                </div>
            `;
            break;
        case 'expired':
            html = `
                <div class="flex items-center text-orange-600 dark:text-orange-400">
                    <i class="fas fa-clock mr-2"></i>
                    <span class="text-sm">' + captchaMessages.expiredMessage + '</span>
                </div>
            `;
            break;
    }
    
    // UI表示要素を探す（隠しフィールドではない要素）
    const displayElement = document.querySelector('#captcha-validation-status:not(input)') || 
                          document.querySelector('[id*="captcha-validation"]:not(input)');
    if (displayElement) {
        displayElement.innerHTML = html;
    }
}

// 新しいvalidateCaptchaWidget関数 - 動的ウィジェット読み込み
function validateCaptchaWidget() {
    console.log('=== CAPTCHA Widget Validation Started ===');
    
    // 現在のフォーム値を取得（null チェック付き）
    const driverElement = document.getElementById('captcha_driver');
    const versionElement = document.getElementById('captcha_google_version');
    const siteKeyElement = document.getElementById('captcha_site_key');
    const secretKeyElement = document.getElementById('captcha_secret_key');
    const projectIdElement = document.getElementById('captcha_google_project_id');
    
    if (!driverElement || !versionElement || !siteKeyElement || !secretKeyElement) {
        console.error('Required form elements not found:', {
            driver: !!driverElement,
            version: !!versionElement,
            siteKey: !!siteKeyElement,
            secretKey: !!secretKeyElement
        });
        showTestResult('error', captchaMessages.requiredFieldsMissing);
        return;
    }
    
    const driver = driverElement.value;
    const version = versionElement.value;
    const siteKey = siteKeyElement.value;
    const secretKey = secretKeyElement.value;
    const projectId = projectIdElement ? projectIdElement.value : '';
    
    console.log('Current form values:', {
        driver: driver,
        version: version,
        siteKey: siteKey ? siteKey.substring(0, 20) + '...' : 'empty',
        secretKey: secretKey ? secretKey.substring(0, 20) + '...' : 'empty',
        projectId: projectId || 'empty'
    });
    
    // 必須フィールドの検証
    if (!driver || !version || !siteKey || !secretKey) {
        showTestResult('error', captchaMessages.requiredFieldsEmpty);
        return;
    }
    
    // Enterpriseの場合はプロジェクトIDも必須
    if (driver === 'google_enterprise' && !projectId) {
        showTestResult('error', captchaMessages.enterpriseProjectIdRequired);
        return;
    }
    
    // CAPTCHAウィジェットコンテナをクリア
    const container = document.getElementById('captcha-widget-container');
    if (container) {
        container.innerHTML = '';
    }
    
    // テスト結果をリセット
    hideTestResult();
    
    // プロバイダーとバージョンに基づいて動的読み込み
    if (driver === 'google') {
        switch (version) {
            case 'v3':
                loadGoogleV3Dynamic(siteKey);
                break;
            case 'v2_invisible':
                loadGoogleV2InvisibleDynamic(siteKey);
                break;
            case 'v2_checkbox':
                loadGoogleV2CheckboxDynamic(siteKey);
                break;
            default:
                showTestResult('error', captchaMessages.unsupportedVersion + ': ' + version);
        }
    } else if (driver === 'google_enterprise') {
        loadGoogleEnterpriseWidgetDynamic(siteKey, projectId);
    } else if (driver === 'turnstile') {
        loadTurnstileWidgetDynamic(siteKey);
    } else {
        showTestResult('error', captchaMessages.unsupportedProvider + ': ' + driver);
    }
}

// CAPTCHA認証結果の処理を更新
function handleCaptchaAuthenticationResult(success, message) {
    console.log('CAPTCHA authentication result:', {success, message});
    
    if (success) {
        // 成功時: 隠し入力に結果を保存し、成功メッセージを表示
        const resultInput = document.getElementById('captcha-authentication-result');
        if (resultInput) {
            resultInput.value = '1';
        }
        showTestResult('success', message || captchaMessages.authenticationSuccess);
        hideAuthTestRequiredNotice();
    } else {
        // 失敗時: 隠し入力をクリアし、エラーメッセージを表示
        const resultInput = document.getElementById('captcha-authentication-result');
        if (resultInput) {
            resultInput.value = '0';
        }
        showTestResult('error', message || captchaMessages.authenticationFailed);
        showAuthTestRequiredNotice();
    }
}

// 認証テスト必要通知を表示
function showAuthTestRequiredNotice() {
    const notice = document.getElementById('auth-test-required-notice');
    if (notice) {
        notice.style.display = 'block';
    }
}

// 認証テスト必要通知を非表示
function hideAuthTestRequiredNotice() {
    const notice = document.getElementById('auth-test-required-notice');
    if (notice) {
        notice.style.display = 'none';
    }
}

// Google reCAPTCHA動的読み込み
function loadGoogleRecaptchaWidgetDynamic(version, siteKey) {
    console.log('Loading Google reCAPTCHA dynamically:', {version, siteKey: siteKey.substring(0, 20) + '...'});
    
    if (version === 'v3') {
        loadGoogleV3Dynamic(siteKey);
    } else if (version === 'v2_invisible') {
        loadGoogleV2InvisibleDynamic(siteKey);
    } else if (version === 'v2_checkbox') {
        loadGoogleV2CheckboxDynamic(siteKey);
    }
}

// Google reCAPTCHA v3 動的読み込み
function loadGoogleV3Dynamic(siteKey) {
    console.log('Loading Google reCAPTCHA v3 dynamically with siteKey:', siteKey.substring(0, 20) + '...');
    
    // 既存のreCAPTCHAスクリプトとgrecaptchaオブジェクトをクリーンアップ
    const existingScripts = document.querySelectorAll('script[src*="google.com/recaptcha"]');
    existingScripts.forEach(script => {
        console.log('Removing existing reCAPTCHA script:', script.src);
        script.remove();
    });
    
    // grecaptchaオブジェクトを削除
    if (typeof grecaptcha !== 'undefined') {
        console.log('Cleaning up existing grecaptcha object');
        delete window.grecaptcha;
    }
    
    // v3スクリプトを動的に読み込み
    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
    script.onload = function() {
        console.log('Google reCAPTCHA v3 script loaded, executing...');
        
        grecaptcha.ready(function() {
            // 自動実行
            grecaptcha.execute(siteKey, {action: 'validate_settings'}).then(function(token) {
                console.log('v3 token generated:', token.substring(0, 50) + '...');
                validateCaptchaToken(token);
            }).catch(function(error) {
                console.error('reCAPTCHA v3 execution error:', error);
                showTestResult('error', captchaMessages.v3ExecutionFailed + ': ' + error.message);
            });
        });
    };
    script.onerror = function() {
        console.error('Failed to load Google reCAPTCHA v3 script');
        showTestResult('error', captchaMessages.v3ScriptLoadFailed);
    };
    document.head.appendChild(script);
}

// Google reCAPTCHA v2 invisible 動的読み込み
function loadGoogleV2InvisibleDynamic(siteKey) {
    console.log('Loading Google reCAPTCHA v2 invisible dynamically with siteKey:', siteKey.substring(0, 20) + '...');
    
    // ウィジェットコンテナを準備
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = '<div id="recaptcha-v2-invisible"></div>';
    
    // 既存のreCAPTCHAスクリプトとgrecaptchaオブジェクトをクリーンアップ
    const existingScripts = document.querySelectorAll('script[src*="google.com/recaptcha"]');
    existingScripts.forEach(script => {
        console.log('Removing existing reCAPTCHA script:', script.src);
        script.remove();
    });
    
    // grecaptchaオブジェクトを削除
    if (typeof grecaptcha !== 'undefined') {
        console.log('Cleaning up existing grecaptcha object');
        delete window.grecaptcha;
    }
    
    // v2スクリプトを動的に読み込み
    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaV2InvisibleLoad&render=explicit';
    
    // グローバルコールバック関数を定義
    window.onRecaptchaV2InvisibleLoad = function() {
        console.log('Google reCAPTCHA v2 script loaded, rendering invisible widget...');
        
        try {
            const widgetId = grecaptcha.render('recaptcha-v2-invisible', {
                'sitekey': siteKey,
                'size': 'invisible',
                'callback': function(token) {
                    console.log('v2 invisible token generated:', token.substring(0, 50) + '...');
                    validateCaptchaToken(token);
                },
                'error-callback': function() {
                    console.error('reCAPTCHA v2 invisible error');
                    showTestResult('error', captchaMessages.v2InvisibleError);
                }
            });
            
            // 自動実行
            setTimeout(() => {
                console.log('Executing reCAPTCHA v2 invisible widget ID:', widgetId);
                grecaptcha.execute(widgetId);
            }, 1000);
        } catch (error) {
            console.error('Failed to render v2 invisible widget:', error);
            showTestResult('error', captchaMessages.v2WidgetRenderFailed + ': ' + error.message);
        }
    };
    
    script.onerror = function() {
        console.error('Failed to load Google reCAPTCHA v2 script');
        showTestResult('error', captchaMessages.v2ScriptLoadFailed);
    };
    
    document.head.appendChild(script);
}

// Google reCAPTCHA v2 checkbox 動的読み込み
function loadGoogleV2CheckboxDynamic(siteKey) {
    console.log('Loading Google reCAPTCHA v2 checkbox dynamically with siteKey:', siteKey.substring(0, 20) + '...');
    
    // ウィジェットコンテナを準備
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = '<div id="recaptcha-v2-checkbox"></div><div class="mt-2 text-sm text-yellow-400">' + captchaMessages.v2CheckboxInstruction + '</div>';
    
    // 既存のreCAPTCHAスクリプトとgrecaptchaオブジェクトをクリーンアップ
    const existingScripts = document.querySelectorAll('script[src*="google.com/recaptcha"]');
    existingScripts.forEach(script => {
        console.log('Removing existing reCAPTCHA script:', script.src);
        script.remove();
    });
    
    // grecaptchaオブジェクトを削除
    if (typeof grecaptcha !== 'undefined') {
        console.log('Cleaning up existing grecaptcha object');
        delete window.grecaptcha;
    }
    
    // v2スクリプトを動的に読み込み
    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaV2CheckboxLoad&render=explicit';
    
    // グローバルコールバック関数を定義
    window.onRecaptchaV2CheckboxLoad = function() {
        console.log('Google reCAPTCHA v2 script loaded, rendering checkbox widget...');
        
        try {
            grecaptcha.render('recaptcha-v2-checkbox', {
                'sitekey': siteKey,
                'callback': function(token) {
                    console.log('v2 checkbox token generated:', token.substring(0, 50) + '...');
                    validateCaptchaToken(token);
                },
                'error-callback': function() {
                    console.error('reCAPTCHA v2 checkbox error');
                    showTestResult('error', captchaMessages.v2CheckboxError);
                }
            });
        } catch (error) {
            console.error('Failed to render v2 checkbox widget:', error);
            showTestResult('error', captchaMessages.v2WidgetRenderFailed + ': ' + error.message);
        }
    };
    
    script.onerror = function() {
        console.error('Failed to load Google reCAPTCHA v2 script');
        showTestResult('error', captchaMessages.v2ScriptLoadFailed);
    };
    
    document.head.appendChild(script);
}

// Cloudflare Turnstile 動的読み込み
function loadTurnstileWidgetDynamic(siteKey) {
    console.log('Loading Cloudflare Turnstile dynamically with siteKey:', siteKey.substring(0, 20) + '...');
    
    // ウィジェットコンテナを準備
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = '<div id="turnstile-widget"></div>';
    
    // Turnstileスクリプトを動的に読み込み
    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    script.onload = function() {
        console.log('Cloudflare Turnstile script loaded, rendering widget...');
        
        turnstile.render('#turnstile-widget', {
            sitekey: siteKey,
            callback: function(token) {
                console.log('Turnstile token generated:', token.substring(0, 50) + '...');
                validateCaptchaToken(token);
            },
            'error-callback': function() {
                console.error('Turnstile error');
                showTestResult('error', captchaMessages.turnstileError);
            }
        });
    };
    script.onerror = function() {
        console.error('Failed to load Cloudflare Turnstile script');
        showTestResult('error', captchaMessages.turnstileScriptLoadFailed);
    };
    document.head.appendChild(script);
}

// Google reCAPTCHA Enterprise ウィジェット読み込み
function loadGoogleEnterpriseWidget() {
    const siteKey = document.getElementById('captcha_site_key').value;
    const projectId = document.getElementById('captcha_google_project_id').value;
    
    if (!siteKey || !projectId) {
        showTestResult('error', captchaMessages.siteKeyProjectIdMissing);
        return;
    }
    
    loadGoogleEnterpriseWidgetDynamic(siteKey, projectId);
}

// Google reCAPTCHA Enterprise 動的読み込み
function loadGoogleEnterpriseWidgetDynamic(siteKey, projectId) {
    console.log('Loading Google reCAPTCHA Enterprise dynamically with siteKey:', siteKey.substring(0, 20) + '...', 'projectId:', projectId);
    
    // Enterprise v3として処理（自動実行）
    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/enterprise.js?render=${siteKey}`;
    script.onload = function() {
        console.log('Google reCAPTCHA Enterprise script loaded, executing...');
        
        grecaptcha.enterprise.ready(function() {
            grecaptcha.enterprise.execute(siteKey, {action: 'validate_settings'}).then(function(token) {
                console.log('Enterprise token generated:', token.substring(0, 50) + '...');
                validateCaptchaToken(token);
            }).catch(function(error) {
                console.error('reCAPTCHA Enterprise execution error:', error);
                showTestResult('error', captchaMessages.enterpriseExecutionFailed + ': ' + error.message);
            });
        });
    };
    script.onerror = function() {
        console.error('Failed to load Google reCAPTCHA Enterprise script');
        showTestResult('error', captchaMessages.enterpriseScriptLoadFailed);
    };
    document.head.appendChild(script);
}

// CAPTCHAトークンをサーバーで検証
function validateCaptchaToken(token) {
    const formData = new FormData();
    const driver = document.getElementById('captcha_driver').value;
    
    formData.append('captcha_driver', driver);
    formData.append('g-recaptcha-response', token);
    
    // 現在のフォーム値を追加
    formData.append('captcha_google_version', document.getElementById('captcha_google_version').value);
    formData.append('captcha_site_key', document.getElementById('captcha_site_key').value);
    formData.append('captcha_secret_key', document.getElementById('captcha_secret_key').value);
    
    // 最小スコアを追加（v3の場合）
    const minScoreElement = document.getElementById('captcha_google_min_score');
    if (minScoreElement && minScoreElement.value) {
        formData.append('captcha_google_min_score', minScoreElement.value);
        console.log('Adding min score to request:', minScoreElement.value);
    }
    
    const projectId = document.getElementById('captcha_google_project_id').value;
    if (projectId) {
        formData.append('captcha_google_project_id', projectId);
        console.log('Adding project ID to request:', projectId);
    }
    
    console.log('Sending CAPTCHA validation request with token:', token.substring(0, 50) + '...');
    
    fetch('{{ route("admin.settings.security.validate-captcha-widget") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server validation response:', data);
        handleCaptchaAuthenticationResult(data.success, data.message);
    })
    .catch(error => {
        console.error('CAPTCHA validation error:', error);
        handleCaptchaAuthenticationResult(false, captchaMessages.serverCommunicationFailed);
    });
}

// リアルタイム監視用の関数（グローバルスコープに移動）
function resetValidationState() {
    captchaValidated = false;
    updateValidationStatus('required');
    
    // hiddenフィールドをリセット
    const statusElement = document.getElementById('captcha-validation-status');
    const tokenElement = document.getElementById('captcha_validation_token');
    if (statusElement) statusElement.value = '0';
    if (tokenElement) tokenElement.value = '';
    
    // 既存のreCAPTCHAウィジェットをクリア
    if (typeof grecaptcha !== 'undefined' && currentCaptchaWidget) {
        try {
            // v2ウィジェットの場合のみリセット
            const version = document.getElementById('captcha_google_version').value;
            if (version !== 'v3') {
                grecaptcha.reset(currentCaptchaWidget);
            }
        } catch (error) {
            console.log('reCAPTCHA reset failed:', error);
        }
    }
    
    // ウィジェットIDをクリア
    currentCaptchaWidget = null;
    
    // ウィジェットを再読み込み
    loadCaptchaWidget();
}

document.addEventListener('DOMContentLoaded', function() {
    // ページ読み込み時にCAPTCHA認証結果をチェックして適切なメッセージを表示
    const authResultInput = document.getElementById('captcha-authentication-result');
    if (authResultInput) {
        if (authResultInput.value === '1') {
            // 認証成功時は統合メッセージ要素で成功状態を表示
            showTestResult('success', captchaMessages.testCompletedSuccessfully);
            console.log('DEBUG: Displayed CAPTCHA success message on page load');
        } else {
            // 認証結果が0の場合はメッセージを非表示
            hideTestResult();
            console.log('DEBUG: Hidden CAPTCHA test result messages on page load due to reset authentication result');
        }
    }
    
    // 初期プロバイダー情報を設定とプロバイダー切り替え時のリセット
    const driverSelect = document.getElementById('captcha_driver');
    if (driverSelect) {
        // 初期プロバイダー情報を設定
        if (driverSelect.value) {
            updateProviderInfo(driverSelect.value);
        }
        
        // プロバイダー切り替え時のリセット
        driverSelect.addEventListener('change', function() {
            const newDriver = this.value;
            
            // プロバイダー情報を更新
            updateProviderInfo(newDriver);
            
            // CAPTCHA認証結果をリセット（プロバイダー変更時は再認証が必要）
            resetCaptchaAuthenticationResult('provider change');
            
            // 認証テスト必要メッセージを表示
            showAuthTestRequiredNotice();
            
            console.log('DEBUG: Provider changed to:', newDriver, '- Reset authentication result and showing test required notice');
        });
    }
    
    // バージョン変更時のウィジェット再読み込み
    const versionSelect = document.getElementById('captcha_google_version');
    if (versionSelect) {
        versionSelect.addEventListener('change', function() {
            console.log('DEBUG: Version change detected, from:', this.dataset.previousValue, 'to:', this.value);
            
            // まず最初にAlpine.jsのcaptchaSettingsChangedを更新してボタンを非表示にする
            try {
                const alpineElement = document.querySelector('[x-data]');
                if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                    console.log('DEBUG: Before Alpine update - captchaVersion:', alpineElement.__x.$data.captchaVersion, 'captchaSettingsChanged:', alpineElement.__x.$data.captchaSettingsChanged);
                    
                    // 設定変更フラグを先に設定してボタンを非表示に
                    alpineElement.__x.$data.captchaSettingsChanged = true;
                    
                    // 少し遅延してからバージョンを更新（Alpine.jsの反応性を制御）
                    setTimeout(() => {
                        alpineElement.__x.$data.captchaVersion = this.value;
                        console.log('DEBUG: After Alpine update - captchaVersion:', alpineElement.__x.$data.captchaVersion, 'captchaSettingsChanged:', alpineElement.__x.$data.captchaSettingsChanged);
                    }, 10);
                }
            } catch (error) {
                console.log('Alpine.js data update failed:', error);
            }
            
            // ボタンの表示状態をチェック
            const button = document.getElementById('captcha-validate-button');
            if (button) {
                console.log('DEBUG: Button display before hide:', window.getComputedStyle(button).display);
                console.log('DEBUG: Button visibility before hide:', window.getComputedStyle(button).visibility);
            }
            
            // バージョン変更時にCAPTCHAテスト結果メッセージを非表示にする
            hideTestResult();
            console.log('DEBUG: Hidden CAPTCHA test result message due to version change');
            
            // バージョン変更時の処理（ボタンは非表示にしない）
            console.log('DEBUG: Version changed, but keeping button visible');
            
            // ボタンの表示状態を再チェック
            if (button) {
                setTimeout(() => {
                    console.log('DEBUG: Button display after hide (delayed):', window.getComputedStyle(button).display);
                    console.log('DEBUG: Button visibility after hide (delayed):', window.getComputedStyle(button).visibility);
                }, 200);
            }
            
            // バージョン変更時にCAPTCHA認証結果を0にリセット
            resetCaptchaAuthenticationResult('version change');
            console.log('DEBUG: Reset captcha-authentication-result to 0 due to version change');
        });
    }
    
    // プロバイダー情報更新関数
    function updateProviderInfo(driver) {
        const providerInfo = getProviderInfo(driver);
        const providerNameElement = document.getElementById('provider-name');
        const providerLinkElement = document.getElementById('provider-setup-link');
        
        if (providerNameElement && providerLinkElement) {
            // プロバイダー名を更新
            providerNameElement.textContent = providerInfo.name;
            
            // リンクのURLを更新
            providerLinkElement.href = providerInfo.url;
            
            // リンクテキストを更新（span要素内のテキストのみ変更し、翻訳部分は保持）
            const spanElement = providerLinkElement.querySelector('span');
            if (spanElement) {
                spanElement.textContent = providerInfo.name;
            }
            
            console.log('DEBUG: Updated provider info - Name:', providerInfo.name, 'URL:', providerInfo.url);
        }
    }
    
    // サイトキー変更監視（リアルタイム）
    const siteKeyInput = document.getElementById('captcha_site_key');
    if (siteKeyInput) {
        let siteKeyOriginalValue = siteKeyInput.value;
        
        siteKeyInput.addEventListener('input', function() {
            if (this.value !== siteKeyOriginalValue) {
                clearCaptchaWidget();
                hideTestResult();
                resetCaptchaAuthenticationResult('site key change');
                showAuthTestRequiredNotice();
            }
            siteKeyOriginalValue = this.value;
        });
    }
    
    // シークレットキー変更監視（リアルタイム）
    const secretKeyInput = document.getElementById('captcha_secret_key');
    if (secretKeyInput) {
        let secretKeyOriginalValue = secretKeyInput.value;
        
        secretKeyInput.addEventListener('input', function() {
            if (this.value !== secretKeyOriginalValue) {
                clearCaptchaWidget();
                hideTestResult();
                resetCaptchaAuthenticationResult('secret key change');
                showAuthTestRequiredNotice();
            }
            secretKeyOriginalValue = this.value;
        });
    }
    
    // 最小スコア変更監視（リアルタイム）
    const minScoreInput = document.getElementById('captcha_google_min_score');
    
    if (minScoreInput) {
        let minScoreOriginalValue = minScoreInput.value;
        
        minScoreInput.addEventListener('input', function() {
            if (this.value !== minScoreOriginalValue) {
                resetCaptchaValidationStatus();
                clearCaptchaWidget();
                hideTestResult();
                resetCaptchaAuthenticationResult('min score change');
                showAuthTestRequiredNotice();
            }
            minScoreOriginalValue = this.value;
        });
    }
    
    // CAPTCHA有効/無効のリアルタイム監視
    setupCaptchaToggleMonitoring();
    
});







// CAPTCHA有効/無効のリアルタイム監視設定
function setupCaptchaToggleMonitoring() {
    const captchaEnabledCheckbox = document.getElementById('captcha_enabled');
    const captchaDriverSelect = document.getElementById('captcha_driver');
    const captchaVersionSelect = document.getElementById('captcha_google_version');
    const siteKeyInput = document.getElementById('captcha_site_key');
    const secretKeyInput = document.getElementById('captcha_secret_key');
    const projectIdInput = document.getElementById('captcha_google_project_id');
    
    // 初期状態を保存
    let initialState = {
        enabled: captchaEnabledCheckbox ? captchaEnabledCheckbox.checked : false,
        driver: captchaDriverSelect ? captchaDriverSelect.value : '',
        version: captchaVersionSelect ? captchaVersionSelect.value : '',
        siteKey: siteKeyInput ? siteKeyInput.value : '',
        secretKey: secretKeyInput ? secretKeyInput.value : '',
        projectId: projectIdInput ? projectIdInput.value : '',
    };
    
    debugCaptcha('Initial CAPTCHA state saved', initialState);
    
    // CAPTCHA有効/無効の変更監視
    if (captchaEnabledCheckbox) {
        captchaEnabledCheckbox.addEventListener('change', function() {
            const currentEnabled = this.checked;
            const previousEnabled = initialState.enabled;
            debugCaptcha('CAPTCHA enabled changed', {from: previousEnabled, to: currentEnabled});
            
            // CAPTCHA有効/無効が変更されたら検証ステータスをリセット
            resetCaptchaValidationStatus();
            
            // 有効/無効の変更時はセクション全体の表示制御（個別要素の非表示は不要）
            
            if (!previousEnabled && currentEnabled) {
                // 無効から有効に変更された場合
                updateValidationStatus('pending');
                showAuthTestRequiredNotice();
            } else if (previousEnabled && !currentEnabled) {
                // 有効から無効に変更された場合
                // ウィジェットをクリア
                clearCaptchaWidget();
                hideAuthTestRequiredNotice();
            }
            
            // 初期状態を更新
        });
    }
    
    // プロバイダー変更監視
    if (captchaDriverSelect) {
        captchaDriverSelect.addEventListener('change', function() {
            if (captchaEnabledCheckbox && captchaEnabledCheckbox.checked) {
                console.log('DEBUG: CAPTCHA driver changed from', initialState.driver, 'to', this.value);
                debugCaptcha('CAPTCHA driver changed', {from: initialState.driver, to: this.value});
                resetCaptchaValidationStatus();
                clearCaptchaWidget();
                resetCaptchaAuthenticationResult('driver change');
                showAuthTestRequiredNotice();
                
                // Alpine.jsの変数をデバッグ
                setTimeout(() => {
                    const alpineElement = document.querySelector('[x-data]');
                    if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                        const data = alpineElement.__x.$data;
                        console.log('DEBUG: Alpine.js variables after driver change:');
                        console.log('  captchaEnabled:', data.captchaEnabled);
                        console.log('  captchaDriver:', data.captchaDriver);
                        console.log('  captchaSiteKey:', data.captchaSiteKey);
                        console.log('  captchaSecretKey:', data.captchaSecretKey);
                        console.log('  captchaProjectId:', data.captchaProjectId);
                        
                        // x-show条件を手動で評価
                        const condition = data.captchaEnabled && data.captchaDriver && data.captchaSiteKey && data.captchaSecretKey && (data.captchaDriver !== 'google_enterprise' || (data.captchaDriver === 'google_enterprise' && data.captchaProjectId));
                        console.log('DEBUG: x-show condition result:', condition);
                        console.log('DEBUG: Enterprise check:', data.captchaDriver === 'google_enterprise', 'Project ID:', data.captchaProjectId);
                    }
                    
                    // ボタンの状態を確認
                    const testButton = document.getElementById('captcha-validate-button');
                    if (testButton) {
                        console.log('DEBUG: Button display after driver change:', window.getComputedStyle(testButton).display);
                        console.log('DEBUG: Button visibility after driver change:', window.getComputedStyle(testButton).visibility);
                        console.log('DEBUG: Button x-show attribute:', testButton.getAttribute('x-show'));
                    }
                }, 100);
            }
        });
    }
    
    // バージョン変更監視
    if (captchaVersionSelect) {
        captchaVersionSelect.addEventListener('change', function() {
            if (captchaEnabledCheckbox && captchaEnabledCheckbox.checked) {
                debugCaptcha('CAPTCHA version changed', {from: initialState.version, to: this.value});
                resetCaptchaValidationStatus();
                clearCaptchaWidget();
                resetCaptchaAuthenticationResult('version change');
                
                // Alpine.jsのcaptchaSettingsChangedフラグを設定
                const alpineElement = document.querySelector('[x-data]');
                if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                    alpineElement.__x.$data.captchaSettingsChanged = true;
                }
                
                showAuthTestRequiredNotice();
            }
        });
    }
    
    // キー変更監視
    const keyInputs = [
        {element: siteKeyInput, key: 'siteKey'},
        {element: secretKeyInput, key: 'secretKey'},
        {element: projectIdInput, key: 'projectId'},
    ];
    
    keyInputs.forEach(({element, key}) => {
        if (element) {
            element.addEventListener('input', function() {
                if (captchaEnabledCheckbox && captchaEnabledCheckbox.checked && this.value !== initialState[key]) {
                    debugCaptcha(`CAPTCHA ${key} changed`, {from: initialState[key], to: this.value});
                    resetCaptchaValidationStatus();
                    clearCaptchaWidget();
                    resetCaptchaAuthenticationResult(`${key} change`);
                    showAuthTestRequiredNotice();
                }
            });
        }
    });
}


// テストボタンを非表示にする関数（デバッグ用）
function hideTestButton() {
    console.trace('DEBUG: hideTestButton() called from:');
    const testButton = document.getElementById('captcha-validate-button');
    if (testButton) {
        testButton.style.display = 'none';
        console.log('DEBUG: hideTestButton() executed - button hidden');
    } else {
        console.log('DEBUG: hideTestButton() called but button not found');
    }
}

// テストボタンを表示する関数
function showTestButton() {
    const testButton = document.getElementById('captcha-validate-button');
    if (testButton) {
        testButton.style.display = 'inline-block';
        debugCaptcha('Test button shown');
    }
}

// CAPTCHAウィジェットをクリアする関数
function clearCaptchaWidget() {
    const container = document.getElementById('captcha-widget-container');
    if (container) {
        container.innerHTML = '';
        debugCaptcha('CAPTCHA widget cleared');
    }
}

// CAPTCHA検証ステータスをリセットする関数
function resetCaptchaValidationStatus() {
    const hiddenInput = document.getElementById('captcha-validation-status');
    if (hiddenInput && hiddenInput.tagName === 'INPUT') {
        hiddenInput.value = '0';
        debugCaptcha('CAPTCHA validation status reset to 0');
    }
}

// テスト必須通知を非表示にする関数
function hideTestRequiredNotice() {
    const notice = document.getElementById('captcha-test-required-notice');
    if (notice) {
        notice.style.display = 'none';
        debugCaptcha('Test required notice hidden');
    }
}

// テスト結果メッセージを非表示にする関数
function hideTestResult() {
    const resultElement = document.getElementById('captcha-test-result');
    if (resultElement) {
        resultElement.style.display = 'none';
    }
    debugCaptcha('Test result messages hidden');
}

// 保存後にCAPTCHAウィジェットを読み込む関数
function loadCaptchaWidgetAfterSave() {
    const enabled = {{ $settings['captcha_enabled'] ? 'true' : 'false' }};
    const driver = '{{ $settings["captcha_driver"] ?? "" }}';
    const siteKey = '{{ $settings["captcha_site_key"] ?? "" }}';
    const secretKey = '{{ $settings["captcha_secret_key"] ?? "" }}';
    const projectId = '{{ $settings["captcha_google_project_id"] ?? "" }}';
    const authResult = {{ $captchaTestResult ? 'true' : 'false' }};
    
    debugCaptcha('Loading CAPTCHA widget after save', {enabled, driver, siteKey: siteKey.substring(0, 20) + '...', authResult});
    
    // 必須条件をチェック
    if (enabled && driver && siteKey && secretKey) {
        // Google Enterprise の場合はプロジェクトIDも必須
        if (driver === 'google_enterprise' && !projectId) {
            debugCaptcha('Google Enterprise requires project ID');
            return;
        }
        
        // 認証が既に成功している場合はウィジェットを読み込まない
        if (authResult) {
            debugCaptcha('Authentication already successful, skipping widget load');
            hideAuthTestRequiredNotice();
            return;
        }
        
        // ウィジェットを読み込み
        if (driver === 'google') {
            loadGoogleRecaptchaWidget();
        } else if (driver === 'google_enterprise') {
            loadGoogleEnterpriseWidget();
        } else if (driver === 'turnstile') {
            loadTurnstileWidget();
        }
        
        // テストボタンを表示（v2_checkboxの場合は除く）
        const version = '{{ $settings["captcha_google_version"] ?? "" }}';
        if (!(driver === 'google' && version === 'v2_checkbox')) {
            showTestButton();
        }
        
        // Alpine.jsのcaptchaSettingsChangedフラグをリセット
        const alpineElement = document.querySelector('[x-data]');
        if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
            alpineElement.__x.$data.captchaSettingsChanged = false;
        }
        
        // 認証テスト必要通知を非表示
        hideAuthTestRequiredNotice();
    }
}

// ページ読み込み時にウィジェットを読み込み（保存済み設定がある場合）
@if($settings['captcha_enabled'] && !empty($settings['captcha_driver']) && !empty($settings['captcha_site_key']) && !empty($settings['captcha_secret_key']))
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(loadCaptchaWidgetAfterSave, 1000); // 1秒後に読み込み
});
@endif

// テスト結果をページに表示する関数
function showTestResult(type, message) {
    // 既存の結果表示を非表示
    hideTestResult();
    
    const resultElement = document.getElementById('captcha-test-result');
    const iconElement = document.getElementById('captcha-test-icon');
    const titleElement = document.getElementById('captcha-test-title');
    const messageElement = document.getElementById('captcha-test-message');
    const hintElement = document.getElementById('captcha-test-hint');
    
    if (resultElement && iconElement && titleElement && messageElement) {
        // メッセージとタイトルを設定
        messageElement.textContent = message;
        
        if (type === 'success') {
            // 成功時のスタイル設定
            resultElement.className = 'mb-4 p-3 border rounded-lg bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200';
            iconElement.className = 'fas fa-check-circle mr-3 text-green-600 dark:text-green-400';
            titleElement.textContent = captchaMessages.authenticationSuccessTitle;
            
            // ヒントメッセージを表示
            if (hintElement && captchaMessages.testCompletedHint) {
                hintElement.innerHTML = captchaMessages.testCompletedHint;
                hintElement.style.display = 'block';
            }
        } else {
            // 失敗時のスタイル設定
            resultElement.className = 'mb-4 p-3 border rounded-lg bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200';
            iconElement.className = 'fas fa-times-circle mr-3 text-red-600 dark:text-red-400';
            titleElement.textContent = captchaMessages.authenticationFailedTitle;
            
            // ヒントメッセージを非表示
            if (hintElement) {
                hintElement.style.display = 'none';
            }
        }
        
        resultElement.style.display = 'block';
    }
    
    debugCaptcha('Test result displayed', {type, message});
}

// 認証ボタンを非表示にする
function hideAuthenticationButton() {
    const button = document.getElementById('captcha-validate-button');
    if (button) {
        button.style.display = 'none';
        debugCaptcha('Authentication button hidden');
    }
}


// 成功時のバリデーション状態更新
function onCaptchaValidationSuccess() {
    updateValidationStatus('passed');
    debugCaptcha('CAPTCHA validation successful - status updated to passed');
    
    // Hidden inputの値を1に更新
    const hiddenInput = document.getElementById('captcha-authentication-result');
    console.log('DEBUG: Looking for hidden input element:', hiddenInput);
    if (hiddenInput) {
        const oldValue = hiddenInput.value;
        hiddenInput.value = '1';
        console.log('DEBUG: Updated captcha_authentication_result hidden input from:', oldValue, 'to:', hiddenInput.value);
        
        // 値が正しく設定されたか確認
        const newValue = document.getElementById('captcha-authentication-result').value;
        console.log('DEBUG: Verification - current value is:', newValue);
    } else {
        console.error('DEBUG: Hidden input element not found! Available elements:');
        console.log('DEBUG: All hidden inputs:', document.querySelectorAll('input[type="hidden"]'));
    }
}

// 失敗時のバリデーション状態更新  
function onCaptchaValidationFailure() {
    updateValidationStatus('failed');
    debugCaptcha('CAPTCHA validation failed - status updated to failed');
    
    // Hidden inputの値を0に更新
    const hiddenInput = document.getElementById('captcha-authentication-result');
    console.log('DEBUG: Looking for hidden input element (failure):', hiddenInput);
    if (hiddenInput) {
        const oldValue = hiddenInput.value;
        hiddenInput.value = '0';
        console.log('DEBUG: Updated captcha_authentication_result hidden input from:', oldValue, 'to:', hiddenInput.value);
        
        // 値が正しく設定されたか確認
        const newValue = document.getElementById('captcha-authentication-result').value;
        console.log('DEBUG: Verification - current value is:', newValue);
    } else {
        console.error('DEBUG: Hidden input element not found! Available elements:');
        console.log('DEBUG: All hidden inputs:', document.querySelectorAll('input[type="hidden"]'));
    }
}

// CAPTCHA authentication result hidden inputを0にリセットする関数
function resetCaptchaAuthenticationResult(reason) {
    const authResultInput = document.getElementById('captcha-authentication-result');
    if (authResultInput) {
        authResultInput.value = '0';
        console.log(`DEBUG: Reset captcha_authentication_result to 0 due to ${reason}`);
    }
    
    // 統合されたテスト結果メッセージを非表示にする
    hideTestResult();
    console.log('DEBUG: Hidden CAPTCHA test result message due to settings change');
}

// ファイル整合性チェック用Alpine.jsコンポーネント
function fileIntegrityChecker() {
    return {
        isScanning: false,
        isRegenerating: false,
        scanResult: null,
        errorMessage: null,

        async runScan() {
            this.isScanning = true;
            this.scanResult = null;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.settings.security.scan-integrity") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.scanResult = data.audit;
                } else {
                    this.errorMessage = data.message || '{{ __("admin.settings.security.file_integrity.scan_failed") }}';
                }
            } catch (error) {
                console.error('Scan error:', error);
                this.errorMessage = '{{ __("admin.settings.security.file_integrity.scan_failed") }}';
            } finally {
                this.isScanning = false;
            }
        },

        async regenerateBaseline() {
            if (!confirm('{{ __("admin.settings.security.file_integrity.regenerate_confirm") }}')) {
                return;
            }

            this.isRegenerating = true;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route("admin.settings.security.regenerate-baseline") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // ページをリロードして最新の情報を表示
                    window.location.reload();
                } else {
                    this.errorMessage = data.message || '{{ __("admin.settings.security.file_integrity.regenerate_failed") }}';
                }
            } catch (error) {
                console.error('Regenerate error:', error);
                this.errorMessage = '{{ __("admin.settings.security.file_integrity.regenerate_failed") }}';
            } finally {
                this.isRegenerating = false;
            }
        },

        getScanResultClass() {
            if (!this.scanResult) return '';
            const status = this.scanResult.status;
            if (status === 'ok') return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            if (status === 'warning') return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        },

        getScanResultIcon() {
            if (!this.scanResult) return '';
            const status = this.scanResult.status;
            if (status === 'ok') return 'fas fa-check-circle text-green-600 dark:text-green-400';
            if (status === 'warning') return 'fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400';
            return 'fas fa-times-circle text-red-600 dark:text-red-400';
        },

        getScanResultTextClass() {
            if (!this.scanResult) return '';
            const status = this.scanResult.status;
            if (status === 'ok') return 'text-green-800 dark:text-green-200';
            if (status === 'warning') return 'text-yellow-800 dark:text-yellow-200';
            return 'text-red-800 dark:text-red-200';
        }
    };
}

</script>
@endpush