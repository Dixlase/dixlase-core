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
                    'id' => 'profile_email',
                    'type' => 'email',
                    'value' => old('email', $member->email),
                    'required' => true
                ])
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                
                @if($hasPendingEmail)
                    <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            {!! __('admin.profile.pending_email_notice', ['email' => $pendingEmail]) !!}
                        </p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                            {{ __('admin.profile.current_email', ['email' => $member->email]) }}
                        </p>
                    </div>
                @else
                    <p class="description-text">
                        @if($isMailServerTested)
                            {!! __('admin.profile.email_change_help') !!}
                        @else
                            {!! __('admin.profile.email_change_help_no_mail') !!}
                        @endif
                    </p>
                @endif
            </fieldset>

            {{-- メールアドレス確認フィールド（メールアドレス変更時のみ表示） --}}
            <fieldset id="profile-email-confirmation-field" style="display: none;">
                <legend>{{ __('admin.settings.members.form.email_confirmation') }}</legend>
                @include('components.form.text', [
                    'type' => 'email',
                    'id' => 'profile_email_confirmation',
                    'name' => 'email_confirmation',
                    'value' => old('email_confirmation'),
                    'required' => false,
                    'autocomplete' => 'off',
                    'onpaste' => 'return false',
                    'oncopy' => 'return false',
                    'oncut' => 'return false',
                ])
                <p class="description-text">{{ __('admin.settings.members.form.email_confirmation_help') }}</p>
                @error('email_confirmation')
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
                        <p>{!! __('common.two_factor_help') !!}</p>
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

    <!-- 2FA管理セクション -->
    <section class="mt-8 transition-colors-unified">
        <h2>{{ __('admin.profile.2fa_management') }}</h2>

        <!-- Passkeyデバイス -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Passkeyデバイス</h3>
                @if(!$passkeyDevices->isEmpty())
                    <button 
                        type="button"
                        onclick="openModal('deleteAllPasskeysModal')"
                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                        <i class="fas fa-trash-alt mr-1"></i>全て削除
                    </button>
                @endif
            </div>
            
            @if($passkeyDevices->isEmpty())
                <p class="text-gray-600 dark:text-gray-400 mb-4">Passkeyデバイスが登録されていません</p>
            @else
                <div class="space-y-4 mb-4">
                    @foreach($passkeyDevices as $device)
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-key text-green-600 dark:text-green-400 mr-2"></i>
                                    <h4 class="font-semibold">{{ $device->name }}</h4>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p><strong>登録日時:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                    @if($device->last_used_at)
                                        <p><strong>最終使用:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                            <button 
                                type="button"
                                onclick="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                                class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                                削除
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
            <!-- 新しいPasskeyを追加 -->
            <button 
                type="button"
                onclick="registerPasskey()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                <i class="fas fa-plus mr-2"></i>Passkeyを追加
            </button>

            <!-- Passkeyの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin.profile.passkey_info_title') }}
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                    <li>{{ __('admin.profile.passkey_info_1') }}</li>
                    <li>{{ __('admin.profile.passkey_info_2') }}</li>
                    <li>{{ __('admin.profile.passkey_info_3') }}</li>
                </ul>
            </div>


        </div>

        <!-- 回復コード -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('two-factor.recovery_codes.title') }}</h3>
            </div>

            @if($hasRecoveryCodes)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        {{ __('two-factor.recovery_codes.remaining', ['count' => $recoveryCodesCount]) }}
                    </p>
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('two-factor.recovery_codes.none') }}</p>
            @endif
            
            <button 
                type="button"
                onclick="openModal('recoveryCodesConfirmModal')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-{{ $hasRecoveryCodes ? 'sync-alt' : 'plus' }} mr-2"></i>{{ __('two-factor.recovery_codes.' . ($hasRecoveryCodes ? 'regenerate' : 'generate')) }}
            </button>
            
            <!-- 回復コードの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin.profile.recovery_codes_info_title') }}
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                    <li>{{ __('admin.profile.recovery_codes_info_1') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_2') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_3') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_4') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_5') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_6') }}</li>
                </ul>
            </div>            
        </div>
    </section>

    <!-- 信頼済みデバイス削除モーダル -->
    @push('scripts')
    <script>
        // モーダルの確認ボタンにイベントリスナーを追加
        document.addEventListener('DOMContentLoaded', function() {
            // 信頼済みデバイス削除
            const deleteTrustedDeviceBtn = document.querySelector('#deleteTrustedDeviceModal .modal-actions button[type="button"]:last-child');
            if (deleteTrustedDeviceBtn) {
                deleteTrustedDeviceBtn.addEventListener('click', revokeTrustedDevice);
            }

            // 信頼済みデバイス一括削除
            const deleteAllTrustedDevicesBtn = document.querySelector('#deleteAllTrustedDevicesModal .modal-actions button[type="button"]:last-child');
            if (deleteAllTrustedDevicesBtn) {
                deleteAllTrustedDevicesBtn.addEventListener('click', revokeAllTrustedDevices);
            }

            // Passkey削除
            const deletePasskeyBtn = document.querySelector('#deletePasskeyModal .modal-actions button[type="button"]:last-child');
            if (deletePasskeyBtn) {
                deletePasskeyBtn.addEventListener('click', revokePasskey);
            }

            // Passkey一括削除
            const deleteAllPasskeysBtn = document.querySelector('#deleteAllPasskeysModal .modal-actions button[type="button"]:last-child');
            if (deleteAllPasskeysBtn) {
                deleteAllPasskeysBtn.addEventListener('click', revokeAllPasskeys);
            }

            // 回復コード生成/再生成確認
            const recoveryCodesConfirmBtn = document.querySelector('#recoveryCodesConfirmModal .modal-actions button[type="button"]:last-child');
            if (recoveryCodesConfirmBtn) {
                console.log('[DEBUG] Found recovery codes confirm button');
                recoveryCodesConfirmBtn.addEventListener('click', function() {
                    console.log('[DEBUG] Recovery codes confirm button clicked');
                    confirmGenerateRecoveryCodes();
                });
            } else {
                console.error('[DEBUG] Recovery codes confirm button not found');
            }

            // フラッシュメッセージの表示
            const flashSuccess = sessionStorage.getItem('flash_success');
            const flashError = sessionStorage.getItem('flash_error');
            
            if (flashSuccess) {
                alert(flashSuccess);
                sessionStorage.removeItem('flash_success');
            }
            
            if (flashError) {
                alert(flashError);
                sessionStorage.removeItem('flash_error');
            }
        });
    </script>
    @endpush

    <x-modal 
        id="deleteTrustedDeviceModal"
        :title="__('admin.profile.confirm_delete_device_title')"
        :message="__('admin.profile.confirm_delete_device_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deleteAllTrustedDevicesModal"
        :title="__('admin.profile.confirm_delete_all_devices_title')"
        :message="__('admin.profile.confirm_delete_all_devices_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deletePasskeyModal"
        :title="__('admin.profile.confirm_delete_passkey_title')"
        :message="__('admin.profile.confirm_delete_passkey_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deleteAllPasskeysModal"
        :title="__('admin.profile.confirm_delete_all_passkeys_title')"
        :message="__('admin.profile.confirm_delete_all_passkeys_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <!-- 自動生成された回復コード表示モーダル（プロフィール保存時） -->
    @if(session('auto_generated_recovery_codes'))
        <x-recovery-codes-modal
            modalId="profileAutoGeneratedRecoveryCodesModal"
            :title="__('two-factor.recovery_codes.auto_generated_title')"
            :codes="session('auto_generated_recovery_codes')"
            :isAutoGenerated="true"
            :autoOpen="true"
        />
    @endif

    <!-- 回復コード再生成エラーモーダル -->
    @if(session('recovery_code_error'))
        <x-recovery-codes-modal
            modalId="recoveryCodeErrorModal"
            :error="session('recovery_code_error')"
            :autoOpen="true"
        />
    @endif

