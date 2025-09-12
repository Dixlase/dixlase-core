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
                <div x-show="captchaDriver === 'google' || captchaDriver === 'google_enterprise'">

                    @include('components::form.label', [
                        'for' => 'captcha_google_version',
                        'text' => __('admin.settings.security.captcha_google_version'),
                    ])
                    <div x-show="captchaDriver === 'google'">
                        @include('components::form.select', [
                            'id' => 'captcha_google_version',
                            'name' => 'captcha_google_version',
                            'value' => old('captcha_google_version', $settings['captcha_google_version']),
                            'options' => __('admin.settings.security.captcha_version_options'),
                            'xModel' => 'captchaVersion'
                        ])
                    </div>

                    <div x-show="captchaVersion === 'v3' || captchaDriver === 'google_enterprise'" class="mt-4">
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

                    <!-- CAPTCHA接続テスト -->
                    <div class="mt-4">
                        <button type="button" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!captchaEnabled"
                                onclick="testCaptchaConnection('google')">
                            {{ __('admin.settings.security.captcha_test_button') }}
                        </button>
                        
                        <!-- テスト結果表示 -->
                        <div id="captcha-test-status-google" class="mt-4 p-3 border rounded-lg 
                            @php
                                $testResult = $captchaTestResults['google'] ?? null;
                                if ($testResult && $testResult['success']) {
                                    echo 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                                } elseif ($testResult && !$testResult['success']) {
                                    echo 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                                } else {
                                    echo 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800';
                                }
                            @endphp
                        " x-show="captchaEnabled">
                            <div class="flex items-center">
                                @php
                                    $testResult = $captchaTestResults['google'] ?? null;
                                    if ($testResult && $testResult['success']) {
                                        $iconClass = 'fas fa-check-circle text-green-500';
                                        $textClass = 'text-green-700 dark:text-green-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.passed');
                                    } elseif ($testResult && !$testResult['success']) {
                                        $iconClass = 'fas fa-times-circle text-red-500';
                                        $textClass = 'text-red-700 dark:text-red-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.failed') . ': ' . ($testResult['error_message'] ?? '');
                                    } else {
                                        $iconClass = 'fas fa-clock text-gray-400';
                                        $textClass = 'text-gray-600 dark:text-gray-400';
                                        $statusText = __('admin.settings.security.captcha_test_status.not_tested');
                                    }
                                @endphp
                                <i class="mr-2 {{ $iconClass }}"></i>
                                <span class="text-sm {{ $textClass }}">
                                    Google reCAPTCHA: {{ $statusText }}
                                    @if($testResult && isset($testResult['tested_at']))
                                        <span class="text-xs opacity-75">({{ $testResult['tested_at'] }})</span>
                                    @endif
                                </span>
                            </div>
                            @if(!$testResult)
                                <div class="mt-3 text-sm text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    reCAPTCHAを有効にする場合は、サイトキーとシークレットキーを入力し、接続テストを完了させてください。
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Google reCAPTCHA Enterprise Settings -->
                <div x-show="captchaDriver === 'google_enterprise'">
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

                    <!-- CAPTCHA接続テスト -->
                    <div class="mt-4">
                        <button type="button" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!captchaEnabled"
                                onclick="testCaptchaConnection('google_enterprise')">
                            {{ __('admin.settings.security.captcha_test_button') }}
                        </button>
                        
                        <!-- テスト結果表示 -->
                        <div id="captcha-test-status-google_enterprise" class="mt-4 p-3 border rounded-lg 
                            @php
                                $testResult = $captchaTestResults['google_enterprise'] ?? null;
                                if ($testResult && $testResult['success']) {
                                    echo 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                                } elseif ($testResult && !$testResult['success']) {
                                    echo 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                                } else {
                                    echo 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800';
                                }
                            @endphp
                        " x-show="captchaEnabled">
                            <div class="flex items-center">
                                @php
                                    $testResult = $captchaTestResults['google_enterprise'] ?? null;
                                    if ($testResult && $testResult['success']) {
                                        $iconClass = 'fas fa-check-circle text-green-500';
                                        $textClass = 'text-green-700 dark:text-green-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.passed');
                                    } elseif ($testResult && !$testResult['success']) {
                                        $iconClass = 'fas fa-times-circle text-red-500';
                                        $textClass = 'text-red-700 dark:text-red-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.failed') . ': ' . ($testResult['error_message'] ?? '');
                                    } else {
                                        $iconClass = 'fas fa-clock text-gray-400';
                                        $textClass = 'text-gray-600 dark:text-gray-400';
                                        $statusText = __('admin.settings.security.captcha_test_status.not_tested');
                                    }
                                @endphp
                                <i class="mr-2 {{ $iconClass }}"></i>
                                <span class="text-sm {{ $textClass }}">
                                    Google reCAPTCHA Enterprise: {{ $statusText }}
                                    @if($testResult && isset($testResult['tested_at']))
                                        <span class="text-xs opacity-75">({{ $testResult['tested_at'] }})</span>
                                    @endif
                                </span>
                            </div>
                            @if(!$testResult)
                                <div class="mt-3 text-sm text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    reCAPTCHA Enterpriseを有効にする場合は、サイトキー、シークレットキー、プロジェクトIDを入力し、接続テストを完了させてください。
                                </div>
                            @endif
                        </div>
                    </div>
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

                    <!-- CAPTCHA接続テスト -->
                    <div class="mt-4">
                        <button type="button" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!captchaEnabled"
                                onclick="testCaptchaConnection('turnstile')">
                            {{ __('admin.settings.security.captcha_test_button') }}
                        </button>
                        
                        <!-- テスト結果表示 -->
                        <div id="captcha-test-status-turnstile" class="mt-4 p-3 border rounded-lg 
                            @php
                                $testResult = $captchaTestResults['turnstile'] ?? null;
                                if ($testResult && $testResult['success']) {
                                    echo 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                                } elseif ($testResult && !$testResult['success']) {
                                    echo 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                                } else {
                                    echo 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800';
                                }
                            @endphp
                        " x-show="captchaEnabled">
                            <div class="flex items-center">
                                @php
                                    $testResult = $captchaTestResults['turnstile'] ?? null;
                                    if ($testResult && $testResult['success']) {
                                        $iconClass = 'fas fa-check-circle text-green-500';
                                        $textClass = 'text-green-700 dark:text-green-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.passed');
                                    } elseif ($testResult && !$testResult['success']) {
                                        $iconClass = 'fas fa-times-circle text-red-500';
                                        $textClass = 'text-red-700 dark:text-red-300';
                                        $statusText = __('admin.settings.security.captcha_test_status.failed') . ': ' . ($testResult['error_message'] ?? '');
                                    } else {
                                        $iconClass = 'fas fa-clock text-gray-400';
                                        $textClass = 'text-gray-600 dark:text-gray-400';
                                        $statusText = __('admin.settings.security.captcha_test_status.not_tested');
                                    }
                                @endphp
                                <i class="mr-2 {{ $iconClass }}"></i>
                                <span class="text-sm {{ $textClass }}">
                                    Cloudflare Turnstile: {{ $statusText }}
                                    @if($testResult && isset($testResult['tested_at']))
                                        <span class="text-xs opacity-75">({{ $testResult['tested_at'] }})</span>
                                    @endif
                                </span>
                            </div>
                            @if(!$testResult)
                                <div class="mt-3 text-sm text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Cloudflare Turnstileを有効にする場合は、サイトキーとシークレットキーを入力し、接続テストを完了させてください。
                                </div>
                            @endif
                        </div>
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
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.security.save_confirmation_title'),
        'message' => __('admin.settings.security.save_confirmation_message'),
        'confirm_label' => __('admin.settings.security.save_button'),
        'cancel_label' => __('admin.settings.security.back_button'),
        'form' => 'security-settings-form',
    ])
