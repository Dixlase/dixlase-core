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

            <!-- 記号 -->
            <fieldset>
                <legend>{{ __('admin.settings.members.settings.password_require_symbol') }}</legend>
                @include('components.form.radio-group', [
                    'name' => 'password_require_symbol',
                    'options' => $symbolOptions,
                    'value' => old('password_require_symbol', (string) (int) $passwordRequireSymbol),
                ])
            </fieldset>
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
                    <!-- 認証方法選択とデフォルト設定 -->
                    <div class="flex justify-start">
                        <!-- 有効な認証方法 -->
                        <fieldset class="flex-1">
                            <legend class="text-sm font-medium">認証方法</legend>
                            @include('components.form.checkbox-group', [
                                'name' => 'enabled_two_factor_methods',
                                'options' => $twoFactorMethodCheckboxOptions,
                                'values' => $enabledTwoFactorMethods ?? [],
                                'flexDirection' => 'col',
                            ])
                        </fieldset>

                        <!-- デフォルト認証方法 -->
                        <fieldset class="flex-1">
                            <legend class="text-sm font-medium text-left">デフォルト</legend>
                            @include('components.form.radio-group', [
                                'name' => 'default_two_factor_method',
                                'options' => $twoFactorMethodRadioOptions,
                                'value' => $selectedDefaultMethod,
                                'flexDirection' => 'col',
                            ])
                        </fieldset>
                    </div>
                    
                    <!-- ヘルプテキスト -->
                    <div class="space-y-1">
                        <p>
                            {{ __('admin.settings.members.settings.enabled_two_factor_methods_help') }}
                        </p>
                        <p>
                            {{ __('admin.settings.members.settings.default_two_factor_method_help') }}
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

            // 二段階認証方法の動的制御
            const enabledCheckboxes = document.querySelectorAll('input[name="enabled_two_factor_methods[]"]');
            const defaultRadios = document.querySelectorAll('input[name="default_two_factor_method"]');

            // 要素が存在する場合のみ処理を実行
            if (enabledCheckboxes.length > 0 && defaultRadios.length > 0) {
                function updateDefaultMethodOptions() {
                    const enabledValues = Array.from(enabledCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);

                    defaultRadios.forEach(radio => {
                        const methodContainer = radio.closest('[data-method]') || radio.closest('.radio-option') || radio.parentElement;
                        
                        if (enabledValues.includes(radio.value)) {
                            radio.disabled = false;
                            if (methodContainer) {
                                methodContainer.style.opacity = '1';
                            }
                        } else {
                            radio.disabled = true;
                            radio.checked = false;
                            if (methodContainer) {
                                methodContainer.style.opacity = '0.5';
                            }
                        }
                    });

                    // 有効な方法が1つだけの場合、自動的にデフォルトに設定
                    if (enabledValues.length === 1) {
                        const enabledRadio = document.querySelector(`input[name="default_two_factor_method"][value="${enabledValues[0]}"]`);
                        if (enabledRadio) {
                            enabledRadio.checked = true;
                        }
                    }
                }

                // チェックボックスの変更を監視
                enabledCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateDefaultMethodOptions);
                });

                // 初期状態を設定
                updateDefaultMethodOptions();
            }
        });
    </script>
@endsection
