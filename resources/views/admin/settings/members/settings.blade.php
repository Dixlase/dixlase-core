{{--
This file is part of Dixlase.

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

@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto">
    <form method="POST" action="{{ route('admin.settings.members.settings.update') }}" id="member-settings-form">
        @csrf

        <!-- パスワード条件設定 -->
        <section>
            <h2>{{ __('admin.settings.members.settings.password_conditions') }}</h2>
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_min_length') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_min_length',
                    'options' => $minLengthOptions,
                    'value' => old('password_min_length', (string) $passwordMinLength),
                ])
            </fieldset>

            <!-- 大文字 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_require_uppercase') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_require_uppercase',
                    'options' => $uppercaseOptions,
                    'value' => old('password_require_uppercase', (string) (int) $passwordRequireUppercase),
                ])
            </fieldset>

            <!-- 数字 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_require_number') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_require_number',
                    'options' => $numberOptions,
                    'value' => old('password_require_number', (string) (int) $passwordRequireNumber),
                ])
            </fieldset>

            <!-- 記号 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_require_symbol') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_require_symbol',
                    'options' => $symbolOptions,
                    'value' => old('password_require_symbol', (string) (int) $passwordRequireSymbol),
                ])
            </fieldset>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                {{ __('admin.settings.members.settings.password_security_warning') }}
            </p>
        </section>

        <!-- ログイン試行制限設定 -->
        <section>
            <h2>{{ __('admin.settings.members.settings.login_attempt_limit_settings') }}</h2>
            @if(!$isMailServerTested)
                @include('components.message', [
                    'type' => 'warning',
                    'message' => __('admin.settings.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base')])
                ])
            @endif
            <!-- 機能有効/無効 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.login_attempt_limit_enabled') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'login_attempt_limit_enabled',
                    'options' => [
                        '0' => __('common.disabled'),
                        '1' => __('common.enabled'),
                    ],
                    'value' => old('login_attempt_limit_enabled', (string) (int) $loginAttemptLimitEnabled),
                ])
                <p>
                    {{ __('admin.settings.members.settings.login_attempt_limit_help') }}
                </p>
            </fieldset>

            <!-- 設定詳細 -->
            <div>
                <!-- 最大試行回数 -->
                <fieldset>
                    <legend>{{ __('admin.settings.members.settings.login_attempt_max_attempts') }}</legend>
                    @include('components.form.text', [
                        'type' => 'number',
                        'name' => 'login_attempt_max_attempts',
                        'value' => old('login_attempt_max_attempts', $loginAttemptMaxAttempts),
                        'min' => 1,
                        'max' => 100,
                        'class' => 'number-input-small'
                    ])
                    <p>
                        {{ __('admin.settings.members.settings.login_attempt_max_attempts_help') }}
                    </p>
                </fieldset>

                <!-- 時間窓 -->
                <fieldset>
                    <legend>{{ __('admin.settings.members.settings.login_attempt_time_window') }}</legend>
                    @include('components.form.text', [
                        'type' => 'number',
                        'name' => 'login_attempt_time_window',
                        'value' => old('login_attempt_time_window', $loginAttemptTimeWindow),
                        'min' => 1,
                        'max' => 1440,
                        'class' => 'number-input-small'
                    ])
                    <p>
                        {{ __('admin.settings.members.settings.login_attempt_time_window_help') }}
                    </p>
                </fieldset>

                <!-- ロックアウト時間 -->
                <fieldset>
                    <legend>{{ __('admin.settings.members.settings.login_attempt_lockout_duration') }}</legend>
                    @include('components.form.text', [
                        'type' => 'number',
                        'name' => 'login_attempt_lockout_duration',
                        'value' => old('login_attempt_lockout_duration', $loginAttemptLockoutDuration),
                        'min' => 1,
                        'max' => 10080,
                        'class' => 'number-input-small'
                    ])
                    <p>
                        {{ __('admin.settings.members.settings.login_attempt_lockout_duration_help') }}
                    </p>
                </fieldset>
                <!-- ロックアウト通知設定 -->
                <fieldset>
                    <legend>{{ __('admin.settings.members.settings.lockout_notification_enabled') }}</legend>
                    @include('components.form.radio-group', [
                        'name' => 'lockout_notification_enabled',
                    'options' => [
                        '0' => __('common.disabled'),
                        '1' => __('common.enabled'),
                    ],
                        'value' => old('lockout_notification_enabled', (string) (int) $lockoutNotificationEnabled),
                    ])

                    <p>
                        {!! __('admin.settings.members.settings.lockout_notification_help') !!}
                    </p>
                </fieldset>
            </div>
        </section>

        <!-- パスワードリセット機能設定 -->
        <section>
            <h2>{{ __('admin.settings.members.settings.password_reset_settings') }}</h2>
            @if(!$isMailServerTested)
                @include('components.message', [
                    'type' => 'warning',
                    'message' => __('admin.settings.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base')])
                ])
            @endif
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_reset_enabled') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_reset_enabled',
                    'options' => $passwordResetOptions,
                    'value' => old('password_reset_enabled', (string) (int) $passwordResetEnabled),
                ])
                <p>
                    {!! __('admin.settings.members.settings.password_reset_help') !!}
                </p>
            </fieldset>
        </section>

        <!-- パスワード辞書攻撃対策設定 -->
        <section>
            <h2>{{ __('admin.settings.members.settings.pwned_password_settings') }}</h2>

            <fieldset>
                <legend>{{ __('admin.settings.members.settings.pwned_password_check_enabled') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'pwned_password_check_enabled',
                    'options' => $pwnedPasswordOptions,
                    'value' => old('pwned_password_check_enabled', (string) (int) $pwnedPasswordCheckEnabled),
                ])
                <p>
                    {!! __('admin.settings.members.settings.pwned_password_help') !!}
                </p>
                <!-- API情報 -->
                @include('components.message', [
                    'type' => 'info',
                    'message' => __('admin.settings.members.settings.pwned_password_api_info')
                ])
            </fieldset>
        </section>

        <!-- 管理メンバー用セッション設定 -->
        <section>
            <h2>{{ __('admin.settings.members.settings.admin_session_settings') }}</h2>
            <p>
                {{ __('admin.settings.members.settings.admin_session_settings_description') }}
            </p>

            <!-- セッション有効時間カスタマイズ有効/無効 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.admin_session_lifetime_enabled') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'members_session_lifetime_enabled',
                    'options' => [
                        '1' => __('common.enabled'),
                        '0' => __('common.disabled')
                    ],
                    'value' => old('members_session_lifetime_enabled', (string) (int) $membersSessionLifetimeEnabled),
                ])
                <p>
                    {{ __('admin.settings.members.settings.admin_session_lifetime_enabled_help') }}
                </p>
            </fieldset>

            <!-- 管理メンバー用セッション有効時間 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.admin_session_lifetime') }}</legend>
                <div class="flex items-center">
                    @include('components.form.text', [
                        'type' => 'number',
                        'name' => 'members_session_lifetime',
                        'value' => old('members_session_lifetime', $membersSessionLifetime),
                        'min' => 1,
                        'max' => 43200,
                        'class' => 'number-input-small'
                    ])
                    <span class="text-sm ml-2 text-gray-700 dark:text-gray-300">{{ __('common.minutes') }}</span>
                </div>
                <p>
                    {{ __('admin.settings.members.settings.admin_session_lifetime_help') }}
                </p>
            </fieldset>
        </section>

        <!-- ログイン通知設定 -->
        <section>
            <h2>{{ __('common.login_notification_mode.label') }}</h2>
            @if(!$isMailServerTested)
                @include('components.message', [
                    'type' => 'warning',
                    'message' => __('admin.settings.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base')])
                ])
            @endif
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.login_notification_global_setting') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'login_notification_mode',
                    'options' => $loginNotificationGlobalOptions,
                    'value' => old('login_notification_mode', (string) $loginNotification),
                ])
            </fieldset>
        </section>

        <!-- 二段階認証設定 -->
        <section>
            <h2>{{ __('common.two_factor_settings') }}</h2>
            @if(!$isMailServerTested)
                @include('components.message', [
                    'type' => 'warning',
                    'message' => __('admin.settings.members.settings.mail_server_test_warning', ['url' => route('admin.settings.base')])
                ])
            @endif
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.two_factor_mode_global_setting') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'force_2fa',
                    'options' => $twoFactorGlobalOptions,
                    'value' => old('force_2fa', (string) $force2fa),
                    'class' => '',
                ])
            </fieldset>

            <!-- 二段階認証方法設定 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.enabled_two_factor_methods_label') }}</legend>
                
                <div class="space-y-6">
                    <!-- 認証方法一覧 -->
                    <div class="space-y-3">
                        <!-- メール認証（常に有効） -->
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                                <span class="text-sm font-medium">{{ __('common.two_factor_method.numbered_options.0') }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">（常に有効）</span>
                        </div>

                        <!-- Passkey認証（有効/無効選択可能） -->
                        <div class="flex items-center space-x-3">
                            @include('components.form.checkbox', [
                                'name' => 'enabled_2fa_passkey',
                                'label' => __('common.two_factor_method.numbered_options.1'),
                                'value' => $passkeyEnabled ? 1 : 0,
                                'checked' => $passkeyEnabled ?? false,
                            ])
                        </div>
                    </div>
                    
                    <!-- ヘルプテキスト -->
                    <div class="space-y-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.enabled_two_factor_methods_help') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.email_always_enabled_note') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <!-- 二段階認証の有効期限設定 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.two_factor_expire_settings') }}</legend>
                
                <div class="space-y-4">
                    <!-- 認証の有効期限（メール・デバイス共通） -->
                    <div>
                        <label for="two_factor_expire_minutes" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.two_factor_expire_minutes') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="two_factor_expire_minutes" 
                                name="two_factor_expire_minutes" 
                                value="{{ old('two_factor_expire_minutes', $twoFactorExpireMinutes) }}"
                                min="1"
                                max="60"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.two_factor_expire_minutes_help') }}
                        </p>
                    </div>

                    <!-- 認証メール再送信間隔 -->
                    <div>
                        <label for="two_factor_resend_interval_seconds" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.two_factor_resend_interval_seconds') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="two_factor_resend_interval_seconds" 
                                name="two_factor_resend_interval_seconds" 
                                value="{{ old('two_factor_resend_interval_seconds', $twoFactorResendIntervalSeconds) }}"
                                min="60"
                                max="600"
                                step="60"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.seconds') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.two_factor_resend_interval_seconds_help') }}
                        </p>
                    </div>

                </div>
            </fieldset>

            <!-- 二段階認証試行制限設定 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.2fa_attempt_limit_settings') }}</legend>
                
                <div class="space-y-4">
                    <!-- 最大試行回数 -->
                    <div>
                        <label for="2fa_max_attempts" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.2fa_max_attempts') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="2fa_max_attempts" 
                                name="2fa_max_attempts" 
                                value="{{ old('2fa_max_attempts', $twoFaMaxAttempts) }}"
                                min="1"
                                max="10"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.times') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.2fa_max_attempts_help') }}
                        </p>
                    </div>

                    <!-- 試行制限の時間枠 -->
                    <div>
                        <label for="2fa_attempt_window" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.2fa_attempt_window') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="2fa_attempt_window" 
                                name="2fa_attempt_window" 
                                value="{{ old('2fa_attempt_window', $twoFaAttemptWindow) }}"
                                min="5"
                                max="60"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.2fa_attempt_window_help') }}
                        </p>
                    </div>

                    <!-- ロックアウト時間 -->
                    <div>
                        <label for="2fa_lockout_duration" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.2fa_lockout_duration') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="2fa_lockout_duration" 
                                name="2fa_lockout_duration" 
                                value="{{ old('2fa_lockout_duration', $twoFaLockoutDuration) }}"
                                min="5"
                                max="1440"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.minutes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.2fa_lockout_duration_help') }}
                        </p>
                    </div>

                    <!-- ロックアウト通知 -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            {{ __('admin.settings.members.settings.2fa_lockout_notification') }}
                        </label>
                        @include('components.form.radio-group', [
                            'name' => '2fa_lockout_notification_enabled',
                            'options' => [
                                '1' => __('common.enabled'),
                                '0' => __('common.disabled'),
                            ],
                            'value' => old('2fa_lockout_notification_enabled', $twoFaLockoutNotificationEnabled ? '1' : '0'),
                        ])
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.2fa_lockout_notification_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>

            <!-- 回復コード設定 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.recovery_code_settings') }}</legend>
                
                <div class="space-y-4">
                    <!-- 回復コード生成個数 -->
                    <div>
                        <label for="recovery_codes_count" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.recovery_codes_count') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="recovery_codes_count" 
                                name="recovery_codes_count" 
                                value="{{ old('recovery_codes_count', $recoveryCodesCount ?? 5) }}"
                                min="1"
                                max="10"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.codes') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.recovery_codes_count_help') }}
                        </p>
                    </div>

                    <!-- 回復コード再生成間隔 -->
                    <div>
                        <label for="recovery_code_regenerate_interval" class="block text-sm font-medium">
                            {{ __('admin.settings.members.settings.recovery_code_regenerate_interval') }}
                        </label>
                        <div class="mt-1 flex items-center space-x-2">
                            <input 
                                type="number" 
                                id="recovery_code_regenerate_interval" 
                                name="recovery_code_regenerate_interval" 
                                value="{{ old('recovery_code_regenerate_interval', $recoveryCodeRegenerateInterval ?? 24) }}"
                                min="1"
                                max="168"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.members.settings.hours') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('admin.settings.members.settings.recovery_code_regenerate_interval_help') }}
                        </p>
                    </div>
                </div>
            </fieldset>
        </section>
    </form>

    <!-- 全メンバー強制ログアウト -->
    <section>
        <h2>{{ __('admin.settings.members.settings.force_logout_heading') }}</h2>
        <p class="mb-3">{{ __('admin.settings.members.settings.force_logout_description') }}</p>
        <!-- 全メンバー強制ログアウト用フォーム -->
        <form id="force-logout-all-form" action="{{ route('admin.settings.members.force-logout-all') }}" method="POST">
            @csrf
        </form>

        <!-- 全メンバー強制ログアウトボタン -->
        @include('components.form.button', [
            'type' => 'button',
            'label' => __('admin.settings.members.settings.force_logout_all_button'),
            'variant' => 'warning',
            'onclick' => "openModal('forceLogoutAllModal')"
        ])
    </section>