@endsection

@section('save')
    @include('components.save', [
        'id_confirmation' => 'confirmProfileModal',
        'label' => __('common.update'),
        'title' => __('admin.profile.confirm_title'),
        'message' => __('admin.profile.confirm_message'),
        'confirm_label' => __('common.update'),
        'cancel_label' => __('common.cancel'),
        'form' => 'profile-form',
    ])
@endsection

@push('scripts')
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
    // === グローバル関数（モーダルから呼び出される） ===
    
    // 信頼済みデバイス管理
    let currentDeviceId = null;
    
    window.openDeleteTrustedDeviceModal = function(deviceId, deviceName) {
        currentDeviceId = deviceId;
        const modal = document.getElementById('deleteTrustedDeviceModal');
        const messageElement = modal.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `{{ __('admin.profile.confirm_delete_device_message') }}\n\n${deviceName}`;
        }
        openModal('deleteTrustedDeviceModal');
    };

    window.revokeTrustedDevice = function() {
        if (!currentDeviceId) return;

        fetch(`/admin/profile/trusted-device/${currentDeviceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            closeModal('deleteTrustedDeviceModal');
            if (data.success) {
                sessionStorage.setItem('flash_success', data.message);
                location.reload();
            } else {
                sessionStorage.setItem('flash_error', data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteTrustedDeviceModal');
            sessionStorage.setItem('flash_error', '{{ __('admin.profile.delete_device_error') }}');
            location.reload();
        });
    };

    window.revokeAllTrustedDevices = function() {
        fetch('/admin/profile/trusted-device/all', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            closeModal('deleteAllTrustedDevicesModal');
            if (data.success) {
                sessionStorage.setItem('flash_success', data.message);
                location.reload();
            } else {
                sessionStorage.setItem('flash_error', data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteAllTrustedDevicesModal');
            sessionStorage.setItem('flash_error', '{{ __('admin.profile.delete_all_devices_error') }}');
            location.reload();
        });
    };
    
    // Passkey管理
    let currentCredentialId = null;
    
    window.openDeletePasskeyModal = function(credentialId, credentialName) {
        currentCredentialId = credentialId;
        const modal = document.getElementById('deletePasskeyModal');
        const messageElement = modal.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `{{ __('admin.profile.confirm_delete_passkey_message') }}\n\n${credentialName}`;
        }
        openModal('deletePasskeyModal');
    };

    window.revokePasskey = function() {
        console.log('[Passkey Delete] Function called');
        console.log('[Passkey Delete] currentCredentialId:', currentCredentialId);
        
        if (!currentCredentialId) {
            console.error('[Passkey Delete] No credential ID found');
            return;
        }

        const url = `/admin/profile/passkey/${currentCredentialId}`;
        console.log('[Passkey Delete] Sending DELETE request to:', url);

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('[Passkey Delete] Response status:', response.status);
            console.log('[Passkey Delete] Response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('[Passkey Delete] Response data:', data);
            closeModal('deletePasskeyModal');
            if (data.success) {
                console.log('[Passkey Delete] Success - reloading page');
                sessionStorage.setItem('flash_success', data.message);
                location.reload();
            } else {
                console.log('[Passkey Delete] Failed:', data.message);
                sessionStorage.setItem('flash_error', data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('[Passkey Delete] Error:', error);
            console.error('[Passkey Delete] Error stack:', error.stack);
            closeModal('deletePasskeyModal');
            sessionStorage.setItem('flash_error', '{{ __('admin.profile.passkey_delete_error') }}');
            location.reload();
        });
    };

    window.revokeAllPasskeys = function() {
        fetch('/admin/profile/passkey/all', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            closeModal('deleteAllPasskeysModal');
            if (data.success) {
                sessionStorage.setItem('flash_success', data.message);
                location.reload();
            } else {
                sessionStorage.setItem('flash_error', data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteAllPasskeysModal');
            sessionStorage.setItem('flash_error', '{{ __('admin.profile.passkey_delete_all_error') }}');
            location.reload();
        });
    };
    
    // Passkey登録機能
    window.registerPasskey = async function() {
        try {
            // WebAuthn対応チェック
            if (!window.PublicKeyCredential) {
                alert('{{ __('admin.profile.passkey_not_supported') }}');
                return;
            }

            console.log('[Passkey] 登録開始');

            // サーバーから登録チャレンジを取得
            const optionsResponse = await fetch('/admin/profile/passkey/register-options', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!optionsResponse.ok) {
                throw new Error('チャレンジの取得に失敗しました');
            }

            const { success, options } = await optionsResponse.json();
            
            if (!success || !options) {
                throw new Error('チャレンジの取得に失敗しました');
            }

            console.log('[Passkey] チャレンジ取得成功', options);

            // Base64文字列をArrayBufferに変換
            const challengeBuffer = base64urlToBuffer(options.challenge);
            const userIdBuffer = base64urlToBuffer(options.user.id);

            // WebAuthn登録オプションを準備
            const publicKeyCredentialCreationOptions = {
                challenge: challengeBuffer,
                rp: options.rp,
                user: {
                    id: userIdBuffer,
                    name: options.user.name,
                    displayName: options.user.displayName
                },
                pubKeyCredParams: options.pubKeyCredParams,
                timeout: options.timeout,
                attestation: options.attestation,
                authenticatorSelection: options.authenticatorSelection
            };

            console.log('[Passkey] WebAuthn登録開始');

            // WebAuthn APIで認証情報を作成
            const credential = await navigator.credentials.create({
                publicKey: publicKeyCredentialCreationOptions
            });

            if (!credential) {
                throw new Error('認証情報の作成に失敗しました');
            }

            console.log('[Passkey] 認証情報作成成功', credential);

            // デバイス名を入力
            const deviceName = prompt('{{ __('admin.profile.passkey_device_name_prompt') }}', '');
            
            if (deviceName === null) {
                console.log('[Passkey] ユーザーがキャンセルしました');
                return;
            }

            // 認証情報をサーバーに送信
            const credentialData = {
                id: credential.id,
                rawId: bufferToBase64url(credential.rawId),
                response: {
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    attestationObject: bufferToBase64url(credential.response.attestationObject)
                },
                type: credential.type
            };

            console.log('[Passkey] サーバーに送信', credentialData);

            const registerResponse = await fetch('/admin/profile/passkey/register', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    credential: credentialData,
                    device_name: deviceName || null
                })
            });

            const result = await registerResponse.json();

            if (result.success) {
                sessionStorage.setItem('flash_success', result.message);
                location.reload();
            } else {
                alert(result.message || '{{ __('admin.profile.passkey_register_error') }}');
            }

        } catch (error) {
            console.error('[Passkey] 登録エラー:', error);
            
            if (error.name === 'NotAllowedError') {
                alert('{{ __('admin.profile.passkey_cancelled') }}');
            } else if (error.name === 'InvalidStateError') {
                alert('{{ __('admin.profile.passkey_already_registered') }}');
            } else {
                alert('{{ __('admin.profile.passkey_register_error') }}\n\n' + error.message);
            }
        }
    };

    // Base64URL文字列をArrayBufferに変換
    function base64urlToBuffer(base64url) {
        const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        const binary = atob(base64);
        const buffer = new ArrayBuffer(binary.length);
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return buffer;
    }

    // ArrayBufferをBase64URL文字列に変換
    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        const base64 = btoa(binary);
        return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // フォーム送信成功時にグローバルテーマストアを更新
        @if(session('success'))
            const savedAppearance = '{{ old('appearance', (string) ($member->appearance->value ?? 0)) }}';
            if (window.themeStore) {
                window.themeStore.theme = savedAppearance;
                window.themeStore.applyTheme();
            }
        @endif

        // メールアドレス変更の監視
        const emailInput = document.getElementById('profile_email');
        const emailConfirmationField = document.getElementById('profile-email-confirmation-field');
        const emailConfirmationInput = document.getElementById('profile_email_confirmation');
        const originalEmail = '{{ $member->email }}';
        
        if (emailInput && emailConfirmationField && emailConfirmationInput) {
            // バリデーションエラーがある場合、または old値がある場合は初期表示
            @if($errors->has('email_confirmation') || old('email_confirmation'))
                emailConfirmationField.style.display = 'block';
                emailConfirmationInput.required = true;
            @endif
            
            // メールアドレスの変更を監視
            emailInput.addEventListener('input', function() {
                if (this.value !== originalEmail && this.value !== '') {
                    // メールアドレスが変更された場合は確認フィールドを表示
                    emailConfirmationField.style.display = 'block';
                    emailConfirmationInput.required = true;
                } else {
                    // 元に戻した場合は確認フィールドを非表示
                    emailConfirmationField.style.display = 'none';
                    emailConfirmationInput.required = false;
                    emailConfirmationInput.value = '';
                }
            });
        }

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

        // Passkey追加
        const addBiometricBtn = document.getElementById('add-biometric-btn');
        if (addBiometricBtn) {
            addBiometricBtn.addEventListener('click', async function() {
                try {
                    // WebAuthn対応チェック
                    if (!window.PublicKeyCredential) {
                        alert('{{ __('admin.profile.webauthn_not_supported') }}');
                        return;
                    }

                    // チャレンジ生成
                    const challengeResponse = await fetch('/admin/profile/biometric/challenge', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const challengeData = await challengeResponse.json();
                    if (!challengeData.success) {
                        alert(challengeData.message);
                        return;
                    }

                    const challenge = challengeData.challenge;

                    // Base64URLデコード
                    const challengeBuffer = Uint8Array.from(atob(challenge.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                    const userIdBuffer = Uint8Array.from(atob(challenge.user.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));

                    // WebAuthn登録
                    const credential = await navigator.credentials.create({
                        publicKey: {
                            challenge: challengeBuffer,
                            rp: challenge.rp,
                            user: {
                                id: userIdBuffer,
                                name: challenge.user.name,
                                displayName: challenge.user.displayName
                            },
                            pubKeyCredParams: challenge.pubKeyCredParams,
                            timeout: challenge.timeout,
                            attestation: challenge.attestation,
                            authenticatorSelection: challenge.authenticatorSelection
                        }
                    });

                    // デバイス名を入力
                    const deviceName = prompt('{{ __('admin.profile.enter_device_name') }}', '');

                    // 登録
                    const registerResponse = await fetch('/admin/profile/biometric/register', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            credential: {
                                id: credential.id,
                                rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                                response: {
                                    attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject))),
                                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON)))
                                },
                                type: credential.type
                            },
                            device_name: deviceName
                        })
                    });

                    const registerData = await registerResponse.json();
                    if (registerData.success) {
                        alert(registerData.message);
                        location.reload();
                    } else {
                        alert(registerData.message);
                    }
                } catch (error) {
                    console.error('Biometric registration error:', error);
                    if (error.name === 'NotAllowedError') {
                        alert('{{ __('admin.profile.biometric_cancelled') }}');
                    } else {
                        alert('{{ __('admin.profile.biometric_registration_error') }}');
                    }
                }
            });
        }
    });


    // 回復コード生成/再生成確認
    async function confirmGenerateRecoveryCodes() {
        closeModal('recoveryCodesConfirmModal');
        
        try {
            const response = await fetch('{{ route("admin.profile.recovery-codes.generate") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // 成功：回復コードを表示
                displayRecoveryCodesInModal('manualRecoveryCodesModal', data.codes);
                openModal('manualRecoveryCodesModal');
            } else {
                // エラー：エラーメッセージを表示
                displayRecoveryCodesError('manualRecoveryCodesModal', data.message || '{{ __("common.error") }}');
                openModal('manualRecoveryCodesModal');
            }
        } catch (error) {
            console.error('Recovery codes error:', error);
            // エラー：エラーメッセージを表示
            displayRecoveryCodesError('manualRecoveryCodesModal', '{{ __("common.error") }}');
            openModal('manualRecoveryCodesModal');
        }
    }
</script>
@endpush

<!-- 回復コード表示モーダル（自動生成用） -->
@if(session('auto_generated_recovery_codes'))
<x-recovery-codes-modal
    modalId="profileAutoGeneratedRecoveryCodesModal"
    :title="__('two-factor.recovery_codes.title')"
    :codes="session('auto_generated_recovery_codes')"
    :isAutoGenerated="true"
    :autoOpen="true"
    :clearSessionRoute="route('admin.profile.recovery-codes.clear-session')"
/>
@endif

<!-- 回復コード表示モーダル（手動生成用・エラー表示兼用） -->
<x-recovery-codes-modal
    modalId="manualRecoveryCodesModal"
    :title="__('two-factor.recovery_codes.title')"
    :codes="[]"
    :isDynamic="true"
/>


<!-- 回復コード生成/再生成確認モーダル -->
<x-modal 
    id="recoveryCodesConfirmModal" 
    :title="__('two-factor.recovery_codes.generate')"
    :message="__('admin.profile.recovery_codes_generate_confirm')"
    confirm_label="{{ __('common.ok') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="warning"
    confirm_color="blue"
/>
