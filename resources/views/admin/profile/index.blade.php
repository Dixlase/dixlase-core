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

@extends('layouts.admin')

@section('content')

    <form method="POST" action="{{ route('admin.profile.update') }}" id="profile-form">
        @csrf

        <!-- 基本情報 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.basic_info') }}</h2>
            
            <fieldset>
                <legend>{{ __('common.name') }}</legend>
                @include('components.form.text', [
                    'name' => 'name',
                    'value' => old('name', $member->name),
                    'required' => true
                ])
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.description') }}</legend>
                @include('components.form.textarea', [
                    'name' => 'description',
                    'value' => old('description', $member->description),
                    'rows' => 3
                ])
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.email') }}</legend>
                @include('components.form.text', [
                    'name' => 'email',
                    'type' => 'email',
                    'value' => old('email', $member->email),
                    'required' => true
                ])
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.locale') }}</legend>
                @include('components.form.select', [
                    'name' => 'locale',
                    'options' => $localeOptions,
                    'value' => old('locale', $member->locale?->value),
                    'nullable' => true,
                    'nullLabel' => __('admin.profile.use_system_default')
                ])
                @error('locale')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p>{{ __('admin.profile.language_help') }}</p>
            </fieldset>
        </section>

        <!-- パスワード設定 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.password_settings') }}</h2>
            
            <fieldset>
                <legend>{{ __('admin.profile.password_change_only') }}</legend>
                @include('components.password-tools', [
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
            </fieldset>
        </section>

        <!-- 外観設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <section class="transition-colors-unified" x-data="{
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
        " data-profile-theme>
            <h2>{{ __('common.appearance_settings') }}</h2>
            
            <fieldset>
                <legend>{{ __('common.appearance_mode') }}</legend>
                @include('components::form.radio-group', [
                    'name' => 'appearance',
                    'options' => [
                        '0' => __('common.auto'),
                        '1' => __('common.light'),
                        '2' => __('common.dark')
                    ],
                    'value' => $appearanceValue,
                    'xModel' => 'localTheme'
                ])
            </fieldset>
        </section>

        <!-- ログイン通知設定 -->
        @php
            // 0 = 無効, 1 = 異なる端末/IP時のみ有効, 2 = 常に有効, 3 = プロフィール設定を反映
            $globalLoginNotification = (int) ($loginNoticeGlobal ?? 0);
            
            // 現在の通知モードを取得 (フォーム送信後の値 or 現在のユーザー設定 or デフォルト値 1 = 無効)
            $currentLoginNotificationMode = old('login_notification_mode', (string) ($loginNotificationMode ?? 1));
            
            // 全体設定が「無効」の場合はセクションを非表示
            $hideLoginNotificationSection = ($globalLoginNotification === 0);
            
            // 全体設定が「プロフィール設定を反映」の場合は設定を表示
            $showLoginNotificationSettings = ($globalLoginNotification === 3);
        @endphp

        <!-- ログイン通知設定 -->
        @unless($hideLoginNotificationSection)
            <section class="transition-colors-unified">
                <h2>{{ __('common.login_notification_mode.label') }}</h2>
                
                @if($showLoginNotificationSettings)
                    <fieldset>
                        <legend>{{ __('common.login_notification_mode.label') }}</legend>
                        @include('components.form.radio-group', [
                            'name' => 'login_notification_mode',
                            'options' => $loginNotificationOptions,
                            'value' => old('login_notification_mode', (string) ($loginNotificationMode?->value ?? 0)),
                        ])
                        <p>{{ __('common.login_notification_mode.help') }}</p>
                    </fieldset>
                @else
                    <fieldset>
                        <legend>{{ __('common.login_notification_mode.label') }}</legend>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">
                                    @if($globalLoginNotification === 1)
                                        {{ __('common.login_notification_mode.options.1') }}
                                    @elseif($globalLoginNotification === 2)
                                        {{ __('common.login_notification_mode.options.2') }}
                                    @endif
                                </span>
                            </p>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('admin.profile.login_notification_global_setting_help') }}
                            </p>
                        </div>
                    </fieldset>
                @endif
            </section>
        @endunless

        <!-- 二段階認証設定 -->
        @if($force2fa === \App\Enums\TwoFactorMode::UseProfileSetting->value || $currentGlobalTwoFactorMode)
            <section class="transition-colors-unified">
                <h2>{{ __('common.two_factor_mode.label') }}</h2>
                
                @if($force2fa === \App\Enums\TwoFactorMode::UseProfileSetting->value)
                    <fieldset>
                        <legend>{{ __('common.two_factor_mode.label') }}</legend>
                        @include('components.form.radio-group', [
                            'name' => 'two_factor_mode',
                            'options' => $profileTwoFactorOptions,
                            'value' => old('two_factor_mode', (string) ($twoFactorMode?->value ?? 0)),
                        ])
                        <p>{{ __('common.two_factor_help') }}</p>
                    </fieldset>
                @elseif($currentGlobalTwoFactorMode)
                    <fieldset>
                        <legend>{{ __('common.two_factor_mode.label') }}</legend>
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                            <p class="text-sm">
                                {{ str_replace(':account_type', __('common.account_types.member'), __('common.two_factor_mode.options.' . $currentGlobalTwoFactorMode->value)) }}
                            </p>
                            <p class="text-xs mt-1">
                                {{ __('common.two_factor_global_setting_fixed') }}
                            </p>
                        </div>
                    </fieldset>
                @endif

        <!-- 二段階認証方法設定 -->
            @if($showMethodSelection && !empty($availableMethodOptions))
                <fieldset>
                    <legend>{{ __('common.two_factor_method.label') }}</legend>
                    @php
                        // 現在の認証方法が有効な方法に含まれているか確認
                        $currentMethodValid = array_key_exists($currentTwoFactorMethod, $availableMethodOptions);
                        // デフォルトの認証方法を取得
                        $defaultMethod = $defaultTwoFactorMethod ?? array_key_first($availableMethodOptions);
                        // 現在の認証方法を決定（無効な場合はデフォルトを使用）
                        $currentMethod = $currentMethodValid ? $currentTwoFactorMethod : $defaultMethod;
                    @endphp

                    @if(count($availableMethodOptions) > 1)
                        @include('components.form.radio-group', [
                            'name' => 'two_factor_method',
                            'options' => $availableMethodOptions,
                            'value' => $currentMethod,
                            'disabled' => !$showMethodSelection
                        ])
                    @else
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ reset($availableMethodOptions) }}
                            </p>
                            <input type="hidden" name="two_factor_method" value="{{ key($availableMethodOptions) }}">
                        </div>
                    @endif

                    @error('two_factor_method')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <p>
                        {{ count($availableMethodOptions) > 1 ? 
                            str_replace(':account_type', __('common.account_types.member'), __('common.two_factor_method_help.multiple')) : 
                            str_replace(':account_type', __('common.account_types.member'), __('common.two_factor_method_help.single')) }}
                    </p>
                </fieldset>
            @elseif($currentGlobalTwoFactorMode && !empty($availableMethodOptions))
                <fieldset>
                    <legend>{{ __('common.two_factor_method.label') }}</legend>
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('common.two_factor_method_global_setting_fixed') }}
                        </p>
                    </div>
                </fieldset>
            @endif
            
        @endif
        </section>
    </form>

