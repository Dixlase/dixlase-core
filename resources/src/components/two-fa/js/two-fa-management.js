/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Two-Factor Authentication Management Component
 * Handles Passkey registration/deletion, trusted device management, and recovery codes
 */

import { base64urlToBuffer, bufferToBase64url, getDeviceNameFromUserAgent } from './webauthn-utils';

// Alpine.js component for 2FA management
window.twoFaManagement = function (routes, csrfToken, translations) {
    return {
        routes: routes,
        csrfToken: csrfToken,
        translations: translations,
        currentCredentialId: null,
        currentDeviceId: null,

        init() {
            // DOMContentLoadedイベントリスナーを設定
            this.setupEventListeners();
        },

        setupEventListeners() {
            // ページリロード時の処理
            const manualRecoveryCodesCloseBtn = document.getElementById('manualRecoveryCodesModal-close-btn');
            if (manualRecoveryCodesCloseBtn) {
                manualRecoveryCodesCloseBtn.addEventListener('click', () => {
                    const checkbox = document.getElementById('manualRecoveryCodesModal-saved-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        location.reload();
                    }
                });
            }
        },

        // Passkey登録
        async registerPasskey() {
            if (!window.PublicKeyCredential) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        this.translations.passkey_not_supported
                    );
                } else {
                    alert(this.translations.passkey_not_supported);
                }
                return;
            }

            try {
                // チャレンジ取得
                const challengeResponse = await fetch(this.routes.passkey_register_options, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const challengeData = await challengeResponse.json();

                if (!challengeData.success) {
                    throw new Error(challengeData.message || this.translations.passkey_register_error);
                }

                const options = challengeData.options;

                // Base64URLデコード
                options.challenge = base64urlToBuffer(options.challenge);
                options.user.id = base64urlToBuffer(options.user.id);

                // Passkey作成
                const credential = await navigator.credentials.create({
                    publicKey: options
                });

                // デバイス名入力モーダルを表示
                const defaultDeviceName = getDeviceNameFromUserAgent();

                if (window.PasskeyDeviceNameModal) {
                    const deviceName = await window.PasskeyDeviceNameModal.prompt(
                        'passkeyDeviceNameModal',
                        this.translations.passkey_device_name_title || 'デバイス名を入力',
                        defaultDeviceName
                    );

                    if (!deviceName) {
                        return;
                    }

                    // Passkey登録
                    await this.completePasskeyRegistration(credential, deviceName);
                }

            } catch (error) {
                // ユーザーがキャンセルまたはタイムアウトした場合は静かに終了
                if (error.name === 'NotAllowedError') {
                    return;
                }

                // その他のエラーはログに記録して表示
                console.error('[Passkey] 登録エラー:', error);

                let errorMessage;
                if (error.name === 'InvalidStateError') {
                    errorMessage = this.translations.passkey_already_registered;
                } else {
                    errorMessage = this.translations.passkey_register_error + '\n\n' + error.message;
                }

                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        errorMessage
                    );
                } else {
                    alert(errorMessage);
                }
            }
        },

        async completePasskeyRegistration(credential, deviceName) {
            const requestData = {
                credential: {
                    id: credential.id,
                    rawId: bufferToBase64url(credential.rawId),
                    type: credential.type,
                    response: {
                        clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64url(credential.response.attestationObject)
                    }
                },
                device_name: deviceName
            };

            const response = await fetch(this.routes.passkey_register, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.success) {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showSuccess(
                        'passkeyResultModal',
                        this.translations.passkey_register_success,
                        result.message || this.translations.passkey_register_success,
                        () => location.reload()
                    );
                } else {
                    alert(result.message || this.translations.passkey_register_success);
                    location.reload();
                }
            } else {
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        result.message || this.translations.passkey_register_error
                    );
                } else {
                    alert(result.message || this.translations.passkey_register_error);
                }
            }
        },

        // Passkey削除モーダルを開く
        openDeletePasskeyModal(credentialId, credentialName) {
            this.currentCredentialId = credentialId;
            if (typeof openModal === 'function') {
                openModal('deletePasskeyModal');
            }
        },

        // Passkey削除
        async deletePasskey() {
            if (!this.currentCredentialId) return;

            if (typeof closeModal === 'function') {
                closeModal('deletePasskeyModal');
            }

            try {
                const response = await fetch(this.routes.passkey_delete.replace(':id', this.currentCredentialId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showSuccess(
                            'passkeyResultModal',
                            this.translations.passkey_delete_success,
                            data.message || this.translations.passkey_delete_success,
                            () => location.reload()
                        );
                    } else {
                        alert(data.message || this.translations.passkey_delete_success);
                        location.reload();
                    }
                } else {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showError(
                            'passkeyResultModal',
                            this.translations.error,
                            data.message || this.translations.passkey_delete_error
                        );
                    } else {
                        alert(data.message || this.translations.passkey_delete_error);
                    }
                }
            } catch (error) {
                console.error('[Passkey] 削除エラー:', error);
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        this.translations.passkey_delete_error
                    );
                } else {
                    alert(this.translations.passkey_delete_error);
                }
            }

            this.currentCredentialId = null;
        },

        // 全Passkey削除
        async deleteAllPasskeys() {
            if (typeof closeModal === 'function') {
                closeModal('deleteAllPasskeysModal');
            }

            try {
                const response = await fetch(this.routes.passkey_delete_all, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showSuccess(
                            'passkeyResultModal',
                            this.translations.passkey_delete_success,
                            data.message || this.translations.passkey_delete_success,
                            () => location.reload()
                        );
                    } else {
                        alert(data.message || this.translations.passkey_delete_success);
                        location.reload();
                    }
                } else {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showError(
                            'passkeyResultModal',
                            this.translations.error,
                            data.message || this.translations.passkey_delete_all_error
                        );
                    } else {
                        alert(data.message || this.translations.passkey_delete_all_error);
                    }
                }
            } catch (error) {
                console.error('[Passkey] 全削除エラー:', error);
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        this.translations.passkey_delete_all_error
                    );
                } else {
                    alert(this.translations.passkey_delete_all_error);
                }
            }
        },

        // 信頼済みデバイス削除モーダルを開く
        openDeleteTrustedDeviceModal(deviceId) {
            this.currentDeviceId = deviceId;
            if (typeof openModal === 'function') {
                openModal('deleteTrustedDeviceModal');
            }
        },

        // 信頼済みデバイス削除
        async deleteTrustedDevice() {
            if (!this.currentDeviceId) return;

            if (typeof closeModal === 'function') {
                closeModal('deleteTrustedDeviceModal');
            }

            try {
                const response = await fetch(this.routes.trusted_device_delete.replace(':id', this.currentDeviceId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'デバイスの削除に失敗しました');
                }
            } catch (error) {
                console.error('[Trusted Device] 削除エラー:', error);
                alert('デバイスの削除に失敗しました');
            }

            this.currentDeviceId = null;
        },

        // 全信頼済みデバイス削除
        async deleteAllTrustedDevices() {
            if (typeof closeModal === 'function') {
                closeModal('deleteAllTrustedDevicesModal');
            }

            try {
                const response = await fetch(this.routes.trusted_device_delete_all, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'デバイスの削除に失敗しました');
                }
            } catch (error) {
                console.error('[Trusted Device] 全削除エラー:', error);
                alert('デバイスの削除に失敗しました');
            }
        },

        // 回復コード生成/再生成確認
        async confirmGenerateRecoveryCodes() {
            if (typeof closeModal === 'function') {
                closeModal('recoveryCodesConfirmModal');
            }

            try {
                const response = await fetch(this.routes.recovery_codes_generate, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (typeof displayRecoveryCodesInModal === 'function') {
                        displayRecoveryCodesInModal('manualRecoveryCodesModal', data.codes);
                    }
                    if (typeof openModal === 'function') {
                        openModal('manualRecoveryCodesModal');
                    }
                } else {
                    // エラーメッセージを表示
                    if (typeof displayRecoveryCodesError === 'function') {
                        displayRecoveryCodesError('manualRecoveryCodesModal', data.message || this.translations.error);
                    }
                    if (typeof openModal === 'function') {
                        openModal('manualRecoveryCodesModal');
                    }
                }
            } catch (error) {
                console.error('[Recovery Codes] Error:', error);
                if (typeof displayRecoveryCodesError === 'function') {
                    displayRecoveryCodesError('manualRecoveryCodesModal', this.translations.recovery_codes_error);
                }
                if (typeof openModal === 'function') {
                    openModal('manualRecoveryCodesModal');
                }
            }
        },

        // 回復コード削除（管理画面用）
        async deleteRecoveryCodes() {
            if (!this.routes.recovery_codes_delete) {
                console.error('[Recovery Codes Delete] No delete route configured');
                return;
            }

            if (typeof closeModal === 'function') {
                closeModal('deleteRecoveryCodesModal');
            }

            try {
                const response = await fetch(this.routes.recovery_codes_delete, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showSuccess(
                            'passkeyResultModal',
                            '削除完了',
                            data.message || '回復コードを削除しました',
                            () => location.reload()
                        );
                    } else {
                        alert(data.message || '回復コードを削除しました');
                        location.reload();
                    }
                } else {
                    if (window.PasskeyResultModal) {
                        window.PasskeyResultModal.showError(
                            'passkeyResultModal',
                            this.translations.error,
                            data.message || '回復コードの削除に失敗しました'
                        );
                    } else {
                        alert(data.message || '回復コードの削除に失敗しました');
                    }
                }
            } catch (error) {
                console.error('[Recovery Codes] 削除エラー:', error);
                if (window.PasskeyResultModal) {
                    window.PasskeyResultModal.showError(
                        'passkeyResultModal',
                        this.translations.error,
                        '回復コードの削除に失敗しました'
                    );
                } else {
                    alert('回復コードの削除に失敗しました');
                }
            }
        }
    };
};

// Backward compatibility: グローバル関数
window.registerPasskey = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.registerPasskey();
    }
};

window.openDeletePasskeyModal = function (credentialId, credentialName) {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.openDeletePasskeyModal(credentialId, credentialName);
    }
};

window.deletePasskey = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.deletePasskey();
    }
};

window.deleteAllPasskeys = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.deleteAllPasskeys();
    }
};

window.openDeleteTrustedDeviceModal = function (deviceId) {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.openDeleteTrustedDeviceModal(deviceId);
    }
};

window.deleteTrustedDevice = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.deleteTrustedDevice();
    }
};

window.deleteAllTrustedDevices = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.deleteAllTrustedDevices();
    }
};

window.confirmGenerateRecoveryCodes = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.confirmGenerateRecoveryCodes();
    }
};

window.deleteRecoveryCodes = function () {
    const component = window.twoFaManagementInstance;
    if (component) {
        component.deleteRecoveryCodes();
    }
};
