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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    'trusted_domains' => [
        // Google Fonts
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',

        // Bunny Fonts (Dixlase default)
        'https://fonts.bunny.net',

        // Font Awesome
        'https://use.fontawesome.com',

        // Google reCAPTCHA
        'https://www.google.com',
        'https://www.gstatic.com',

        // YouTube embed
        'https://www.youtube.com',
        'https://www.youtube-nocookie.com',

        // Google Maps
        'https://maps.googleapis.com',
        'https://maps.gstatic.com',

        // Gravatar
        'https://www.gravatar.com',

        // Cloudflare Turnstile
        'https://challenges.cloudflare.com',

        // CDN (必要に応じて追加)
        // 'https://cdn.jsdelivr.net',
        // 'https://cdnjs.cloudflare.com',

        // Tailwind CSS Play CDN (開発用)
        // 'https://cdn.tailwindcss.com',

    ],

    /*
    |--------------------------------------------------------------------------
    | Admin-specific Directives
    |--------------------------------------------------------------------------
    |
    | Additional directives for admin panel only.
    | These are added only on admin panel routes.
    |
    */
    'admin_directives' => [
        // 管理画面で必要な追加設定があればここに
    ],

    /*
    |--------------------------------------------------------------------------
    | Front-specific Directives
    |--------------------------------------------------------------------------
    |
    | Additional directives for frontend only.
    | These are added only on frontend routes.
    |
    */
    'front_directives' => [
        // フロントエンドで必要な追加設定があればここに
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Detection Keywords
    |--------------------------------------------------------------------------
    |
    | Keyword definitions for automatically categorizing trusted domains
    | into appropriate CSP directives. When domain names contain these keywords,
    | they are added to the corresponding directives.
    |
    */
    'domain_detection_keywords' => [
        // Font-related → font-src, style-src
        'font-src' => [
            'font', 'fonts', 'typekit', 'typography',
        ],

        // Script-related → script-src
        'script-src' => [
            'cdn', 'cdnjs', 'jsdelivr', 'unpkg', 'cloudflare',
            'ajax', 'api', 'sdk', 'js', 'script',
            'recaptcha', 'captcha', 'turnstile', 'challenges',
            'analytics', 'gtag', 'gtm', 'tag', 'tracking',
            'jquery', 'bootstrap', 'vue', 'react', 'angular',
        ],

        // Style-related → style-src
        'style-src' => [
            'css', 'style', 'styles', 'theme',
            'bootstrap', 'tailwind', 'bulma', 'materialize',
        ],

        // Image-related → img-src
        'img-src' => [
            'img', 'image', 'images', 'photo', 'photos',
            'static', 'assets', 'media', 'upload', 'uploads',
            'gravatar', 'avatar', 'icon', 'icons',
        ],

        // iframe/frame-related → frame-src
        'frame-src' => [
            'embed', 'widget', 'iframe', 'frame',
            'recaptcha', 'captcha', 'turnstile', 'challenges',
            'youtube', 'vimeo', 'player', 'video',
            'maps', 'map',
        ],

        // Connection-related (API, WebSocket, etc.) → connect-src
        'connect-src' => [
            'api', 'ws', 'wss', 'socket', 'realtime',
            'graphql', 'rest', 'endpoint',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Purpose Domain Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords for domains used for multiple purposes.
    | Domains containing these keywords are added to multiple specified directives.
    |
    */
    'multi_purpose_keywords' => [
        // CDN domains are multi-purpose
        'cdn' => ['script-src', 'style-src', 'font-src', 'img-src'],
        'cdnjs' => ['script-src', 'style-src', 'font-src', 'img-src'],
        'jsdelivr' => ['script-src', 'style-src', 'font-src', 'img-src'],

        // Generic static domains serve multiple purposes
        'static' => ['script-src', 'style-src', 'img-src', 'font-src'],
        'assets' => ['script-src', 'style-src', 'img-src', 'font-src'],

        // gstatic.com is special (Google static resources)
        'gstatic' => ['script-src', 'style-src', 'img-src', 'font-src', 'frame-src'],

        // Font services provide both stylesheets and font files
        'fonts' => ['style-src', 'font-src'],
    ],
];