@endsection

@section('save')
    @include('components.save', [
        'id' => 'confirmationModal',
        'label' => __('common.update'),
        'onclick' => "openModal('confirmProfileModal')",
        'title' => __('admin.profile.confirm_title'),
        'message' => __('admin.profile.confirm_message'),
        'confirm_label' => __('common.update'),
        'cancel_label' => __('common.cancel'),
        'form' => 'profile-form',
    ])
@endsection

@section('scripts')
<!-- プロフィールページ専用のフォーム要素トランジション -->
<style>
    #profile-form input, 
    #profile-form textarea, 
    #profile-form select, 
    #profile-form button, 
    #profile-form fieldset, 
    #profile-form legend {
        transition: border-color var(--transition-duration) ease-in-out,
                   box-shadow var(--transition-duration) ease-in-out,
                   background-color var(--transition-duration) ease-in-out,
                   color var(--transition-duration) ease-in-out;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // フォーム送信成功時にグローバルテーマストアを更新
        @if(session('success'))
            const savedAppearance = '{{ old('appearance', (string) ($member->appearance->value ?? 0)) }}';
            if (window.themeStore) {
                window.themeStore.theme = savedAppearance;
                window.themeStore.applyTheme();
            }
        @endif

        // フォーム送信前にパスワード確認フィールドの処理
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const passwordField = document.getElementById('profile_password');
                const confirmationField = document.getElementById('profile_password_confirmation');
                
                // パスワードフィールドが空の場合、確認フィールドを削除
                if (passwordField && confirmationField) {
                    if (!passwordField.value.trim()) {
                        confirmationField.remove();
                    }
                }
            });
        }
    });
</script>
@endsection
