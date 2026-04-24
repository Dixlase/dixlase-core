/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * WebAuthn Utility Functions
 * Base64URL encoding/decoding and device name generation for WebAuthn/Passkey
 */

/**
 * Base64URL文字列をArrayBufferに変換
 */
export function base64urlToBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(base64);
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}

/**
 * ArrayBufferをBase64URL文字列に変換
 */
export function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

/**
 * User Agentからデバイス名を生成
 */
export function getDeviceNameFromUserAgent() {
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

// Backward compatibility: グローバル関数として公開
window.base64urlToBuffer = base64urlToBuffer;
window.bufferToBase64url = bufferToBase64url;
window.getDeviceNameFromUserAgent = getDeviceNameFromUserAgent;
