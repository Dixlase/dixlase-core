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

    <form method="POST" action="{{ route('admin.settings.members.settings.update') }}" id="member-settings-form">
        @csrf

        <!-- パスワード条件設定 -->
        <div>
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.password_conditions') }}</h2>

            <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin.settings.members.settings.password_min_length') }}
            </label>

            @php
                $minLengthOptions = collect(__('admin.settings.members.settings.password_min_length_options'))
                    ->mapWithKeys(fn($label, $key) => [$key => $label])
                    ->toArray();

            @endphp

            @include('components.form.radio-group', [
                'name' => 'password_min_length',
                'options' => $minLengthOptions,
                'value' => old('password_min_length', (string) $passwordMinLength),
            ])
        </div>

            <!-- 大文字 -->
            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.settings.password_require_uppercase') }}
                </label>

                @php
                    $uppercaseOptions = collect(__('admin.settings.members.settings.password_require_uppercase_options'))
                        ->mapWithKeys(fn($label, $key) => [$key => $label])
                        ->toArray();
                @endphp

                @include('components.form.radio-group', [
                    'name' => 'password_require_uppercase',
                    'options' => $uppercaseOptions,
                    'value' => old('password_require_uppercase', (string) (int) $passwordRequireUppercase),
                ])
            </div>

            <!-- 記号 -->
            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.settings.password_require_symbol') }}
                </label>

                @php
                    $symbolOptions = collect(__('admin.settings.members.settings.password_require_symbol_options'))
                        ->mapWithKeys(fn($label, $key) => [$key => $label])
                        ->toArray();
                @endphp

                @include('components.form.radio-group', [
                    'name' => 'password_require_symbol',
                    'options' => $symbolOptions,
                    'value' => old('password_require_symbol', (string) (int) $passwordRequireSymbol),
                ])
            </div>
        </div>


        <!-- メールサーバー設定状況 -->
        @if(!$isMailServerTested)
            <div class="mt-8 mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0"></i>
                    <div class="text-sm">
                        <p class="text-yellow-800 dark:text-yellow-200 font-medium">{{ __('admin.settings.members.validation.mail_server_warning') }}</p>
                        <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                            {{ __('admin.settings.members.validation.mail_server_warning_message') }}<br>
                            <a href="{{ route('admin.settings.base') }}" class="underline hover:no-underline ml-1">{{ __('admin.settings.base.heading') }}</a>{{ __('admin.settings.members.validation.please_configure_in') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- ログイン試行制限設定 -->
        <div class="mt-8 border-t pt-6">
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.login_attempt_limit_settings') }}</h2>

            <!-- 機能有効/無効 -->
            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                    {{ __('admin.settings.members.settings.login_attempt_limit_enabled') }}
                </label>

                @php
                    $loginAttemptLimitOptions = [
                        '1' => __('admin.settings.members.settings.login_attempt_limit_enabled_options.enabled'),
                        '0' => __('admin.settings.members.settings.login_attempt_limit_enabled_options.disabled'),
                    ];
                @endphp

                @include('components.form.radio-group', [
                    'name' => 'login_attempt_limit_enabled',
                    'options' => $loginAttemptLimitOptions,
                    'value' => old('login_attempt_limit_enabled', (string) (int) $loginAttemptLimitEnabled),
                ])

                <p class="mt-1 text-xs text-gray-500 dark:text-white">
                    {{ __('admin.settings.members.settings.login_attempt_limit_help') }}
                </p>
            </div>

            <!-- 設定詳細 -->
            <div id="login-attempt-details" class="space-y-6">
                <!-- 最大試行回数 -->
                <div>
                    <label for="login_attempt_max_attempts" class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                        {{ __('admin.settings.members.settings.login_attempt_max_attempts') }}
                    </label>
                    <input
                        type="number"
                        id="login_attempt_max_attempts"
                        name="login_attempt_max_attempts"
                        value="{{ old('login_attempt_max_attempts', $loginAttemptMaxAttempts) }}"
                        min="1"
                        max="100"
                        class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-white">
                        {{ __('admin.settings.members.settings.login_attempt_max_attempts_help') }}
                    </p>
                </div>

                <!-- 時間窓 -->
                <div>
                    <label for="login_attempt_time_window" class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                        {{ __('admin.settings.members.settings.login_attempt_time_window') }}
                    </label>
                    <input
                        type="number"
                        id="login_attempt_time_window"
                        name="login_attempt_time_window"
                        value="{{ old('login_attempt_time_window', $loginAttemptTimeWindow) }}"
                        min="1"
                        max="1440"
                        class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-white">
                        {{ __('admin.settings.members.settings.login_attempt_time_window_help') }}
                    </p>
                </div>

                <!-- ロックアウト時間 -->
                <div>
                    <label for="login_attempt_lockout_duration" class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                        {{ __('admin.settings.members.settings.login_attempt_lockout_duration') }}
                    </label>
                    <input
                        type="number"
                        id="login_attempt_lockout_duration"
                        name="login_attempt_lockout_duration"
                        value="{{ old('login_attempt_lockout_duration', $loginAttemptLockoutDuration) }}"
                        min="1"
                        max="10080"
                        class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-white">
                        {{ __('admin.settings.members.settings.login_attempt_lockout_duration_help') }}
                    </p>
                </div>

                <!-- ロックアウト通知設定 -->
                <div>
                    <label class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                        {{ __('admin.settings.members.settings.lockout_notification_enabled') }}
                    </label>

                    @php
                        $lockoutNotificationOptions = [
                            '1' => __('admin.settings.members.settings.lockout_notification_enabled_options.enabled'),
                            '0' => __('admin.settings.members.settings.lockout_notification_enabled_options.disabled'),
                        ];
                    @endphp

                    @include('components.form.radio-group', [
                        'name' => 'lockout_notification_enabled',
                        'options' => $lockoutNotificationOptions,
                        'value' => old('lockout_notification_enabled', (string) (int) $lockoutNotificationEnabled),
                    ])

                    <p class="mt-3 text-xs">
                        {!! __('admin.settings.members.settings.lockout_notification_help') !!}
                    </p>

                    @if(!$isMailServerTested)
                        <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 text-sm"></i>
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                        {!! __('admin.settings.members.settings.lockout_notification_mail_test_required', ['url' => route('admin.settings.base')]) !!}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>



        <!-- パスワードリセット機能設定 -->
        <div class="mt-8 border-t pt-6">
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.password_reset_settings') }}</h2>

            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-white mb-1">
                    {{ __('admin.settings.members.settings.password_reset_enabled') }}
                </label>

                @php
                    $passwordResetOptions = [
                        '1' => __('admin.settings.members.settings.password_reset_enabled_options.enabled'),
                        '0' => __('admin.settings.members.settings.password_reset_enabled_options.disabled'),
                    ];
                @endphp

                @include('components.form.radio-group', [
                    'name' => 'password_reset_enabled',
                    'options' => $passwordResetOptions,
                    'value' => old('password_reset_enabled', (string) (int) $passwordResetEnabled),
                ])

                <p class="mt-1 text-xs text-gray-500 dark:text-white">
                    {!! __('admin.settings.members.settings.password_reset_help') !!}
                </p>
            </div>
        </div>

        <!-- ログイン通知設定 -->
        <div class="mt-8 pt-6">
            <h2 class="border-b text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.login_notification_settings') }}</h2>

            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.settings.login_notification_global_setting') }}
                </label>

            @php
                $loginNotificationOptions = collect(config('admin.global_login_notification_mail_mode'))
                    ->mapWithKeys(fn ($value) => [$value => __('admin.settings.members.login_notification_mode.options.' . $value)])
                    ->toArray();
            @endphp

                @include('components.form.radio-group', [
                    'name' => 'login_notification_mode',
                    'options' => $loginNotificationOptions,
                    'value' => old('login_notification_mode', (string) $loginNotification),
                ])
            </div>
        </div>

        <!-- 二段階認証設定 -->
        <div class="mt-8  pt-6">
            <h2 class="border-b text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.two_factor_settings') }}</h2>

            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.two_factor_mode.label') }}
                </label>

                @php
                    $twoFactorOptions = collect(config('admin.global_two_factor_mode'))
                        ->mapWithKeys(fn ($value) => [$value => __('admin.settings.members.two_factor_mode.options.' . $value)])
                        ->toArray();
                @endphp

                @include('components.form.radio-group', [
                    'name' => 'force_2fa',
                    'options' => $twoFactorOptions,
                    'value' => old('force_2fa', (string) $force2fa),
                    'class' => '',
                ])
            </div>

            <!-- 二段階認証方法設定 -->
            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.settings.enabled_two_factor_methods_label') }}
                </label>

                <div class="space-y-3 mt-2">


                    <div class="flex">
                        <div>
                            <div class="">認証方法</div>
                            <div>
                                @foreach($twoFactorMethodOptions as $method)
                                    <div class="flex mr-5" data-method="{{ $method->value }}">
                                        <input
                                            id="enabled_two_factor_method_{{ $method->value }}"
                                            name="enabled_two_factor_methods[]"
                                            type="checkbox"
                                            value="{{ $method->value }}"
                                            {{ in_array($method->value, $enabledTwoFactorMethods ?? []) ? 'checked' : '' }}
                                            class="mb-2 w-4 h-4 border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <label for="enabled_two_factor_method_{{ $method->value }}" class="flex-1 ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $method->label() }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="text-center">デフォルト</div>
                            <div class="flex flex-col">
                                @foreach($twoFactorMethodOptions as $method)
                                    <div class="flex ">
                                        <input
                                            id="default_two_factor_method_{{ $method->value }}"
                                            name="default_two_factor_method"
                                            type="radio"
                                            value="{{ $method->value }}"
                                            {{ (isset($defaultTwoFactorMethod) && $defaultTwoFactorMethod === $method->value) || (!isset($defaultTwoFactorMethod) && $method->value === \App\Enums\TwoFactorMethod::EMAIL->value) ? 'checked' : '' }}
                                            class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 my-1">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>



                <div class="mt-3 space-y-1">
                    <p class="text-xs">
                        {{ __('admin.settings.members.settings.enabled_two_factor_methods_help') }}
                    </p>
                    <p class="text-xs">
                        {{ __('admin.settings.members.settings.default_two_factor_method_help') }}
                    </p>
                </div>
                </div>
            </div>
        </div>
    </form>

    <h2 class="border-b text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.force_logout_heading') }}</h2>
    <!-- 全メンバー強制ログアウト用フォーム -->
    <form id="force-logout-all-form" action="{{ route('admin.settings.members.force-logout-all') }}" method="POST">
        @csrf
    </form>

     <!-- 全メンバー強制ログアウトボタン -->
    @include('components::form.button', [
        'type' => 'button',
        'label' => __('admin.settings.members.settings.force_logout_all_button'),
        'class' => 'my-4 bg-orange-600 hover:bg-orange-700 text-white dark:bg-orange-500 dark:hover:bg-orange-600',
        'onclick' => "openModal('forceLogoutAllModal')"
    ])

@endsection

@section('save')
    <!-- 更新ボタン -->
    @include('components::form.button', [
        'type' => 'button',
        'label' => __('admin.settings.members.settings.update_button'),
        'class' => '',
        'onclick' => "openModal('confirmationModal')"
    ])
@endsection

@section('modals')
    <!-- 更新確認モーダル -->
    @include('components::form.modal', [
        'id' => 'confirmationModal',
        'title' => __('admin.settings.members.settings.confirm_title'),
        'message' => __('admin.settings.members.settings.confirm_message'),
        'confirm_label' => __('admin.settings.members.settings.confirm_label'),
        'cancel_label' => __('admin.settings.members.settings.cancel_label'),
        'form' => 'member-settings-form',
    ])

    <!-- 全メンバー強制ログアウト確認モーダル -->
    @include('components::form.modal', [
        'id' => 'forceLogoutAllModal',
        'title' => __('admin.settings.members.settings.force_logout_all_modal.title'),
        'message' => __('admin.settings.members.settings.force_logout_all_modal.message'),
        'confirm_label' => __('admin.settings.members.settings.force_logout_all_modal.confirm_label'),
        'cancel_label' => __('admin.settings.members.settings.force_logout_all_modal.cancel_label'),
        'form' => 'force-logout-all-form',
    ])

    <script>
        // Modal functions
        /*
        function openModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.remove('opacity-100', 'scale-100');
            modal.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
            document.body.style.overflow = 'auto';
        }
        */

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

            function updateDefaultMethodOptions() {
                const enabledValues = Array.from(enabledCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                defaultRadios.forEach(radio => {
                    const methodContainer = radio.closest('[data-method]');
                    if (enabledValues.includes(radio.value)) {
                        radio.disabled = false;
                        methodContainer.style.opacity = '1';
                    } else {
                        radio.disabled = true;
                        radio.checked = false;
                        methodContainer.style.opacity = '0.5';
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
        });
    </script>
@endsection