</div>
@endsection

@section('save')
    <!-- 更新ボタン -->
    @include('components.form.button', [
        'type' => 'button',
        'label' => __('common.update'),
        'class' => 'button-save',
        'onclick' => "openModal('confirmationModal')"
    ])
@endsection

@section('modals')
    <!-- 更新確認モーダル -->
    @include('components.modal', [
        'id' => 'confirmationModal',
        'title' => __('common.update_confirmation_title'),
        'message' => __('common.update_confirmation_message'),
        'confirm_label' => __('common.update'),
        'cancel_label' => __('common.cancel'),
        'form' => 'member-settings-form',
    ])

    <!-- 全メンバー強制ログアウト確認モーダル -->
    @include('components.modal', [
        'id' => 'forceLogoutAllModal',
        'title' => __('admin.settings.members.settings.force_logout_all_modal.title'),
        'message' => __('admin.settings.members.settings.force_logout_all_modal.message'),
        'confirm_label' => __('admin.settings.members.settings.force_logout_all_modal.confirm_label'),
        'cancel_label' => __('common.cancel'),
        'form' => 'force-logout-all-form',
        'icon_type' => 'warning',
        'confirm_color' => 'yellow'
    ])
@endsection
@section('scripts')
    <script>
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('[id$="Modal"]');

            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            // Initialize form submission handlers
            const confirmationModal = document.getElementById('confirmationModal');
            const forceLogoutAllModal = document.getElementById('forceLogoutAllModal');

            if (confirmationModal) {
                const confirmButton = confirmationModal.querySelector('button[type="submit"]');
                if (confirmButton) {
                    confirmButton.addEventListener('click', () => {
                        document.getElementById('member-settings-form').submit();
                    });
                }
            }

            if (forceLogoutAllModal) {
                const forceLogoutButton = forceLogoutAllModal.querySelector('button[type="submit"]');
                if (forceLogoutButton) {
                    forceLogoutButton.addEventListener('click', () => {
                        document.getElementById('force-logout-all-form').submit();
                    });
                }
            }

            // 二段階認証方法の動的制御は不要（メール認証は常に有効、Passkeyは単純なチェックボックス）
        });
    </script>
@endsection
