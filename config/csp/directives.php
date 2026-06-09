<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

return [
    // Default fallback
    'default-src' => ["'self'"],

    // Scripts
    // 'nonce' is automatically replaced with a per-request nonce value
    // 'strict-dynamic' allows scripts loaded from nonce'd scripts
    // 'unsafe-eval' is added because Alpine.js requires it
    // CAPTCHA origins are NOT listed here. They are injected dynamically by
    // CaptchaCspProvider only when captcha is enabled and based on the active
    // driver, so sites that don't use captcha don't carry those allowances.
    // Vite dev server is automatically added by CspBuilder in local environment only
    'script-src' => ["'self'", "'nonce'", "'strict-dynamic'", "'unsafe-eval'"],

    // Script attributes (event handler attributes like onclick)
    // Base value is 'none' (blocked). In dev mode, CspBuilder overwrites to 'unsafe-inline'
    // Alpine.js @click etc. are controlled by script-src, not script-src-attr
    'script-src-attr' => ["'none'"],

    // Styles
    // Since 'unsafe-inline' is ignored when used with nonce, to allow inline styles (element.style)
    // you must either not use nonce or use only unsafe-inline
    // Using unsafe-inline to allow style manipulation in Alpine.js and JavaScript
    // Bunny Fonts、Font Awesome CDN
    'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', 'https://cdnjs.cloudflare.com', 'https://use.fontawesome.com'],

    // Images
    // raw.githubusercontent.com: thumbnails on online extension add screen (GitHub Source Provider)
    'img-src' => ["'self'", 'data:', 'blob:', 'https://raw.githubusercontent.com'],

    // Fonts
    // Bunny Fonts、Font Awesome CDN
    // Local fonts (Vite build assets)
    'font-src' => ["'self'", 'data:', 'blob:', 'https://fonts.bunny.net', 'https://cdnjs.cloudflare.com', 'https://use.fontawesome.com'],

    // Connection targets (XHR, fetch, WebSocket, etc.)
    // CAPTCHA verification origins are injected dynamically by
    // CaptchaCspProvider only when captcha is enabled.
    'connect-src' => ["'self'"],

    // Media (audio, video)
    'media-src' => ["'self'"],

    // Objects (plugin, embed, object)
    'object-src' => ["'none'"],

    // Frames
    // Default to 'none'. Front pages do not embed iframes by default.
    // Admin context relaxes this to 'self' via config/csp/admin.php so that
    // same-origin preview iframes work. CAPTCHA iframe origins are injected
    // dynamically by CaptchaCspProvider only when captcha is enabled.
    // Plugins/themes that need to embed external frames declare them via
    // csp.frames in plugin.json / theme.json.
    'frame-src' => ["'none'"],

    // Frame ancestors (parents that can embed this page)
    // Note: Overridden to 'none' in admin panel (clickjacking protection)
    'frame-ancestors' => ["'self'"],

    // Form submission destinations
    'form-action' => ["'self'"],

    // Base URI
    'base-uri' => ["'self'"],

    // Manifest
    'manifest-src' => ["'self'"],

    // Workers
    'worker-src' => ["'self'", 'blob:'],
];
