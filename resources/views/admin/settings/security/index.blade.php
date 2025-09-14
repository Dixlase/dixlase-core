{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')

<div x-data="{
    enableAllowedIPs: {{ old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']) ? 'true' : 'false' }},
    blockedAdminIps: {{ old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']) ? 'true' : 'false' }},
    enableAllowedFrontIPs: {{ old('enable_allowed_front_ips', $settings['enable_allowed_front_ips']) ? 'true' : 'false' }},
    enableBlockedFrontIps: {{ old('enable_blocked_front_ips', $settings['enable_blocked_front_ips']) ? 'true' : 'false' }},
    captchaEnabled: {{ old('captcha_enabled', $settings['captcha_enabled']) ? 'true' : 'false' }},
    captchaEnabledSaved: {{ $settings['captcha_enabled'] ? 'true' : 'false' }},
    captchaDriver: '{{ old('captcha_driver', $settings['captcha_driver']) }}',
    captchaVersion: '{{ old('captcha_google_version', $settings['captcha_google_version']) }}',
}">
    <form id="security-settings-form" method="POST" action="{{ route('admin.settings.security.update') }}" onsubmit="debugFormSubmission(event)">
        @csrf
        @method('POST')
        
        <!-- Hidden field to track CAPTCHA authentication result -->
        <input type="hidden" id="captcha-authentication-result" name="captcha_authentication_result" value="{{ $captchaTestResult ? '1' : '0' }}">
        
        <!-- CAPTCHA test required notice for enabled CAPTCHA -->
        @if($settings['captcha_enabled'] && !$captchaTestResult)
        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                    {{ __('admin.security.validation.captcha_test_required') }}
                </p>
            </div>
        </div>
        @endif
        
        <input type="hidden" id="captcha_validation_token" name="captcha_validation_token" value="{{ session('captcha_just_saved') ? 'saved_token' : '' }}">
        
        <!-- システムエラー通知設定 -->
        <div class="my-6 pt-6">
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.security.error_notification_settings') }}</h2>
            <p class="text-sm mb-4">
                {{ __('admin.settings.security.error_notification_settings_description') }}
            </p>

            <!-- エラー通知機能の有効/無効 -->
            <div class="mt-4">
                @include('components::form.label', [
                    'text' => __('admin.settings.security.notification_enabled'),
                ])
                @include('components::form.hidden', [
                    'id' => 'notification_enabled',
                    'name' => 'notification_enabled',
                    'value' => '0'
                ])
                @include('components::form.radio-group', [
                    'name' => 'notification_enabled',
                    'options' => [
                        1 => __('admin.settings.security.yes'),
                        0 => __('admin.settings.security.no')
                    ],
                    'value' => $settings['notification_enabled'] ?? 0,
                ])
                <p class="text-sm mt-1">{{ __('admin.settings.security.notification_enabled_help') }}</p>
            </div>

            <!-- 通知するログレベル -->
            <div class="mt-6" x-data="{ enabled: {{ ($settings['notification_enabled'] ?? 0) ? 'true' : 'false' }} }" x-init="
                // ラジオボタンの変更を監視
                document.querySelectorAll('input[name=notification_enabled]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        enabled = radio.value === '1';
                    });
                });
            ">
                @include('components::form.label', [
                    'text' => __('admin.settings.security.notification_log_levels'),
                ])
                <div class="mt-2 space-y-2">
                    @php
                        $logLevelOptions = [];
                        foreach (\App\Enums\LogLevel::getNotificationLevels() as $level) {
                            $levelString = \App\Enums\LogLevel::from($level)->toString();
                            $logLevelOptions[$level] = 'admin.settings.security.log_levels.' . $levelString;
                        }
                    @endphp
                    @include('components::form.checkbox-group', [
                        'name' => 'notification_log_levels',
                        'options' => $logLevelOptions,
                        'values' => $settings['notification_log_levels'] ?? \App\Enums\LogLevel::getDefaultNotificationLevels(),
                        'disabled' => false,
                        'flexDirection' => 'col'
                    ])
                </div>
                <p class="text-sm  mt-3">{{ __('admin.settings.security.notification_log_levels_help') }}</p>
            </div>

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-green-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-sm"></i>
                        </div>
                        <div class="ml-2">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                {!! __('admin.settings.security.error_notification_mail_test_required', ['url' => route('admin.settings.base')]) !!}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- reCAPTCHA設定 -->
        <div class="my-6">
            <h2 class="{{ config('admin.appearance_class.heading.h2') }}">{{ __('admin.settings.security.recaptcha_settings') }}</h2>
            
            @include('components::form.checkbox', [
                'label' => __('admin.settings.security.captcha_enabled'),
                'id' => 'captcha_enabled',
                'name' => 'captcha_enabled',
                'value' => old('captcha_enabled', $settings['captcha_enabled']),
                'xModel' => 'captchaEnabled'
            ])
            
            <!-- CAPTCHA有効化時のメッセージ -->
            <div id="captcha-enabled-notice" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg" style="display: none;">
                <div class="flex items-center text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium">CAPTCHAを有効にしました</span>
                </div>
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    各項目をすべて入力してから保存し、保存後に認証テストを行ってください。
                </div>
            </div>

            <div class="mt-4 space-y-4">
                @include('components::form.label', [
                    'for' => 'captcha_driver',
                    'text' => __('admin.settings.security.captcha_driver'),
                ])
                @include('components::form.select', [
                    'label' => __('admin.settings.security.captcha_driver'),
                    'id' => 'captcha_driver',
                    'name' => 'captcha_driver',
                    'value' => old('captcha_driver', $settings['captcha_driver']),
                    'options' => [
                        'google' => 'Google reCAPTCHA (v2/v3)',
                        'google_enterprise' => 'Google reCAPTCHA Enterprise',
                        'turnstile' => 'Cloudflare Turnstile'
                    ],
                    'xModel' => 'captchaDriver'
                ])

                <!-- Common CAPTCHA Settings -->
                @include('components::form.label', [
                    'for' => 'captcha_site_key',
                    'text' => __('admin.settings.security.captcha_site_key'),
                ])
                @include('components::form.text', [
                    'id' => 'captcha_site_key',
                    'name' => 'captcha_site_key',
                    'value' => old('captcha_site_key', $settings['captcha_site_key'] ?? ''),
                    'type' => 'password',
                ])

                @include('components::form.label', [
                    'for' => 'captcha_secret_key',
                    'text' => __('admin.settings.security.captcha_secret_key'),
                ])
                @include('components::form.text', [
                    'id' => 'captcha_secret_key',
                    'name' => 'captcha_secret_key',
                    'value' => old('captcha_secret_key', $settings['captcha_secret_key'] ?? ''),
                    'type' => 'password',
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
                </div>

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
                    @include('components::form.label', [
                        'for' => 'captcha_google_project_id',
                        'text' => __('admin.settings.security.captcha_google_project_id'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_project_id',
                        'name' => 'captcha_google_project_id',
                        'value' => old('captcha_google_project_id', $settings['captcha_google_project_id']),
                        'placeholder' => 'your-gcp-project-id'
                    ])
                </div>

                <!-- Min Score for Google v3 and Enterprise -->
                <div x-show="(captchaDriver === 'google' && captchaVersion === 'v3') || captchaDriver === 'google_enterprise'" class="mt-4">
                    @include('components::form.label', [
                        'for' => 'captcha_google_min_score',
                        'text' => __('admin.settings.security.captcha_google_min_score'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_min_score',
                        'name' => 'captcha_google_min_score',
                        'value' => old('captcha_google_min_score', $settings['captcha_google_min_score']),
                        'type' => 'number',
                        'step' => '0.1',
                    ])
                    <p class="text-sm text-gray-200 mt-1">
                        {{ __('admin.settings.security.captcha_min_score_description') }}
                    </p>
                </div>

                <!-- CAPTCHA設定保存後のテスト -->
                <div class="mt-6" x-show="captchaEnabled && captchaEnabledSaved" id="captcha-test-section">
                    <!-- CAPTCHA Test Section (Only shown when CAPTCHA is enabled) -->
                    <div class="mt-4 p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                        <!-- Test Status Display -->
                        @if(!$captchaTestResult)
                        <div id="captcha-test-required-notice" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                            <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span class="text-sm font-medium">CAPTCHAを使用するには認証テストが必要です。</span>
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                <ul>
                                    <li>認証が成功したら再度保存してください。</li>
                                    <li>認証に失敗する場合は設定内容をご確認ください。</li>
                                    <li>設定内容を変更した場合には再度保存してから認証テストを行ってください。</li>
                                </ul>
                            </div>
                        </div>
                        @endif

                        <!-- CAPTCHA Test Success Display (from DB) -->
                        
                        <!-- CAPTCHA Widget Container -->
                        <div id="captcha-widget-container" class="mb-4">
                            <!-- Dynamic CAPTCHA widget will be loaded here -->
                        </div>
                        
                        
                        <!-- Settings Change Notice -->
                        <div id="settings-change-notice" class="mb-4 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded" style="display: none;">
                            <div class="flex items-center text-orange-600 dark:text-orange-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span class="text-sm font-medium">設定が変更されました</span>
                            </div>
                            <div class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                CAPTCHAの設定を変更する場合には一度保存し、保存後に認証テストを行ってください。
                            </div>
                        </div>
                        
                        <!-- CAPTCHA Test Result Message (Unified) -->
                        <div id="captcha-test-result" class="mb-4 p-3 border rounded-lg" style="display: none;">
                            <div class="flex items-center">
                                <i id="captcha-test-icon" class="mr-3"></i>
                                <div>
                                    <h4 id="captcha-test-title" class="font-semibold"></h4>
                                    <p id="captcha-test-message" class="text-sm mt-1"></p>
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
                                @if(!$settings['captcha_enabled'] || empty($settings['captcha_driver']) || empty($settings['captcha_site_key']) || empty($settings['captcha_secret_key']) || ($settings['captcha_driver'] === 'google_enterprise' && empty($settings['captcha_google_project_id'])))
                                style="display: none;"
                                @endif>
                            {{ __('admin.settings.security.captcha_validate_button') }}
                        </button>
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
        </div>


        <!-- IPアクセス制御設定 -->
        <div class="mt-8 border-t pt-6">
            <h2 class="{{ config('admin.appearance_class.heading.h2') }}">{{ __('admin.settings.security.ip_access_control') }}</h2>
            <h3>{{ __('admin.settings.security.admin_ip_access_control') }}</h3>
            <div>
                <div class="my-4">
                    <div class="my-4">
                        @include('components::form.checkbox', [
                            'label' => __('admin.settings.security.enable_allowed_admin_ips'),
                            'id' => 'enable_allowed_admin_ips',
                            'name' => 'enable_allowed_admin_ips',
                            'value' => old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']),
                            'xModel' => 'enableAllowedIPs', // Alpine.jsに状態をバインド
                            
                        ])
                    </div>
            
                    @include('components::form.textarea', [
                        'id' => 'allowed_admin_ips',
                        'name' => 'allowed_admin_ips',
                        'value' => $settings['allowed_admin_ips'],
                        'rows' => 10,
                        'placeholder' => '',
                        'class' => '',
                    ])
                </div>

                <div class="my-4">
                    <div class="my-4">
                        @include('components::form.checkbox', [
                            'label' => __('admin.settings.security.enable_blocked_admin_ips'),
                            'id' => 'enable_blocked_admin_ips',
                            'name' => 'enable_blocked_admin_ips',
                            'value' => old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']),
                            'xModel' => 'blockedAdminIps', // Alpine.jsに状態をバインド
                        ])
                    </div>

                    @include('components::form.textarea', [
                        'id' => 'blocked_admin_ips',
                        'name' => 'blocked_admin_ips',
                        'value' => $settings['blocked_admin_ips'],
                        'rows' => 10,
                        'placeholder' => '',
                        'required' => false,
                        'class' => '',
                    ])
                </div>
            </div>

            <h3>{{ __('admin.settings.security.front_ip_access_control') }}</h3>
            <div>
                <div class="my-4">
                    <div class="my-4">
                        @include('components::form.checkbox', [
                            'label' => __('admin.settings.security.enable_allowed_front_ips'),
                            'id' => 'enable_allowed_front_ips',
                            'name' => 'enable_allowed_front_ips',
                            'value' => old('enable_allowed_front_ips', $settings['enable_allowed_front_ips']),
                            'xModel' => 'enableAllowedFrontIPs', // Alpine.jsに状態をバインド
                        ])
                    </div>
                

                    @include('components::form.textarea', [
                        'id' => 'allowed_front_ips',
                        'name' => 'allowed_front_ips',
                        'value' => $settings['allowed_front_ips'],
                        'rows' => 10,
                        'placeholder' => '',
                        'class' => '',
                    ])
                </div>

                <div class="my-4">
                    <div class="my-4">
                        @include('components::form.checkbox', [
                            'label' => __('admin.settings.security.enable_blocked_front_ips'),
                            'id' => 'enable_blocked_front_ips',
                            'name' => 'enable_blocked_front_ips',
                            'value' => old('enable_blocked_front_ips', $settings['enable_blocked_front_ips']),
                            'xModel' => 'enableBlockedFrontIps', // Alpine.jsに状態をバインド
                        ])
                    </div>

                    @include('components::form.textarea', [
                        'id' => 'blocked_front_ips',
                        'name' => 'blocked_front_ips',
                        'value' => $settings['blocked_front_ips'],
                        'rows' => 10,
                        'placeholder' => '',
                        'required' => false,
                        'class' => '',
                    ])
                </div>
            </div>
        </div>
    </form>
</div>


@endsection



@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.security.save_button'),
        'onclick' => "validateBeforeSave()",
        'title' => __('admin.settings.security.save_confirmation_title'),
        'message' => __('admin.settings.security.save_confirmation_message'),
        'confirm_label' => __('admin.settings.security.save_button'),
        'cancel_label' => __('admin.settings.security.back_button'),
        'form' => 'security-settings-form',
    ])
@endsection


@push('scripts')

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
        showTestResult('error', 'CAPTCHA検証中にエラーが発生しました。');
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
        button.title = reason || 'キーエラーのため無効化されています';
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
        container.innerHTML = '<p class="text-sm text-gray-500">サイトキーを入力してください</p>';
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
            container.innerHTML = '<div class="text-red-600 text-sm">v3サイトキーが無効です。正しいv3用のキーを入力してください。<br>現在のキー: ' + siteKey.substring(0, 20) + '...</div>';
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
            
            // キー形式の事前チェックを削除 - プロバイダー/バージョン選択に基づいてウィジェットを読み込み
            
            // v3ウィジェットを配置 - キーの有効性に関係なく表示
            currentCaptchaWidget = 'v3';
            container.innerHTML = `
                <div class="text-sm text-gray-600">reCAPTCHA v3が読み込まれました。認証ボタンをクリックしてテストしてください。</div>
                <input type="hidden" id="g-recaptcha-response-v3" name="g-recaptcha-response" value="">
            `;
            debugCaptcha('v3 widget placed', {siteKey: siteKey.substring(0, 20) + '...'});
        });
    } catch (error) {
        console.error('reCAPTCHA v3 initialization error:', error);
        // エラーが発生してもウィジェットを配置
        currentCaptchaWidget = 'v3';
        container.innerHTML = `
            <div class="text-sm text-gray-600">reCAPTCHA v3が読み込まれました。認証ボタンをクリックしてテストしてください。</div>
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
        container.innerHTML = '<p class="text-sm text-gray-500">サイトキーを入力してください</p>';
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
                    <span class="text-sm">CAPTCHA認証が期限切れです。再度実行してください。</span>
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

// CAPTCHAウィジェット検証を実行
function validateCaptchaWidget() {
    const driver = document.getElementById('captcha_driver').value;
    const version = document.getElementById('captcha_google_version').value;
    
    console.log('validateCaptchaWidget called:', {driver, version, currentCaptchaWidget});
    
    if (driver === 'google' && version === 'v3') {
        // v3の場合は手動で実行
        const siteKey = document.getElementById('captcha_site_key').value;
        console.log('Executing v3 with siteKey:', siteKey.substring(0, 20) + '...');
        
        // v3実行前にエラーハンドラーを設定
        const v3ErrorHandler = function(event) {
            if (event.message && event.message.includes('Invalid site key')) {
                console.error('Caught v3 execution error:', event.message);
                updateValidationStatus('failed');
                alert('v3サイトキーが無効です。正しいv3用のキーを入力してください。\n現在のキー: ' + siteKey.substring(0, 20) + '...');
                window.removeEventListener('error', v3ErrorHandler);
                event.preventDefault();
                return true;
            }
        };
        window.addEventListener('error', v3ErrorHandler);
        
        grecaptcha.ready(function() {
            grecaptcha.execute(siteKey, {action: 'validate_settings'}).then(function(token) {
                console.log('v3 execution successful, token received');
                debugCaptcha('v3 token generated successfully', {tokenLength: token.length, action: 'validate_settings'});
                
                const responseElement = document.getElementById('g-recaptcha-response-v3');
                if (responseElement) {
                    responseElement.value = token;
                    debugCaptcha('Starting server-side validation', {token: token.substring(0, 50) + '...'});
                    validateCaptchaToken(token);
                } else {
                    console.error('g-recaptcha-response-v3 element not found');
                    debugCaptcha('ERROR: g-recaptcha-response-v3 element not found');
                    updateValidationStatus('failed');
                    alert('CAPTCHA要素が見つかりません。ページを再読み込みしてください。');
                }
                window.removeEventListener('error', v3ErrorHandler);
            }).catch(function(error) {
                console.error('reCAPTCHA v3 execution error:', error);
                updateValidationStatus('failed');
                alert('reCAPTCHA v3の実行に失敗しました。サイトキーが正しいか確認してください。\n現在のキー: ' + siteKey.substring(0, 20) + '...');
                window.removeEventListener('error', v3ErrorHandler);
            });
        });
    } else if (driver === 'google' && version === 'v2_invisible') {
        // v2非表示の場合は手動で実行
        const siteKey = document.getElementById('captcha_site_key').value;
        debugCaptcha('v2 invisible validation started', {siteKey: siteKey.substring(0, 20) + '...', currentCaptchaWidget});
        
        // キー形式チェックを削除 - プロバイダー/バージョン選択に基づいて処理
        
        // currentCaptchaWidgetが正しく設定されているかチェック
        if (!currentCaptchaWidget || currentCaptchaWidget === 0) {
            debugCaptcha('Invalid currentCaptchaWidget detected', {currentCaptchaWidget});
            updateValidationStatus('failed');
            disableValidationButton('CAPTCHAウィジェットが正しく初期化されていません');
            alert('CAPTCHAウィジェットが正しく初期化されていません。ページを再読み込みしてください。');
            return;
        }
        
        grecaptcha.ready(function() {
            grecaptcha.execute(currentCaptchaWidget);
        });
    } else if (driver === 'google' && version === 'v2_checkbox') {
        // v2チェックボックスの場合はレスポンスをチェック
        const siteKey = document.getElementById('captcha_site_key').value;
        debugCaptcha('v2 checkbox validation started', {siteKey: siteKey.substring(0, 20) + '...', currentCaptchaWidget});
        
        // キー形式チェックを削除 - プロバイダー/バージョン選択に基づいて処理
        
        const token = grecaptcha.getResponse(currentCaptchaWidget);
        if (token) {
            validateCaptchaToken(token);
        } else {
            updateValidationStatus('failed');
            alert('チェックボックスをクリックしてCAPTCHA認証を完了してください。');
        }
    } else if (driver === 'turnstile') {
        // Turnstileの場合は既に検証済み
        if (captchaValidated) {
            updateValidationStatus('success');
        } else {
            updateValidationStatus('failed');
        }
    }
}

// CAPTCHAトークンをサーバーで検証
function validateCaptchaToken(token) {
    const formData = new FormData();
    const driver = document.getElementById('captcha_driver').value;
    
    formData.append('captcha_driver', driver);
    formData.append('g-recaptcha-response', token);
    
    if (driver === 'google') {
        formData.append('captcha_site_key', document.getElementById('captcha_site_key').value);
        formData.append('captcha_secret_key', document.getElementById('captcha_secret_key').value);
        formData.append('captcha_google_version', document.getElementById('captcha_google_version').value);
        
        // v3の場合はmin_scoreも送信
        const version = document.getElementById('captcha_google_version').value;
        if (version === 'v3') {
            formData.append('captcha_google_min_score', document.getElementById('captcha_google_min_score').value);
        }
    }
    
    fetch('@php echo route("admin.settings.security.validate-captcha-widget"); @endphp', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        debugCaptcha('Server response received', {status: response.status, ok: response.ok});
        return response.json();
    })
    .then(data => {
        debugCaptcha('Server validation result', {success: data.success, message: data.message});
        
        if (data.success) {
            captchaValidated = true;
            updateValidationStatus('success');
            debugCaptcha('CAPTCHA validation SUCCESS', {token: token.substring(0, 20) + '...'});
            
            // 成功メッセージを表示
            showTestResult('success', data.message || 'CAPTCHA認証が成功しました。');
            
            // hiddenフィールドに検証結果を保存
            const statusElement = document.getElementById('captcha-validation-status');
            const tokenElement = document.getElementById('captcha_validation_token');
            if (statusElement) statusElement.value = '1';
            if (tokenElement) tokenElement.value = token;
            
            // CAPTCHA authentication result hidden inputを更新
            const authResultInput = document.getElementById('captcha-authentication-result');
            console.log('DEBUG: Looking for auth result input:', authResultInput);
            if (authResultInput) {
                const oldValue = authResultInput.value;
                authResultInput.value = '1';
                console.log('DEBUG: Updated captcha_authentication_result from:', oldValue, 'to:', authResultInput.value);
            } else {
                console.error('DEBUG: captcha-authentication-result input not found!');
            }
            
            // 認証成功時に警告通知を非表示にする
            hideTestRequiredNotice();
        } else {
            debugCaptcha('CAPTCHA validation FAILED', {message: data.message, error: data.error});
            resetValidationState();
            
            // CAPTCHA authentication result hidden inputを失敗に更新
            const authResultInput = document.getElementById('captcha-authentication-result');
            console.log('DEBUG: Looking for auth result input (failure):', authResultInput);
            if (authResultInput) {
                const oldValue = authResultInput.value;
                authResultInput.value = '0';
                console.log('DEBUG: Updated captcha_authentication_result from:', oldValue, 'to:', authResultInput.value);
            } else {
                console.error('DEBUG: captcha-authentication-result input not found!');
            }
            
            // 失敗メッセージを表示
            showTestResult('error', data.message || 'CAPTCHA認証に失敗しました。');
        }
    })
    .catch(error => {
        console.error('CAPTCHA validation error:', error);
        debugCaptcha('CAPTCHA validation ERROR', {error: error.message});
        resetValidationState();
        
        // CAPTCHA authentication result hidden inputをエラーに更新
        const authResultInput = document.getElementById('captcha-authentication-result');
        if (authResultInput) {
            authResultInput.value = '0';
            console.log('DEBUG: Updated captcha_authentication_result to 0 due to error');
        }
        
        // エラーメッセージを表示
        showTestResult('error', 'CAPTCHA認証中にエラーが発生しました。');
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
            showTestResult('success', 'CAPTCHAの認証テストが正常に完了しています。');
            console.log('DEBUG: Displayed CAPTCHA success message on page load');
        } else {
            // 認証結果が0の場合はメッセージを非表示
            hideTestResult();
            console.log('DEBUG: Hidden CAPTCHA test result messages on page load due to reset authentication result');
        }
    }
    
    // プロバイダー切り替え時のリセット
    const driverSelect = document.getElementById('captcha_driver');
    if (driverSelect) {
        driverSelect.addEventListener('change', function() {
            const newDriver = this.value;
            
            // プロバイダー情報を更新
            updateProviderInfo(newDriver);
        });
    }
    
    // バージョン変更時のウィジェット再読み込み
    const versionSelect = document.getElementById('captcha_google_version');
    if (versionSelect) {
        versionSelect.addEventListener('change', function() {
            // Alpine.jsのcaptchaVersionも更新
            try {
                const alpineElement = document.querySelector('[x-data]');
                if (alpineElement && alpineElement.__x && alpineElement.__x.$data) {
                    alpineElement.__x.$data.captchaVersion = this.value;
                }
            } catch (error) {
                console.log('Alpine.js data update failed:', error);
            }
        });
    }
    
    // プロバイダー情報更新関数
    function updateProviderInfo(driver) {
        const providerInfo = getProviderInfo(driver);
        const providerNameElement = document.getElementById('provider-name');
        const providerLinkElement = document.getElementById('provider-setup-link');
        
        if (providerNameElement && providerLinkElement) {
            providerNameElement.textContent = providerInfo.name;
            providerLinkElement.href = providerInfo.url;
            providerLinkElement.textContent = `(${providerInfo.name}の設定を確認)`;
        }
    }
    
    // サイトキー変更監視（リアルタイム）
    const siteKeyInput = document.getElementById('captcha_site_key');
    if (siteKeyInput) {
        let siteKeyOriginalValue = siteKeyInput.value;
        
        siteKeyInput.addEventListener('input', function() {
            if (this.value !== siteKeyOriginalValue) {
                hideTestButton();
                clearCaptchaWidget();
                hideTestRequiredNotice();
                hideTestResult();
                resetCaptchaAuthenticationResult('site key change');
                
                const settingsNotice = document.getElementById('settings-change-notice');
                if (settingsNotice) {
                    settingsNotice.style.display = 'block';
                }
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
                hideTestButton();
                clearCaptchaWidget();
                hideTestRequiredNotice();
                hideTestResult();
                resetCaptchaAuthenticationResult('secret key change');
                
                const settingsNotice = document.getElementById('settings-change-notice');
                if (settingsNotice) {
                    settingsNotice.style.display = 'block';
                }
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
                hideTestButton();
                clearCaptchaWidget();
                hideTestRequiredNotice();
                hideTestResult();
                resetCaptchaAuthenticationResult('min score change');
                
                const settingsNotice = document.getElementById('settings-change-notice');
                if (settingsNotice) {
                    settingsNotice.style.display = 'block';
                }
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
                const captchaNotice = document.getElementById('captcha-enabled-notice');
                if (captchaNotice) {
                    captchaNotice.style.display = 'block';
                }
                updateValidationStatus('pending');
            } else if (previousEnabled && !currentEnabled) {
                // 有効から無効に変更された場合
                const captchaNotice = document.getElementById('captcha-enabled-notice');
                if (captchaNotice) {
                    captchaNotice.style.display = 'none';
                }
                // ウィジェットをクリア
                clearCaptchaWidget();
            }
            
            // 初期状態を更新
        });
    }
    
    // プロバイダー変更監視
    if (captchaDriverSelect) {
        captchaDriverSelect.addEventListener('change', function() {
            if (captchaEnabledCheckbox && captchaEnabledCheckbox.checked) {
                debugCaptcha('CAPTCHA driver changed', {from: initialState.driver, to: this.value});
                resetCaptchaValidationStatus();
                hideTestButton();
                clearCaptchaWidget();
                hideTestRequiredNotice();
                const settingsNotice = document.getElementById('settings-change-notice');
                if (settingsNotice) {
                    settingsNotice.style.display = 'block';
                }
            }
        });
    }
    
    // バージョン変更監視
    if (captchaVersionSelect) {
        captchaVersionSelect.addEventListener('change', function() {
            if (captchaEnabledCheckbox && captchaEnabledCheckbox.checked) {
                debugCaptcha('CAPTCHA version changed', {from: initialState.version, to: this.value});
                resetCaptchaValidationStatus();
                hideTestButton();
                clearCaptchaWidget();
                hideTestRequiredNotice();
                const settingsNotice = document.getElementById('settings-change-notice');
                if (settingsNotice) {
                    settingsNotice.style.display = 'block';
                }
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
                    hideTestButton();
                    clearCaptchaWidget();
                    hideTestRequiredNotice();
                    resetCaptchaAuthenticationResult(`${key} change`);
                    
                    // Show settings change notice inline
                    const settingsNotice = document.getElementById('settings-change-notice');
                    if (settingsNotice) {
                        settingsNotice.style.display = 'block';
                    }
                }
            });
        }
    });
}


// テストボタンを非表示にする関数
function hideTestButton() {
    const testButton = document.getElementById('captcha-validate-button');
    if (testButton) {
        testButton.style.display = 'none';
        debugCaptcha('Test button hidden');
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
    
    debugCaptcha('Loading CAPTCHA widget after save', {enabled, driver, siteKey: siteKey.substring(0, 20) + '...'});
    
    // 必須条件をチェック
    if (enabled && driver && siteKey && secretKey) {
        // Google Enterprise の場合はプロジェクトIDも必須
        if (driver === 'google_enterprise' && !projectId) {
            debugCaptcha('Google Enterprise requires project ID');
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
        
        // テストボタンを表示
        showTestButton();
        
        // 設定変更通知を非表示
        const settingsNotice = document.getElementById('settings-change-notice');
        if (settingsNotice) {
            settingsNotice.style.display = 'none';
        }
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
    
    if (resultElement && iconElement && titleElement && messageElement) {
        // メッセージとタイトルを設定
        messageElement.textContent = message;
        
        if (type === 'success') {
            // 成功時のスタイル設定
            resultElement.className = 'mb-4 p-3 border rounded-lg bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200';
            iconElement.className = 'fas fa-check-circle mr-3 text-green-600 dark:text-green-400';
            titleElement.textContent = '認証成功';
        } else {
            // 失敗時のスタイル設定
            resultElement.className = 'mb-4 p-3 border rounded-lg bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200';
            iconElement.className = 'fas fa-times-circle mr-3 text-red-600 dark:text-red-400';
            titleElement.textContent = '認証失敗';
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



@endpush