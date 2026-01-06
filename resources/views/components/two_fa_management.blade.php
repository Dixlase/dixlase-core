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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'passkeyEnabled' => false,
    'passkeyDevices' => null,
    'hasRecoveryCodes' => false,
    'recoveryCodesCount' => 0,
    'trustedDevices' => null,
    'showTrustedDevices' => false,
    'hideAddButtons' => false,
    'hideGenerateButton' => false,
    'adminContext' => false,
    'routes' => [
        'passkey_register_options' => '',
        'passkey_register' => '',
        'passkey_delete' => '',
        'passkey_delete_all' => '',
        'recovery_codes_generate' => '',
        'recovery_codes_delete' => '',
        'trusted_device_delete' => '',
        'trusted_device_delete_all' => '',
    ],
    'csrfToken' => '',
])

<section class="mt-8 transition-colors-unified">
    <h2>{{ __('components.two_fa_management.title') }}</h2>

    <!-- Passkeyデバイス -->
    @if($passkeyEnabled)
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components.two_fa_management.passkey_devices') }}</h3>
            @if($passkeyDevices && !$passkeyDevices->isEmpty())
                <button 
                    type="button"
                    onclick="openModal('deleteAllPasskeysModal')"
                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                    <i class="fas fa-trash-alt mr-1"></i>{{ __('components.two_fa_management.delete_all') }}
                </button>
            @endif
        </div>
        
        @if(!$passkeyDevices || $passkeyDevices->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components.two_fa_management.no_passkey_devices') }}</p>
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
                                <p><strong>{{ __('components.two_fa_management.registered_at') }}:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                @if($device->last_used_at)
                                    <p><strong>{{ __('components.two_fa_management.last_used') }}:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        <button 
                            type="button"
                            onclick="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                            class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            {{ __('components.two_fa_management.delete') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        
        @if(!$hideAddButtons)
        <!-- 新しいPasskeyを追加 -->
        <button 
            type="button"
            onclick="registerPasskey()"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
            <i class="fas fa-plus mr-2"></i>{{ __('components.two_fa_management.add_passkey') }}
        </button>
        @endif

        <!-- Passkeyの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components.two_fa_management.passkey_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components.two_fa_management.passkey_info_1') }}</li>
                <li>{{ __('components.two_fa_management.passkey_info_2') }}</li>
                <li>{{ __('components.two_fa_management.passkey_info_3') }}</li>
            </ul>
        </div>
    </div>
    @endif

    <!-- 回復コード -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components.two_fa_management.recovery_codes_title') }}</h3>
        </div>

        @if($hasRecoveryCodes)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        {{ __('components.two_fa_management.recovery_codes_remaining', ['count' => $recoveryCodesCount]) }}
                    </p>
                    @if($adminContext && isset($routes['recovery_codes_delete']))
                    <button 
                        type="button"
                        onclick="openModal('deleteRecoveryCodesModal')"
                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                        <i class="fas fa-trash mr-1"></i>{{ __('components.two_fa_management.delete') }}
                    </button>
                    @endif
                </div>
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components.two_fa_management.recovery_codes_not_generated') }}</p>
        @endif
        
        @if(!$hideGenerateButton)
        <button 
            type="button"
            onclick="openModal('recoveryCodesConfirmModal')"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-{{ $hasRecoveryCodes ? 'sync-alt' : 'plus' }} mr-2"></i>{{ __('components.two_fa_management.recovery_codes_' . ($hasRecoveryCodes ? 'regenerate' : 'generate')) }}
        </button>
        @endif
        
        <!-- 回復コードの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components.two_fa_management.recovery_codes_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components.two_fa_management.recovery_codes_info_1') }}</li>
                <li>{{ __('components.two_fa_management.recovery_codes_info_2') }}</li>
                <li>{{ __('components.two_fa_management.recovery_codes_info_3') }}</li>
                <li>{{ __('components.two_fa_management.recovery_codes_info_4') }}</li>
                <li>{{ __('components.two_fa_management.recovery_codes_info_5') }}</li>
                <li>{{ __('components.two_fa_management.recovery_codes_info_6') }}</li>
            </ul>
        </div>            
    </div>

    <!-- 信頼済みデバイス管理 -->
    @if($showTrustedDevices)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components.two_fa_management.trusted_devices_title') }}</h3>
            @if($trustedDevices && !$trustedDevices->isEmpty())
                <button 
                    type="button"
                    onclick="openModal('deleteAllTrustedDevicesModal')"
                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                    <i class="fas fa-trash-alt mr-1"></i>{{ __('components.two_fa_management.delete_all') }}
                </button>
            @endif
        </div>
        
        @if(!$trustedDevices || $trustedDevices->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components.two_fa_management.no_trusted_devices') }}</p>
        @else
            <div class="space-y-4 mb-4">
                @foreach($trustedDevices as $device)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-mobile-alt text-blue-600 dark:text-blue-400 mr-2"></i>
                                <h4 class="font-semibold">{{ $device->device_name ?? __('components.two_fa_management.unknown_device') }}</h4>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>{{ __('components.two_fa_management.ip_address') }}:</strong> {{ $device->ip_address }}</p>
                                <p><strong>{{ __('components.two_fa_management.registered_at') }}:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                @if($device->last_used_at)
                                    <p><strong>{{ __('components.two_fa_management.last_used') }}:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        <button 
                            type="button"
                            onclick="openDeleteTrustedDeviceModal('{{ $device->id }}', '{{ $device->device_name ?? __('components.two_fa_management.unknown_device') }}')"
                            class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            {{ __('components.two_fa_management.delete') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 信頼済みデバイスの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components.two_fa_management.trusted_devices_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components.two_fa_management.trusted_devices_info_1') }}</li>
                <li>{{ __('components.two_fa_management.trusted_devices_info_2') }}</li>
                <li>{{ __('components.two_fa_management.trusted_devices_info_3') }}</li>
            </ul>
        </div>
    </div>
    @endif
</section>

@once
@push('scripts')
<script @cspNonce>
(function() {
    'use strict';
    
    // コンポーネントの設定
    const config = {
        routes: @json($routes),
        csrfToken: '{{ $csrfToken }}',
        translations: {
            error: '{{ __('common.error') }}',
            passkey_not_supported: '{{ __('components.two_fa_management.passkey_not_supported') }}',
            passkey_register_success: '{{ __('components.two_fa_management.passkey_register_success') }}',
            passkey_register_error: '{{ __('components.two_fa_management.passkey_register_error') }}',
            passkey_cancelled: '{{ __('components.two_fa_management.passkey_cancelled') }}',
            passkey_already_registered: '{{ __('components.two_fa_management.passkey_already_registered') }}',
            passkey_delete_success: '{{ __('components.two_fa_management.passkey_delete_success') }}',
            passkey_delete_error: '{{ __('components.two_fa_management.passkey_delete_error') }}',
            passkey_delete_all_error: '{{ __('components.two_fa_management.passkey_delete_all_error') }}',
            confirm_delete_passkey: '{{ __('components.two_fa_management.confirm_delete_passkey') }}',
            recovery_codes_error: '{{ __('components.two_fa_management.recovery_codes_error') }}',
        }
    };
    
    // Passkey管理用の変数
    let currentCredentialId = null;
    
    // 信頼済みデバイス管理用の変数
    let currentDeviceId = null;
    
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
    
    // Passkey削除モーダルを開く
    window.openDeletePasskeyModal = function(credentialId, credentialName) {
        currentCredentialId = credentialId;
        const modal = document.getElementById('deletePasskeyModal');
        const messageElement = modal?.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `${config.translations.confirm_delete_passkey}\n\n${credentialName}`;
        }
        if (typeof openModal === 'function') {
            openModal('deletePasskeyModal');
        }
    };
    
    // Passkey削除
    window.revokePasskey = function() {
        if (!currentCredentialId) {
            console.error('[Passkey Delete] No credential ID found');
            return;
        }
        
        const url = config.routes.passkey_delete.replace(':id', currentCredentialId);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (typeof closeModal === 'function') {
                closeModal('deletePasskeyModal');
            }
            if (data.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        config.translations.passkey_delete_success,
                        data.message,
                        () => location.reload()
                    );
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        data.message
                    );
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('[Passkey Delete] Error:', error);
            if (typeof closeModal === 'function') {
                closeModal('deletePasskeyModal');
            }
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    config.translations.passkey_delete_error
                );
            } else {
                alert(config.translations.passkey_delete_error);
            }
        });
    };
    
    // 全Passkey削除
    window.revokeAllPasskeys = function() {
        fetch(config.routes.passkey_delete_all, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (typeof closeModal === 'function') {
                closeModal('deleteAllPasskeysModal');
            }
            if (data.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        config.translations.passkey_delete_success,
                        data.message,
                        () => location.reload()
                    );
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        data.message
                    );
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('[Passkey Delete All] Error:', error);
            if (typeof closeModal === 'function') {
                closeModal('deleteAllPasskeysModal');
            }
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    config.translations.passkey_delete_all_error
                );
            } else {
                alert(config.translations.passkey_delete_all_error);
            }
        });
    };
    
    // Passkey登録
    window.registerPasskey = async function() {
        try {
            // WebAuthn対応チェック
            if (!window.PublicKeyCredential) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        config.translations.passkey_not_supported
                    );
                } else {
                    alert(config.translations.passkey_not_supported);
                }
                return;
            }
            
            console.log('[Passkey] 登録開始');
            
            // サーバーから登録チャレンジを取得
            const optionsResponse = await fetch(config.routes.passkey_register_options, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
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
            
            console.log('[Passkey] チャレンジ取得成功');
            
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
            
            console.log('[Passkey] 認証情報作成成功');
            
            // デバイス名を入力（モーダルで）
            const defaultDeviceName = getDeviceNameFromUserAgent();
            let deviceName = defaultDeviceName;
            
            if (window.PasskeyDeviceNameModal) {
                deviceName = await new Promise((resolve) => {
                    window.PasskeyDeviceNameModal.open('passkeyDeviceNameModal', resolve, defaultDeviceName);
                });
                
                if (deviceName === null) {
                    console.log('[Passkey] ユーザーがキャンセルしました');
                    return;
                }
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
            
            console.log('[Passkey] サーバーに送信');
            
            const registerResponse = await fetch(config.routes.passkey_register, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
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
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        config.translations.passkey_register_success,
                        result.message,
                        () => location.reload()
                    );
                } else {
                    alert(result.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        result.message || config.translations.passkey_register_error
                    );
                } else {
                    alert(result.message || config.translations.passkey_register_error);
                }
            }
            
        } catch (error) {
            console.error('[Passkey] 登録エラー:', error);
            
            let errorMessage;
            if (error.name === 'NotAllowedError') {
                errorMessage = config.translations.passkey_cancelled;
            } else if (error.name === 'InvalidStateError') {
                errorMessage = config.translations.passkey_already_registered;
            } else {
                errorMessage = config.translations.passkey_register_error + '\n\n' + error.message;
            }
            
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    errorMessage
                );
            } else {
                alert(errorMessage);
            }
        }
    };
    
    // 回復コード生成/再生成確認
    window.confirmGenerateRecoveryCodes = async function() {
        if (typeof closeModal === 'function') {
            closeModal('recoveryCodesConfirmModal');
        }
        
        try {
            const response = await fetch(config.routes.recovery_codes_generate, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // 成功：回復コードを表示
                if (typeof displayRecoveryCodesInModal === 'function') {
                    displayRecoveryCodesInModal('manualRecoveryCodesModal', data.codes);
                }
                if (typeof openModal === 'function') {
                    openModal('manualRecoveryCodesModal');
                }
            } else {
                // エラー：エラーメッセージを表示
                if (typeof displayRecoveryCodesError === 'function') {
                    displayRecoveryCodesError('manualRecoveryCodesModal', data.message || config.translations.error);
                }
                if (typeof openModal === 'function') {
                    openModal('manualRecoveryCodesModal');
                }
            }
        } catch (error) {
            console.error('[Recovery Codes] Error:', error);
            // エラー：エラーメッセージを表示
            if (typeof displayRecoveryCodesError === 'function') {
                displayRecoveryCodesError('manualRecoveryCodesModal', config.translations.recovery_codes_error);
            }
            if (typeof openModal === 'function') {
                openModal('manualRecoveryCodesModal');
            }
        }
    };
    
    // 回復コード削除（管理画面用）
    window.deleteRecoveryCodes = function() {
        if (!config.routes.recovery_codes_delete) {
            console.error('[Recovery Codes Delete] No delete route configured');
            return;
        }
        
        fetch(config.routes.recovery_codes_delete, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (typeof closeModal === 'function') {
                closeModal('deleteRecoveryCodesModal');
            }
            if (data.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        '{{ __('components.two_fa_management.recovery_codes_delete_success') }}',
                        data.message,
                        () => location.reload()
                    );
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        data.message
                    );
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('[Recovery Codes Delete] Error:', error);
            if (typeof closeModal === 'function') {
                closeModal('deleteRecoveryCodesModal');
            }
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    '{{ __('components.two_fa_management.recovery_codes_delete_error') }}'
                );
            } else {
                alert('{{ __('components.two_fa_management.recovery_codes_delete_error') }}');
            }
        });
    };
    
    // 信頼済みデバイス削除モーダルを開く
    window.openDeleteTrustedDeviceModal = function(deviceId, deviceName) {
        currentDeviceId = deviceId;
        const modal = document.getElementById('deleteTrustedDeviceModal');
        const messageElement = modal?.querySelector('.modal-message p');
        if (messageElement) {
            messageElement.textContent = `{{ __('components.two_fa_management.confirm_delete_trusted_device') }}\n\n${deviceName}`;
        }
        if (typeof openModal === 'function') {
            openModal('deleteTrustedDeviceModal');
        }
    };
    
    // 信頼済みデバイス削除
    window.revokeTrustedDevice = function() {
        if (!currentDeviceId) {
            console.error('[Trusted Device Delete] No device ID found');
            return;
        }
        
        const url = config.routes.trusted_device_delete.replace(':id', currentDeviceId);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (typeof closeModal === 'function') {
                closeModal('deleteTrustedDeviceModal');
            }
            if (data.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        '{{ __('components.two_fa_management.trusted_device_delete_success') }}',
                        data.message,
                        () => location.reload()
                    );
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        data.message
                    );
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('[Trusted Device Delete] Error:', error);
            if (typeof closeModal === 'function') {
                closeModal('deleteTrustedDeviceModal');
            }
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    '{{ __('components.two_fa_management.trusted_device_delete_error') }}'
                );
            } else {
                alert('{{ __('components.two_fa_management.trusted_device_delete_error') }}');
            }
        });
    };
    
    // 全信頼済みデバイス削除
    window.revokeAllTrustedDevices = function() {
        fetch(config.routes.trusted_device_delete_all, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (typeof closeModal === 'function') {
                closeModal('deleteAllTrustedDevicesModal');
            }
            if (data.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        '{{ __('components.two_fa_management.trusted_device_delete_success') }}',
                        data.message,
                        () => location.reload()
                    );
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        config.translations.error,
                        data.message
                    );
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('[Trusted Device Delete All] Error:', error);
            if (typeof closeModal === 'function') {
                closeModal('deleteAllTrustedDevicesModal');
            }
            if (window.PasskeyResultModal) {
                window.PasskeyResultModal.showError(
                    'passkeyResultModal',
                    config.translations.error,
                    '{{ __('components.two_fa_management.trusted_device_delete_all_error') }}'
                );
            } else {
                alert('{{ __('components.two_fa_management.trusted_device_delete_all_error') }}');
            }
        });
    };
    
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
            recoveryCodesConfirmBtn.addEventListener('click', function() {
                confirmGenerateRecoveryCodes();
            });
        }

        // 回復コード削除（管理画面用）
        const deleteRecoveryCodesBtn = document.querySelector('#deleteRecoveryCodesModal .modal-actions button[type="button"]:last-child');
        if (deleteRecoveryCodesBtn) {
            deleteRecoveryCodesBtn.addEventListener('click', deleteRecoveryCodes);
        }

        // 手動生成回復コードモーダルを閉じた時にページリロード
        const manualRecoveryCodesCloseBtn = document.getElementById('manualRecoveryCodesModal-close-btn');
        if (manualRecoveryCodesCloseBtn) {
            manualRecoveryCodesCloseBtn.addEventListener('click', function() {
                // チェックボックスが有効な場合（コードが表示されている場合）のみリロード
                const checkbox = document.getElementById('manualRecoveryCodesModal-saved-checkbox');
                if (checkbox && !checkbox.disabled) {
                    location.reload();
                }
            });
        }
    });
    
})();
</script>
@endpush
@endonce

