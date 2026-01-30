/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * ログインフローの管理
 * 2段階ログイン（識別子入力 → 認証方法選択）を実装
 */
function createLoginFlow() {
    return {
        // data属性から値を取得
        init() {
            const el = this.$el;
            this.routes = {
                checkIdentifier: el.dataset.routeCheckIdentifier,
                passkeyChallenge: el.dataset.routePasskeyChallenge,
                passkeyVerify: el.dataset.routePasskeyVerify
            };
            this.translations = {
                errorOccurred: el.dataset.transErrorOccurred,
                authFailed: el.dataset.transAuthFailed,
                passkeyCancelled: el.dataset.transPasskeyCancelled
            };
        },
        step: 1,
        identifier: '',
        hasPasskey: false,
        loading: false,
        errors: {},

        async checkIdentifier() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch(this.routes.checkIdentifier, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login: this.identifier
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.hasPasskey = data.has_passkey;
                    this.step = 2;
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else if (data.message) {
                        this.errors.login = data.message;
                    }
                }
            } catch (error) {
                console.error('Identifier check error:', error);
                this.errors.login = this.translations.errorOccurred;
            } finally {
                this.loading = false;
            }
        },

        async loginWithPasskey() {
            this.loading = true;
            this.errors = {};

            try {
                // チャレンジを取得
                const challengeResponse = await fetch(this.routes.passkeyChallenge, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login: this.identifier
                    })
                });

                const challengeData = await challengeResponse.json();

                if (!challengeResponse.ok) {
                    this.errors.login = challengeData.error || this.translations.errorOccurred;
                    this.loading = false;
                    return;
                }

                // WebAuthn認証を実行
                const credential = await navigator.credentials.get({
                    publicKey: challengeData.publicKey
                });

                if (!credential) {
                    this.errors.login = this.translations.authFailed;
                    this.loading = false;
                    return;
                }

                // 認証情報を送信
                const verifyResponse = await fetch(this.routes.passkeyVerify, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id: credential.id,
                        rawId: this.arrayBufferToBase64(credential.rawId),
                        type: credential.type,
                        response: {
                            authenticatorData: this.arrayBufferToBase64(credential.response.authenticatorData),
                            clientDataJSON: this.arrayBufferToBase64(credential.response.clientDataJSON),
                            signature: this.arrayBufferToBase64(credential.response.signature),
                            userHandle: credential.response.userHandle ? this.arrayBufferToBase64(credential.response.userHandle) : null
                        }
                    })
                });

                const verifyData = await verifyResponse.json();

                if (verifyResponse.ok && verifyData.success) {
                    // ログイン成功 - リダイレクト
                    window.location.href = verifyData.redirect;
                } else {
                    this.errors.login = verifyData.error || this.translations.authFailed;
                    this.loading = false;
                }
            } catch (error) {
                console.error('Passkey login error:', error);

                // ユーザーがキャンセルした場合
                if (error.name === 'NotAllowedError') {
                    this.errors.login = this.translations.passkeyCancelled;
                } else {
                    this.errors.login = this.translations.errorOccurred;
                }

                this.loading = false;
            }
        },

        arrayBufferToBase64(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary);
        },

        resetFlow() {
            this.step = 1;
            this.identifier = '';
            this.hasPasskey = false;
            this.errors = {};
        }
    }
}

// Alpine.jsのグローバルスコープに登録
window.loginFlow = createLoginFlow;