@endsection


@push('scripts')
function testCaptchaConnection(driver) {
    const statusElement = document.getElementById(`captcha-test-status-${driver}`);
    const button = event.target;
    
    // ドライバー名を取得
    let driverName = '';
    if (driver === 'google') {
        driverName = 'Google reCAPTCHA';
    } else if (driver === 'google_enterprise') {
        driverName = 'Google reCAPTCHA Enterprise';
    } else if (driver === 'turnstile') {
        driverName = 'Cloudflare Turnstile';
    }
    
    // テスト中の表示
    statusElement.className = 'mt-4 p-3 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
    statusElement.innerHTML = `
        <div class="flex items-center">
            <i class="mr-2 fas fa-spinner fa-spin text-blue-500"></i>
            <span class="text-sm text-blue-700 dark:text-blue-300">
                ${driverName}: @lang("admin.settings.security.captcha_test_status.testing")
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
            statusElement.className = 'mt-4 p-3 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            statusElement.innerHTML = `
                <div class="flex items-center">
                    <i class="mr-2 fas fa-check-circle text-green-500"></i>
                    <span class="text-sm text-green-700 dark:text-green-300">
                        ${driverName}: @lang("admin.settings.security.captcha_test_status.passed")
                        <span class="text-xs opacity-75">(${currentTime})</span>
                    </span>
                </div>
            `;
        } else {
            statusElement.className = 'mt-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
            statusElement.innerHTML = `
                <div class="flex items-center">
                    <i class="mr-2 fas fa-times-circle text-red-500"></i>
                    <span class="text-sm text-red-700 dark:text-red-300">
                        ${driverName}: @lang("admin.settings.security.captcha_test_status.failed"): ${data.message}
                        <span class="text-xs opacity-75">(${currentTime})</span>
                    </span>
                </div>
            `;
        }
    })
    .catch(error => {
        const currentTime = new Date().toLocaleString('ja-JP');
        statusElement.className = 'mt-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        statusElement.innerHTML = `
            <div class="flex items-center">
                <i class="mr-2 fas fa-times-circle text-red-500"></i>
                <span class="text-sm text-red-700 dark:text-red-300">
                    ${driverName}: @lang("admin.settings.security.captcha_test_status.failed"): ${error.message}
                    <span class="text-xs opacity-75">(${currentTime})</span>
                </span>
            </div>
        `;
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = '@lang("admin.settings.security.captcha_test_button")';
    });
}

// 設定変更時にテスト結果をリセット
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[name^="captcha_"]');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // 現在のドライバーのテスト結果をリセット
            const driver = document.querySelector('select[name="captcha_driver"]').value;
            const statusElement = document.getElementById(`captcha-test-status-${driver}`);
            if (statusElement) {
                statusElement.innerHTML = '<span class="text-gray-500">@lang("admin.settings.security.captcha_test_status.not_tested")</span>';
            }
        });
    });
});
@endpush