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
    enableAllowedIPs: {{ old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']) ? 'true' : 'false' }},
    blockedAdminIps: {{ old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']) ? 'true' : 'false' }},
    enableAllowedFrontIPs: {{ old('enable_allowed_front_ips', $settings['enable_allowed_front_ips']) ? 'true' : 'false' }},
    enableBlockedFrontIps: {{ old('enable_blocked_front_ips', $settings['enable_blocked_front_ips']) ? 'true' : 'false' }},
    captchaEnabled: {{ old('captcha_enabled', $settings['captcha_enabled']) ? 'true' : 'false' }},
    captchaDriver: '{{ old('captcha_driver', $settings['captcha_driver']) }}',
    captchaVersion: '{{ old('captcha_google_version', $settings['captcha_google_version']) }}',
}">
    <form id="security-settings-form" method="POST" action="{{ route('admin.settings.security.update') }}">
        @csrf
        @method('POST')

        
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

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
                    <div class="mt-4">
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
                            'min' => '0',
                            'max' => '1',
                        ])
                        <p class="text-sm text-gray-200 mt-1">
                            {{ __('admin.settings.security.captcha_min_score_description') }}
                        </p>
                    </div>

                    @include('components::form.label', [
                        'for' => 'captcha_google_enterprise_site_key',
                        'text' => __('admin.settings.security.captcha_google_enterprise_site_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_enterprise_site_key',
                        'name' => 'captcha_google_enterprise_site_key',
                        'value' => old('captcha_google_enterprise_site_key', $settings['captcha_google_enterprise_site_key']),
                        'placeholder' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
                        'type' => 'password',
                    ])

                    @include('components::form.label', [
                        'for' => 'captcha_google_enterprise_secret_key',
                        'text' => __('admin.settings.security.captcha_google_enterprise_secret_key'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_enterprise_secret_key',
                        'name' => 'captcha_google_enterprise_secret_key',
                        'value' => old('captcha_google_enterprise_secret_key', $settings['captcha_google_enterprise_secret_key']),
                        'placeholder' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
                        'type' => 'password',
                    ])

                    @include('components::form.label', [
                        'for' => 'captcha_google_enterprise_project_id',
                        'text' => __('admin.settings.security.captcha_google_project_id'),
                    ])
                    @include('components::form.text', [
                        'id' => 'captcha_google_enterprise_project_id',
                        'name' => 'captcha_google_project_id',
                        'value' => old('captcha_google_project_id', $settings['captcha_google_project_id']),
                        'placeholder' => 'your-gcp-project-id'
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

                <!-- 統合CAPTCHAテスト -->
                <div class="mt-6" x-show="captchaEnabled">

                    <!-- テスト結果表示 -->
                    <div :id="`captcha-test-status-${captchaDriver}`" class="my-4 p-3 border rounded-lg 
                        @php
                        // 現在選択されているドライバーのテスト結果を取得
                        $currentDriver = old('captcha_driver', $settings['captcha_driver']);
                        $testResult = $captchaTestResults[$currentDriver] ?? null;
                            if ($testResult && $testResult['success'] && !($testResult['is_reset'] ?? false)) {
                                echo 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                            } elseif ($testResult && !$testResult['success'] && !($testResult['is_reset'] ?? false)) {
                                echo 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                            } else {
                                echo 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
                            }
                        @endphp
                    " x-show="captchaEnabled">
                        <div class="flex items-center">
                            @php
                            $testResult = $captchaTestResults[$currentDriver] ?? null;
                            
                            // デバッグ用: セッションとテスト結果の状態を表示
                            if (config('app.debug')) {
                                echo "<!-- DEBUG: Session captcha_test_result: " . json_encode(session('captcha_test_result')) . " -->";
                                echo "<!-- DEBUG: Current driver: {$currentDriver} -->";
                                echo "<!-- DEBUG: Test result for {$currentDriver}: " . json_encode($testResult) . " -->";
                                echo "<!-- DEBUG: is_reset flag: " . json_encode($testResult['is_reset'] ?? 'not set') . " -->";
                            }
                            
                            if ($testResult && $testResult['success'] && !($testResult['is_reset'] ?? false)) {
                                $iconClass = 'fas fa-check-circle text-green-500';
                                $textClass = 'text-green-700 dark:text-green-300';
                                $statusText = __('admin.settings.security.captcha_test_status.passed_initial');
                                $showProviderName = false;
                            } elseif ($testResult && !$testResult['success'] && !($testResult['is_reset'] ?? false)) {
                                $iconClass = 'fas fa-times-circle text-red-500';
                                $textClass = 'text-red-700 dark:text-red-300';
                                $statusText = __('admin.settings.security.captcha_test_status.failed');
                                $showProviderName = false;
                            } else {
                                $iconClass = 'fas fa-clock text-yellow-500';
                                $textClass = 'text-yellow-700 dark:text-yellow-300';
                                $providerEnum = \App\Enums\CaptchaProvider::fromString($currentDriver);
                                $providerName = $providerEnum ? $providerEnum->label() : $currentDriver;
                                $setupUrl = $providerEnum ? $providerEnum->getSetupUrl() : '#';
                                $statusText = __('admin.settings.security.captcha_test_status.not_tested_with_provider', [
                                    'provider' => $providerName,
                                    'link' => $setupUrl
                                ]);
                                $showProviderName = false;
                            }
                            
                            // プロバイダー名の取得
                            $providerEnum = \App\Enums\CaptchaProvider::fromString($currentDriver);
                            $providerName = $providerEnum ? $providerEnum->label() : $currentDriver;
                            @endphp
                            <i class="mr-2 {{ $iconClass }}"></i>
                            <span class="text-sm {{ $textClass }}">
                                @if($showProviderName)
                                    {{ $providerName }}: {{ $statusText }}
                                    @if($testResult && isset($testResult['tested_at']))
                                        <span class="text-xs opacity-75">({{ $testResult['tested_at'] }})</span>
                                    @endif
                                @else
                                    {!! $statusText !!}
                                @endif
                            </span>
                        </div>
                    </div>
                    <!-- Live CAPTCHA Widget Validation -->
                    <div class="mt-4 p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-medium mb-3 text-blue-800 dark:text-blue-200">
                            {{ __('admin.settings.security.captcha_live_validation') }}
                        </h4>
                        <p class="text-xs text-blue-600 dark:text-blue-300 mb-3">
                            {{ __('admin.settings.security.captcha_live_validation_description') }}
                        </p>
                        
                        <!-- CAPTCHA Widget Container -->
                        <div id="captcha-widget-container" class="mb-4">
                            <!-- Dynamic CAPTCHA widget will be loaded here -->
                        </div>
                        
                        <!-- Validation Status -->
                        <div id="captcha-validation-status" class="mb-3">
                            <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span class="text-sm">{{ __('admin.settings.security.captcha_validation_required') }}</span>
                            </div>
                        </div>
                        
                        <!-- Test Button -->
                        <button type="button" 
                                id="captcha-validate-button"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!captchaEnabled"
                                @click="validateCaptchaWidget()">
                            {{ __('admin.settings.security.captcha_validate_button') }}
                        </button>
                    </div>
                    
                    
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
        formData.append('captcha_google_site_key', document.getElementById('captcha_google_site_key').value);
        formData.append('captcha_google_secret_key', document.getElementById('captcha_google_secret_key').value);
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

// プロバイダー切り替え時とフィールド変更時のテスト結果リセット関数
function resetCaptchaTestResult(driver) {
    const statusElement = document.getElementById(`captcha-test-status-${driver}`);
    if (statusElement) {
        const providerInfo = getProviderInfo(driver);
        
        statusElement.className = 'my-4 p-3 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
        const notTestedMessage = '@lang("admin.settings.security.captcha_test_status.not_tested")';
        const setupLinkText = '@lang("admin.settings.security.captcha_test_status.setup_link_text")';
        
        statusElement.innerHTML = `
            <div class="flex items-center">
                <i class="mr-2 fas fa-clock text-yellow-500"></i>
                <div class="text-sm text-yellow-700 dark:text-yellow-300">
                    <div class="mb-1">${notTestedMessage}</div>
                    <div><a href="${providerInfo.url}" target="_blank" class="underline hover:text-yellow-100">${setupLinkText.replace(':provider', providerInfo.name)}</a></div>
                </div>
            </div>
        `;
    }
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

// Google reCAPTCHAウィジェットを読み込み
function loadGoogleRecaptchaWidget() {
    const siteKey = document.getElementById('captcha_google_site_key').value;
    const version = document.getElementById('captcha_google_version').value;
    const container = document.getElementById('captcha-widget-container');
    
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
        script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.onload = () => renderV3Widget(siteKey);
    } else {
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.onload = () => renderV2Widget(siteKey, version);
    }
    
    document.head.appendChild(script);
}

// v3ウィジェットをレンダリング
function renderV3Widget(siteKey) {
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = `
        <div class="text-sm text-gray-600 mb-2">reCAPTCHA v3 (自動実行)</div>
        <div id="v3-status" class="p-2 bg-gray-100 rounded text-sm">準備中...</div>
        <input type="hidden" id="g-recaptcha-response-v3" name="g-recaptcha-response" value="">
    `;
    
    grecaptcha.ready(function() {
        document.getElementById('v3-status').textContent = 'v3ウィジェット準備完了 - 認証ボタンをクリックしてください';
        currentCaptchaWidget = 'v3';
    });
}

// v2ウィジェットをレンダリング
function renderV2Widget(siteKey, version) {
    const container = document.getElementById('captcha-widget-container');
    const widgetId = 'recaptcha-widget-' + Date.now();
    
    if (version === 'v2_invisible') {
        container.innerHTML = `
            <div class="text-sm text-gray-600 mb-2">reCAPTCHA v2 非表示</div>
            <div id="${widgetId}"></div>
            <input type="hidden" id="g-recaptcha-response-v2" name="g-recaptcha-response" value="">
            <p class="text-xs text-gray-500 mt-2">v2非表示モード: 認証ボタンをクリックして実行してください</p>
        `;
    } else {
        container.innerHTML = `
            <div class="text-sm text-gray-600 mb-2">reCAPTCHA ${version}</div>
            <div id="${widgetId}"></div>
            <input type="hidden" id="g-recaptcha-response-v2" name="g-recaptcha-response" value="">
        `;
    }
    
    grecaptcha.ready(function() {
        const widgetOptions = {
            'sitekey': siteKey,
            'callback': onRecaptchaSuccess,
            'expired-callback': onRecaptchaExpired
        };
        
        if (version === 'v2_invisible') {
            widgetOptions['size'] = 'invisible';
        }
        
        currentCaptchaWidget = grecaptcha.render(widgetId, widgetOptions);
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
    
    captchaValidated = true;
    updateValidationStatus('success');
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
            html = `
                <div class="flex items-center text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="text-sm">{{ __('admin.settings.security.captcha_validation_success') }}</span>
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
    statusElement.innerHTML = html;
}

// CAPTCHAウィジェット検証を実行
function validateCaptchaWidget() {
    const driver = document.getElementById('captcha_driver').value;
    const version = document.getElementById('captcha_google_version').value;
    
    if (driver === 'google' && version === 'v3') {
        // v3の場合は手動で実行
        const siteKey = document.getElementById('captcha_google_site_key').value;
        grecaptcha.ready(function() {
            grecaptcha.execute(siteKey, {action: 'validate_settings'}).then(function(token) {
                document.getElementById('g-recaptcha-response-v3').value = token;
                validateCaptchaToken(token);
            });
        });
    } else if (driver === 'google' && version === 'v2_invisible') {
        // v2非表示の場合は手動で実行
        grecaptcha.ready(function() {
            grecaptcha.execute(currentCaptchaWidget);
        });
    } else if (driver === 'google' && version === 'v2_checkbox') {
        // v2チェックボックスの場合はレスポンスをチェック
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
        formData.append('captcha_google_site_key', document.getElementById('captcha_google_site_key').value);
        formData.append('captcha_google_secret_key', document.getElementById('captcha_google_secret_key').value);
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            captchaValidated = true;
            updateValidationStatus('success');
        } else {
            captchaValidated = false;
            updateValidationStatus('failed');
        }
    })
    .catch(error => {
        console.error('CAPTCHA validation error:', error);
        captchaValidated = false;
        updateValidationStatus('failed');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // プロバイダー切り替え時のリセット
    const driverSelect = document.getElementById('captcha_driver');
    if (driverSelect) {
        driverSelect.addEventListener('change', function() {
            const newDriver = this.value;
            
            // テスト結果をリセット（セッションもクリア）
            resetCaptchaTestResult(newDriver);
            clearCaptchaTestSession(newDriver);
            
            // CAPTCHAウィジェットを再読み込み
            loadCaptchaWidget();
        });
    }
    
    // バージョン変更時のウィジェット再読み込み
    const versionSelect = document.getElementById('captcha_google_version');
    if (versionSelect) {
        versionSelect.addEventListener('change', function() {
            captchaValidated = false;
            loadCaptchaWidget();
        });
    }
    
    // キー入力時のウィジェット再読み込み
    const siteKeyInput = document.getElementById('captcha_google_site_key');
    if (siteKeyInput) {
        siteKeyInput.addEventListener('blur', function() {
            if (this.value) {
                loadCaptchaWidget();
            }
        });
    }
    
    // 初期ウィジェット読み込み
    if (document.getElementById('captcha_enabled').checked) {
        loadCaptchaWidget();
    }
    
    // フィールド変更時のリセット
    const inputs = document.querySelectorAll('input[name^="captcha_"]');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // 現在のドライバーのテスト結果をリセット
            const driver = document.querySelector('select[name="captcha_driver"]').value;
            resetCaptchaTestResult(driver);
            clearCaptchaTestSession(driver);
        });
    });
});
@endpush