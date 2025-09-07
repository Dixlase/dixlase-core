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

    <form method="POST" action="{{ route('admin.profile.update') }}" id="profile-form" class="mb-8">
        @csrf

        <!-- 名前 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.profile.name') }}</label>
            <input type="text" name="name" value="{{ old('name', $member->name) }}"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- 説明 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.profile.description') }}</label>
            <textarea name="description"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2"
                rows="3">{{ old('description', $member->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- メールアドレス -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.profile.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $member->email) }}"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- パスワード -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.profile.password_change_only') }}</label>
            @include('components.form.password-tools', [
                'name' => 'password',
                'id' => 'profile_password',
                'required' => false,
                'minLength' => $passwordMinLength,
                'requireUppercase' => $passwordRequireUppercase,
                'requireLowercase' => true,
                'requireNumber' => true,
                'requireSymbol' => $passwordRequireSymbol,
                'showConfirmation' => true
            ])
        </div>

        <!-- 外観モードの設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <div x-data="{
            localTheme: '{{ $appearanceValue }}',
            savedTheme: '{{ $appearanceValue }}',
            applyLocalTheme() {
                const isDark = this.localTheme === '2' || (this.localTheme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.classList.toggle('light', !isDark);
            },
            resetToSavedTheme() {
                this.localTheme = this.savedTheme;
                this.applyLocalTheme();
            }
        }" x-init="
            // 初期化時に保存された値でDOMをリセット
            resetToSavedTheme();
            $watch('localTheme', () => applyLocalTheme());
        " class="mb-6" data-profile-theme>
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.profile.appearance_mode') }}</label>

            <div class="flex gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="0" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.profile.appearance_auto') }}</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="1" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.profile.appearance_light') }}</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="2" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.profile.appearance_dark') }}</span>
                </label>
            </div>
        </div>

        <!-- ログイン通知設定 -->
        @if($loginNoticeGlobal === 0)
            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.profile.login_notification_mode') }}
                </label>

                @include('components.form.radio-group', [
                    'name' => 'login_notification_mode',
                    'options' => $loginNotificationOptions,
                    'value' => old('login_notification_mode', (string) ($loginNotificationMode?->value ?? 0)),
                ])

                <div class="mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('admin.profile.login_notification_help') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- 二段階認証設定 -->
        @if($force2fa === \App\Enums\TwoFactorMode::UseProfileSetting->value)
            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.profile.two_factor_mode') }}
                </label>

                @include('components.form.radio-group', [
                    'name' => 'two_factor_mode',
                    'options' => $profileTwoFactorOptions,
                    'value' => old('two_factor_mode', (string) ($twoFactorMode?->value ?? 0)),
                ])

                <div class="mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('admin.profile.two_factor_help') }}
                    </p>
                </div>
            </div>
        @elseif($currentGlobalTwoFactorMode)
            <!-- 全体設定で固定されている場合の表示 -->
            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.profile.two_factor_mode') }}
                </label>
                
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('admin.profile.two_factor_mode_options.' . $currentGlobalTwoFactorMode->value) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('admin.profile.two_factor_global_setting_fixed') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- 二段階認証方法設定 -->
        @if($showMethodSelection && !empty($availableMethodOptions))
            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.profile.two_factor_method') }}
                    @if(count($availableMethodOptions) > 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            ({{ count($availableMethodOptions) }} {{ __('admin.profile.available_methods') }})
                        </span>
                    @endif
                </label>

                @php
                    // 現在の認証方法が有効な方法に含まれているか確認
                    $currentMethodValid = array_key_exists($currentTwoFactorMethod, $availableMethodOptions);
                    // デフォルトの認証方法を取得
                    $defaultMethod = $defaultTwoFactorMethod ?? array_key_first($availableMethodOptions);
                    // 現在の認証方法を決定（無効な場合はデフォルトを使用）
                    $currentMethod = $currentMethodValid ? $currentTwoFactorMethod : $defaultMethod;
                @endphp


                @if(count($availableMethodOptions) > 1)
                    
                    <div class="space-y-2">
                        @foreach($availableMethodOptions as $methodValue => $methodLabel)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="radio" id="two_factor_method_{{ $methodValue }}" 
                                           name="two_factor_method" 
                                           value="{{ $methodValue }}" 
                                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 mt-0.5"
                                           @if((string)$currentMethod === (string)$methodValue) checked @endif
                                           @if(!$showMethodSelection) disabled @endif>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="two_factor_method_{{ $methodValue }}" class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $methodLabel }}
                                        @if((int)$methodValue === (int)$defaultTwoFactorMethod)
                                            <span class="ml-1 text-xs text-primary-600 dark:text-primary-400">({{ __('admin.profile.default_method') }})</span>
                                        @endif
                                    </label>
                                    @if($methodValue == \App\Enums\TwoFactorMethod::EMAIL->value)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ __('admin.profile.two_factor_method_email_help') }}
                                        </p>
                                    @elseif($methodValue == \App\Enums\TwoFactorMethod::DEVICE->value)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ __('admin.profile.two_factor_method_device_help') }}
                                        </p>
                                    @elseif($methodValue == \App\Enums\TwoFactorMethod::BIOMETRIC->value)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ __('admin.profile.two_factor_method_biometric_help') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ reset($availableMethodOptions) }}
                            @if((int)key($availableMethodOptions) === (int)$defaultTwoFactorMethod)
                                <span class="ml-1 text-xs text-primary-600 dark:text-primary-400">({{ __('admin.profile.only_method_available') }})</span>
                            @endif
                        </p>
                        @if(isset($availableMethodOptions[\App\Enums\TwoFactorMethod::EMAIL->value]))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('admin.profile.two_factor_method_email_help') }}
                            </p>
                        @elseif(isset($availableMethodOptions[\App\Enums\TwoFactorMethod::DEVICE->value]))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('admin.profile.two_factor_method_device_help') }}
                            </p>
                        @elseif(isset($availableMethodOptions[\App\Enums\TwoFactorMethod::BIOMETRIC->value]))
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('admin.profile.two_factor_method_biometric_help') }}
                            </p>
                        @endif
                        <input type="hidden" name="two_factor_method" value="{{ key($availableMethodOptions) }}">
                    </div>
                @endif

                @if($errors->has('two_factor_method'))
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ $errors->first('two_factor_method') }}
                    </p>
                @endif

                <div class="mt-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ count($availableMethodOptions) > 1 ? 
                            __('admin.profile.two_factor_method_help_multiple') : 
                            __('admin.profile.two_factor_method_help_single') }}
                    </p>
                </div>
            </div>
        @elseif($currentGlobalTwoFactorMode && !empty($availableMethodOptions))
            <!-- 全体設定で固定されている場合の認証方法表示 -->
            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('admin.profile.two_factor_method' )}}
                    @if(count($availableMethodOptions) > 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            ({{ count($availableMethodOptions) }} {{ __('admin.profile.available_methods') }})
                        </span>
                    @endif
                </label>
                
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $availableMethodOptions[$currentTwoFactorMethod] ?? __('admin.profile.two_factor_method_not_set') }}
                        @if(isset($availableMethodOptions[$currentTwoFactorMethod]) && (int)$currentTwoFactorMethod === (int)$defaultTwoFactorMethod)
                            <span class="ml-1 text-xs text-primary-600 dark:text-primary-400">({{ __('admin.profile.default_method') }})</span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('admin.profile.two_factor_method_global_setting_fixed') }}
                    </p>
                </div>
            </div>
        @endif
    </form>

@endsection

@section('save')
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.profile.update_button'),
        'onclick' => "openModal('confirmProfileModal')",
        'title' => __('admin.profile.confirm_title'),
        'message' => __('admin.profile.confirm_message'),
        'confirm_label' => __('admin.profile.confirm_label'),
        'cancel_label' => __('admin.profile.cancel_label'),
        'form' => 'profile-form',
    ])
@endsection

@section('scripts')
    document.addEventListener('DOMContentLoaded', function() {
        // フォーム送信成功時にグローバルテーマストアを更新
        @if(session('success'))
            const savedAppearance = '{{ old('appearance', (string) ($member->appearance->value ?? 0)) }}';
            if (window.themeStore) {
                window.themeStore.theme = savedAppearance;
                window.themeStore.applyTheme();
            }
        @endif
    });
@endsection
