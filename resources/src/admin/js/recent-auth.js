/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Recent re-authentication (#675).
 *
 * 1. `recentAuth` is the Alpine data of the confirmation screen: it runs the
 *    passkey ceremony against the confirmation endpoints.
 * 2. A sensitive admin route answers a script request that has no recent
 *    confirmation with 423 and the `X-Dixlase-Recent-Auth` header, which
 *    carries the confirmation URL. fetch and axios are wrapped here so that
 *    every admin script follows it, without each call site handling it.
 */

import Alpine from 'alpinejs';
import { base64urlToBuffer, bufferToBase64url } from '../../components/two-fa/js/webauthn-utils';

const HEADER = 'X-Dixlase-Recent-Auth';

function confirmationUrlOf(status, headers) {
    if (status !== 423 || !headers) {
        return null;
    }

    const url = typeof headers.get === 'function' ? headers.get(HEADER) : headers[HEADER.toLowerCase()];

    return url || null;
}

function goToConfirmation(url) {
    window.location.assign(url);
}

if (typeof window !== 'undefined' && typeof window.fetch === 'function' && !window.__dixlaseRecentAuthFetchPatched) {
    const originalFetch = window.fetch.bind(window);

    window.fetch = async function (...args) {
        const response = await originalFetch(...args);
        const url = response ? confirmationUrlOf(response.status, response.headers) : null;

        if (url) {
            goToConfirmation(url);
        }

        return response;
    };

    window.__dixlaseRecentAuthFetchPatched = true;
}

// axios is set up by the common bundle, which may load after this one, so
// its interceptor is attached once the page has run every module.
function patchAxios() {
    if (!window.axios || window.__dixlaseRecentAuthAxiosPatched) {
        return;
    }

    window.axios.interceptors.response.use(
        (response) => response,
        (error) => {
            const url = error && error.response
                ? confirmationUrlOf(error.response.status, error.response.headers)
                : null;

            if (url) {
                goToConfirmation(url);
            }

            return Promise.reject(error);
        }
    );

    window.__dixlaseRecentAuthAxiosPatched = true;
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', patchAxios);
}

function decodeRequestOptions(options) {
    const publicKey = { ...options, challenge: base64urlToBuffer(options.challenge) };

    if (Array.isArray(options.allowCredentials)) {
        publicKey.allowCredentials = options.allowCredentials.map(function (credential) {
            return { ...credential, id: base64urlToBuffer(credential.id) };
        });
    }

    return publicKey;
}

function serializeAssertion(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            authenticatorData: bufferToBase64url(credential.response.authenticatorData),
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            signature: bufferToBase64url(credential.response.signature),
            userHandle: credential.response.userHandle ? bufferToBase64url(credential.response.userHandle) : null,
        },
    };
}

function createRecentAuth() {
    return {
        loading: false,
        error: '',

        init() {
            const data = this.$el.dataset;
            this.routes = { options: data.routePasskeyOptions, verify: data.routePasskeyVerify };
            this.translations = {
                failed: data.transPasskeyFailed,
                cancelled: data.transPasskeyCancelled,
                error: data.transError,
            };
        },

        async post(url, body) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(function () {
                return {};
            });

            return { ok: response.ok, data: data };
        },

        async usePasskey() {
            if (!window.PublicKeyCredential || !navigator.credentials) {
                this.error = this.translations.error;

                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const options = await this.post(this.routes.options, {});

                if (!options.ok || !options.data.success) {
                    this.error = options.data.error || this.translations.error;

                    return;
                }

                const credential = await navigator.credentials.get({
                    publicKey: decodeRequestOptions(options.data.challenge),
                });

                if (!credential) {
                    this.error = this.translations.failed;

                    return;
                }

                const result = await this.post(this.routes.verify, serializeAssertion(credential));

                if (result.data.redirect) {
                    window.location.assign(result.data.redirect);

                    return;
                }

                this.error = result.data.error || this.translations.failed;
            } catch (error) {
                this.error = error && error.name === 'NotAllowedError'
                    ? this.translations.cancelled
                    : this.translations.error;
            } finally {
                this.loading = false;
            }
        },
    };
}

Alpine.data('recentAuth', createRecentAuth);
