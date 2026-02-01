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
export function createLoginFlow() {
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

            // old入力値があればステップ2を表示（パスワード間違い時）
            const oldLogin = document.querySelector('input[name="login"]')?.value;
            if (oldLogin) {
                this.identifier = oldLogin;
                this.step = 2;
                // パスキー情報は再チェックが必要だが、エラー表示を優先するためスキップ
                this.hasPasskey = false;
            }

            // sessionStorageからロックアウトメッセージを読み込み
            const loginError = sessionStorage.getItem('login_error');
            if (loginError) {
                this.errors.login = loginError;
                sessionStorage.removeItem('login_error');
            }
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
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(this.routes.checkIdentifier, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
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
                    // ロックアウトエラーの場合はページをリロードしてフラッシュメッセージを表示
                    if (data.errors && data.errors.login) {
                        // エラーメッセージを取得（配列の場合は最初の要素）
                        const errorMessage = Array.isArray(data.errors.login)
                            ? data.errors.login[0]
                            : data.errors.login;

                        // ロックアウトメッセージかチェック
                        if (errorMessage.includes('上限に達しました') || errorMessage.includes('lockout') || errorMessage.includes('locked out')) {
                            // セッションにエラーメッセージを保存してリロード
                            sessionStorage.setItem('login_error', errorMessage);
                            window.location.reload();
                            return;
                        }
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
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                // チャレンジを取得
                const challengeResponse = await fetch(this.routes.passkeyChallenge, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login: this.identifier
                    })
                });

                const challengeData = await challengeResponse.json();

                if (!challengeResponse.ok || !challengeData.success) {
                    this.errors.login = challengeData.error || challengeData.message || this.translations.errorOccurred;
                    this.loading = false;
                    return;
                }

                // チャレンジをデコード
                const publicKeyOptions = this.decodePublicKeyOptions(challengeData.challenge);

                // WebAuthn認証を実行
                const credential = await navigator.credentials.get({
                    publicKey: publicKeyOptions
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
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id: credential.id,
                        rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                        type: credential.type,
                        response: {
                            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                            signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))),
                            userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))) : null
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
        },

        // Base64URLデコードヘルパー
        base64urlDecode(str) {
            // Base64URL to Base64
            str = str.replace(/-/g, '+').replace(/_/g, '/');
            // パディング追加
            const pad = str.length % 4;
            if (pad) {
                if (pad === 1) {
                    throw new Error('Invalid base64url string');
                }
                str += new Array(5 - pad).join('=');
            }
            // Base64デコード
            const binary = atob(str);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes;
        },

        // PublicKeyCredentialRequestOptionsをデコード
        decodePublicKeyOptions(options) {
            return {
                challenge: this.base64urlDecode(options.challenge),
                timeout: options.timeout,
                rpId: options.rpId,
                allowCredentials: options.allowCredentials.map(cred => ({
                    id: this.base64urlDecode(cred.id),
                    type: cred.type,
                    transports: cred.transports
                })),
                userVerification: options.userVerification
            };
        }
    }
}