{{-- モーダル --}}
<x-modal 
    id="deleteTrustedDeviceModal"
    :title="__('components.two_fa_management.confirm_delete_trusted_device_title')"
    :message="__('components.two_fa_management.confirm_delete_trusted_device_message')"
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
/>

<x-modal 
    id="deleteAllTrustedDevicesModal"
    :title="__('components.two_fa_management.confirm_delete_all_trusted_devices_title')"
    :message="__('components.two_fa_management.confirm_delete_all_trusted_devices_message')"
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
/>

<x-modal 
    id="deletePasskeyModal"
    :title="__('components.two_fa_management.confirm_delete_passkey_title')"
    :message="__('components.two_fa_management.confirm_delete_passkey_message')"
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
/>

<x-modal 
    id="deleteAllPasskeysModal"
    :title="__('components.two_fa_management.confirm_delete_all_passkeys_title')"
    :message="__('components.two_fa_management.confirm_delete_all_passkeys_message')"
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
/>

<!-- 回復コード生成/再生成確認モーダル -->
<x-modal 
    id="recoveryCodesConfirmModal" 
    :title="__('components.two_fa_management.recovery_codes_confirm_title')"
    :message="__('components.two_fa_management.recovery_codes_confirm_message')"
    confirm_label="{{ __('common.ok') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="warning"
    confirm_color="blue"
/>

<!-- 回復コード削除確認モーダル（管理画面用） -->
@if($adminContext)
<x-modal 
    id="deleteRecoveryCodesModal"
    :title="__('components.two_fa_management.confirm_delete_recovery_codes_title')"
    :message="__('components.two_fa_management.confirm_delete_recovery_codes_message')"
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
/>
@endif

<!-- 回復コード表示モーダル（手動生成用・エラー表示兼用） -->
@include('two-factor.partials.recovery-codes-modal', [
    'modalId' => 'manualRecoveryCodesModal',
    'title' => __('two-factor.recovery_codes.title'),
    'codes' => [],
    'isDynamic' => true
])

<!-- Passkeyデバイス名入力モーダル -->
@include('two-factor.partials.passkey-device-name-modal', [
    'modalId' => 'passkeyDeviceNameModal'
])

<!-- Passkey登録結果モーダル -->
@include('two-factor.partials.passkey-result-modal', [
    'modalId' => 'passkeyResultModal'
])
