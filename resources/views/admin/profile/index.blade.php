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
                <legend>{{ __('common.account_name') }}</legend>
                <x-form.text
                    name="account_name"
                    :value="old('account_name', $member->account_name)"
                    :required="true"
                    pattern="^[a-zA-Z0-9]+$"
                    minlength="3"
                    maxlength="20"
                    class="w-full"
                />
                <p class="description-text">{!! __('admin/profile.account_name_help') !!}</p>
                @error('account_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.display_name') }}</legend>
                <x-form.text
                    name="display_name"
                    :value="old('display_name', $member->display_name)"
                    class="w-full"
                />
                <p class="description-text">{{ __('admin/profile.display_name_help') }}</p>
                @error('display_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.description') }}</legend>
                <x-form.textarea
                    name="description"
                    :value="old('description', $member->description)"
                    :rows="3"
                    class="w-full"
                />
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-email-input
                id="profile_email"
                name="email"
                :value="old('email', $member->email)"
                :required="false"
                :showConfirmation="true"
                :showConfirmationOnChange="true"
            />
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            @error('email_confirmation')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            
            @if($hasPendingEmail)
                <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {!! __('admin/profile.pending_email_notice', ['email' => $pendingEmail]) !!}
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('admin/profile.current_email', ['email' => $member->email]) }}
                    </p>
                </div>
            @else
                <p class="description-text">
                    @if($isMailServerTested)
                        {!! __('admin/profile.email_change_help') !!}
                    @else
                        {!! __('admin/profile.email_change_help_no_mail') !!}
                    @endif
                </p>
            @endif

            <fieldset>
                <legend>{{ __('common.locale') }}</legend>
                <x-form.select
                    name="locale"
                    :options="$localeOptions"
                    :value="old('locale', $member->locale?->value)"
                    :nullable="true"
                    :nullLabel="__('admin/profile.use_system_default')"
                />
                @error('locale')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p>{{ __('admin/profile.language_help') }}</p>
            </fieldset>
        </section>

        <!-- パスワード設定 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.password_settings') }}</h2>
            
            <fieldset>
                <legend>{{ __('admin/profile.password_change_only') }}</legend>
                <x-password-tools
                    name="password"
                    id="profile_password"
                    :required="false"
                    :minLength="$passwordMinLength"
                    :requireUppercase="$passwordRequireUppercase"
                    :requireLowercase="true"
                    :requireNumber="true"
                    :requireSymbol="$passwordRequireSymbol"
                    :showConfirmation="true"
                    :showConfirmationOnChange="true"
                />
            </fieldset>
        </section>

        <!-- 外観設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <section class="transition-colors-unified">
            <h2>{{ __('common.appearance_settings') }}</h2>
            <div class="lg:w-1/2">
                <x-appearance-mode-selector
                    name="appearance"
                    :value="$appearanceValue"
                    :enableRealtimeSwitch="true"
                    :columns="3"
                    color="primary"
                    variant="filled"
                    :showCheck="true"
                />
            </div>
        </section>

        <!-- ログイン通知設定 -->
        @php
            $loginNotificationModeValue = $loginNotificationMode instanceof \App\Enums\AuthenticationMode 
                ? $loginNotificationMode->value 
                : ($loginNotificationMode ?? 1);
        @endphp

        <section class="transition-colors-unified">
            <h2>{{ __('auth.login_notification_mode.label') }}</h2>
            <x-login-notification-selector
                name="login_notification_mode"
                :value="old('login_notification_mode', (string) $loginNotificationModeValue)"
                :globalSetting="(int) ($loginNoticeGlobal ?? 0)"
                :excludeUseProfileSetting="true"
                :columns="3"
            />
        </section>

        <!-- 二段階認証設定（メールサーバー設定済みの場合のみ表示） -->
        @if($isMailServerTested && ($force2fa === \App\Enums\AuthenticationMode::UseProfileSetting->value || $currentGlobalTwoFactorMode))
            @php
                $member = Auth::guard('member')->user();
                $passkeyGloballyEnabled = in_array(\App\Enums\TwoFactorMethod::PASSKEY->value, array_keys($enabledTwoFactorMethods ?? []));
            @endphp
            
            <section class="transition-colors-unified">
                <h2>{{ __('auth.two_factor_mode.label') }}</h2>
                
                <x-two-factor-auth-selector
                    name="two_factor_mode"
                    :value="old('two_factor_mode', (string) ($twoFactorMode?->value ?? 0))"
                    :globalSetting="$force2fa"
                    :excludeUseProfileSetting="true"
                    :passkeyGloballyEnabled="$passkeyGloballyEnabled"
                    :passkeyEnabled="$member->two_factor_passkey_enabled ?? true"
                    :defaultTwoFactorMethod="(string) ($member->default_two_factor_method ?? $defaultTwoFactorMethod)"
                    :columns="3"
                />
            </section>
        @endif
    </form>

    <!-- 2FA管理セクション（メールサーバー設定済み、かつ二段階認証が有効の場合のみ表示） -->
    @if($isMailServerTested && $force2fa !== \App\Enums\AuthenticationMode::Disabled->value)
        <x-two-factor-management
            :passkeyEnabled="$passkeyEnabled"
            :passkeyDevices="$passkeyDevices"
            :hasRecoveryCodes="$hasRecoveryCodes"
            :recoveryCodesCount="$recoveryCodesCount"
        />
    @endif

    <!-- 信頼済みデバイス削除モーダル -->
    @push('scripts')
    <script @cspNonce>
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

            // 手動生成回復コードモーダルを閉じた時にページリロード
            const manualRecoveryCodesCloseBtn = document.getElementById('manualRecoveryCodesModal-close-btn');
            console.log('[DEBUG] manualRecoveryCodesCloseBtn:', manualRecoveryCodesCloseBtn);
            if (manualRecoveryCodesCloseBtn) {
                console.log('[DEBUG] Adding click listener to manual recovery codes close button');
                manualRecoveryCodesCloseBtn.addEventListener('click', function() {
                    console.log('[DEBUG] Manual recovery codes close button clicked');
                    // チェックボックスが有効な場合（コードが表示されている場合）のみリロード
                    const checkbox = document.getElementById('manualRecoveryCodesModal-saved-checkbox');
                    console.log('[DEBUG] Checkbox:', checkbox);
                    console.log('[DEBUG] Checkbox disabled:', checkbox ? checkbox.disabled : 'N/A');
                    if (checkbox && !checkbox.disabled) {
                        console.log('[DEBUG] Reloading page...');
                        location.reload();
                    } else {
                        console.log('[DEBUG] Not reloading - checkbox is disabled or not found');
                    }
                });
            } else {
                console.error('[DEBUG] Manual recovery codes close button not found');
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
        :title="__('admin/profile.confirm_delete_device_title')"
        :message="__('admin/profile.confirm_delete_device_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deleteAllTrustedDevicesModal"
        :title="__('admin/profile.confirm_delete_all_devices_title')"
        :message="__('admin/profile.confirm_delete_all_devices_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deletePasskeyModal"
        :title="__('admin/profile.confirm_delete_passkey_title')"
        :message="__('admin/profile.confirm_delete_passkey_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <x-modal 
        id="deleteAllPasskeysModal"
        :title="__('admin/profile.confirm_delete_all_passkeys_title')"
        :message="__('admin/profile.confirm_delete_all_passkeys_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
    />

    <!-- 自動生成された回復コード表示モーダル（プロフィール保存時） -->
    @if(session('auto_generated_recovery_codes'))
        @include('two-factor.partials.recovery-codes-modal', [
            'modalId' => 'profileAutoGeneratedRecoveryCodesModal',
            'title' => __('two-factor.recovery_codes.auto_generated_title'),
            'codes' => session('auto_generated_recovery_codes'),
            'isAutoGenerated' => true,
            'autoOpen' => true
        ])
    @endif

    <!-- 回復コード再生成エラーモーダル -->
    @if(session('recovery_code_error'))
        @include('two-factor.partials.recovery-codes-modal', [
            'modalId' => 'recoveryCodeErrorModal',
            'error' => session('recovery_code_error'),
            'autoOpen' => true
        ])
    @endif

    <!-- Passkeyデバイス名入力モーダル -->
    @include('two-factor.partials.passkey-device-name-modal', [
        'modalId' => 'passkeyDeviceNameModal'
    ])

    <!-- Passkey登録結果モーダル -->
    @include('two-factor.partials.passkey-result-modal', [
        'modalId' => 'passkeyResultModal'
    ])

@endsection

@section('save')
    <x-save
        id_confirmation="confirmProfileModal"
        :label="__('common.update')"
        :title="__('admin/profile.confirm_title')"
        :message="__('admin/profile.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-form"
    />
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

<script @cspNonce>
    // === グローバル関数（モーダルから呼び出される） ===
    
    // 信頼済みデバイス管理
    let currentDeviceId = null;
    
    window.openDeleteTrustedDeviceModal = function(deviceId, deviceName) {
        currentDeviceId = deviceId;
        const modal = document.getElementById('deleteTrustedDeviceModal');
        const messageElement = modal.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `{{ __('admin/profile.confirm_delete_device_message') }}\n\n${deviceName}`;
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
                window.PasskeyResultModal.showSuccess(
                    'passkeyResultModal',
                    '{{ __('admin/profile.device_delete_success_title') }}',
                    data.message,
                    () => location.reload()
                );
            } else {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    data.message
                );
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteTrustedDeviceModal');
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                '{{ __('admin/profile.delete_device_error') }}'
            );
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
                window.PasskeyResultModal.showSuccess(
                    'passkeyResultModal',
                    '{{ __('admin/profile.device_delete_success_title') }}',
                    data.message,
                    () => location.reload()
                );
            } else {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    data.message
                );
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteAllTrustedDevicesModal');
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                '{{ __('admin/profile.delete_all_devices_error') }}'
            );
        });
    };
    
    // Passkey管理
    let currentCredentialId = null;
    
    window.openDeletePasskeyModal = function(credentialId, credentialName) {
        currentCredentialId = credentialId;
        const modal = document.getElementById('deletePasskeyModal');
        const messageElement = modal.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `{{ __('admin/profile.confirm_delete_passkey_message') }}\n\n${credentialName}`;
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
                console.log('[Passkey Delete] Success');
                window.PasskeyResultModal.showSuccess(
                    'passkeyResultModal',
                    '{{ __('admin/profile.passkey_delete_success_title') }}',
                    data.message,
                    () => location.reload()
                );
            } else {
                console.log('[Passkey Delete] Failed:', data.message);
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    data.message
                );
            }
        })
        .catch(error => {
            console.error('[Passkey Delete] Error:', error);
            console.error('[Passkey Delete] Error stack:', error.stack);
            closeModal('deletePasskeyModal');
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                '{{ __('admin/profile.passkey_delete_error') }}'
            );
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
                window.PasskeyResultModal.showSuccess(
                    'passkeyResultModal',
                    '{{ __('admin/profile.passkey_delete_success_title') }}',
                    data.message,
                    () => location.reload()
                );
            } else {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    data.message
                );
            }
        })
        .catch(error => {
            console.error('Error:', error);
            closeModal('deleteAllPasskeysModal');
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                '{{ __('admin/profile.passkey_delete_all_error') }}'
            );
        });
    };
    
    // User Agentからデバイス名を生成
    function getDeviceNameFromUserAgent() {
        const ua = navigator.userAgent;
        let deviceName = '';
        
        // OS検出
        if (ua.includes('Mac OS X')) {
            if (ua.includes('iPhone')) {
                deviceName = 'iPhone';
            } else if (ua.includes('iPad')) {
                deviceName = 'iPad';
            } else {
                deviceName = 'Mac';
            }
        } else if (ua.includes('Windows')) {
            deviceName = 'Windows PC';
        } else if (ua.includes('Android')) {
            deviceName = 'Android';
        } else if (ua.includes('Linux')) {
            deviceName = 'Linux PC';
        } else {
            deviceName = 'Device';
        }
        
        // ブラウザ検出
        let browser = '';
        if (ua.includes('Edg/')) {
            browser = 'Edge';
        } else if (ua.includes('Chrome/') && !ua.includes('Edg/')) {
            browser = 'Chrome';
        } else if (ua.includes('Safari/') && !ua.includes('Chrome/')) {
            browser = 'Safari';
        } else if (ua.includes('Firefox/')) {
            browser = 'Firefox';
        }
        
        // デバイス名とブラウザを組み合わせ
        if (browser) {
            return `${deviceName} (${browser})`;
        }
        return deviceName;
    }
    
    // Passkey登録機能
    window.registerPasskey = async function() {
        try {
            // WebAuthn対応チェック
            if (!window.PublicKeyCredential) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    '{{ __('admin/profile.passkey_not_supported') }}'
                );
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

            // デバイス名を入力（モーダルで）
            const defaultDeviceName = getDeviceNameFromUserAgent();
            const deviceName = await new Promise((resolve) => {
                window.PasskeyDeviceNameModal.open('passkeyDeviceNameModal', resolve, defaultDeviceName);
            });
            
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
                window.PasskeyResultModal.showSuccess(
                    'passkeyResultModal',
                    '{{ __('admin/profile.passkey_register_success_title') }}',
                    result.message,
                    () => location.reload()
                );
            } else {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    '{{ __('common.error') }}',
                    result.message || '{{ __('admin/profile.passkey_register_error') }}'
                );
            }

        } catch (error) {
            console.error('[Passkey] 登録エラー:', error);
            
            let errorMessage;
            if (error.name === 'NotAllowedError') {
                errorMessage = '{{ __('admin/profile.passkey_cancelled') }}';
            } else if (error.name === 'InvalidStateError') {
                errorMessage = '{{ __('admin/profile.passkey_already_registered') }}';
            } else {
                errorMessage = '{{ __('admin/profile.passkey_register_error') }}\n\n' + error.message;
            }
            
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                errorMessage
            );
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
                        alert('{{ __('admin/profile.webauthn_not_supported') }}');
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
                    const deviceName = prompt('{{ __('admin/profile.enter_device_name') }}', '');

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
                        alert('{{ __('admin/profile.biometric_cancelled') }}');
                    } else {
                        alert('{{ __('admin/profile.biometric_registration_error') }}');
                    }
                }
            });
        }
    });


    // 回復コード生成/再生成確認
    async function confirmGenerateRecoveryCodes() {
        console.log('[DEBUG] confirmGenerateRecoveryCodes called');
        closeModal('recoveryCodesConfirmModal');
        
        try {
            console.log('[DEBUG] Fetching recovery codes...');
            const response = await fetch('{{ route("admin.profile.recovery-codes.generate") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            console.log('[DEBUG] Recovery codes response:', data);
            
            if (data.success) {
                // 成功：回復コードを表示
                console.log('[DEBUG] Success - displaying codes');
                displayRecoveryCodesInModal('manualRecoveryCodesModal', data.codes);
                openModal('manualRecoveryCodesModal');
            } else {
                // エラー：エラーメッセージを表示
                console.log('[DEBUG] Error - displaying error message');
                displayRecoveryCodesError('manualRecoveryCodesModal', data.message || '{{ __("common.error") }}');
                openModal('manualRecoveryCodesModal');
            }
        } catch (error) {
            console.error('[DEBUG] Recovery codes error:', error);
            // エラー：エラーメッセージを表示
            displayRecoveryCodesError('manualRecoveryCodesModal', '{{ __("common.error") }}');
            openModal('manualRecoveryCodesModal');
        }
    }
</script>
@endpush

<!-- 回復コード表示モーダル（自動生成用） -->
@if(session('auto_generated_recovery_codes'))
@include('two-factor.partials.recovery-codes-modal', [
    'modalId' => 'profileAutoGeneratedRecoveryCodesModal',
    'title' => __('two-factor.recovery_codes.title'),
    'codes' => session('auto_generated_recovery_codes'),
    'isAutoGenerated' => true,
    'autoOpen' => true,
    'clearSessionRoute' => route('admin.profile.recovery-codes.clear-session')
])
@endif

<!-- 回復コード表示モーダル（手動生成用・エラー表示兼用） -->
@include('two-factor.partials.recovery-codes-modal', [
    'modalId' => 'manualRecoveryCodesModal',
    'title' => __('two-factor.recovery_codes.title'),
    'codes' => [],
    'isDynamic' => true
])


<!-- 回復コード生成/再生成確認モーダル -->
<x-modal 
    id="recoveryCodesConfirmModal" 
    :title="__('two-factor.recovery_codes.generate')"
    :message="__('admin/profile.recovery_codes_generate_confirm')"
    confirm_label="{{ __('common.ok') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="warning"
    confirm_color="blue"
/>
