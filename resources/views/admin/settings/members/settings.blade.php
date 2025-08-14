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
            </div>
        </div>

        <!-- ログイン通知設定 -->
        <div class="mt-8 border-t pt-6">
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.login_notification_settings') }}</h2>
            
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
        <div class="mt-8 border-t pt-6">
            <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.members.settings.two_factor_settings') }}</h2>
            
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
                ])
            </div>

            <!-- 二段階認証の方法 -->
            <div class="mb-6">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.settings.members.settings.two_factor_methods_label') }}
                </label>
                
                <div class="space-y-2 mt-2">
                    @foreach(\App\Enums\TwoFactorMethod::cases() as $method)
                    <div class="flex items-center">
                        <input 
                            id="two_factor_method_{{ $method->value }}" 
                            name="two_factor_methods[]" 
                            type="checkbox" 
                            value="{{ $method->value }}"
                            {{ in_array($method->value, $enabledTwoFactorMethods) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <label for="two_factor_method_{{ $method->value }}" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $method->label() }}
                        </label>
                    </div>
                    @endforeach
                </div>
                
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('admin.settings.members.settings.two_factor_methods_help') }}
                </p>
            </div>
        </div>

        

    </form>

@endsection

@section('save')
    <!-- 更新ボタンとモーダル-->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.members.settings.update_button'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.members.settings.confirm_title'),
        'message' => __('admin.settings.members.settings.confirm_message'),
        'confirm_label' => __('admin.settings.members.settings.confirm_label'),
        'cancel_label' => __('admin.settings.members.settings.cancel_label'),
        'form' => 'member-settings-form',
    ])
@endsection



