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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

        // Bunny Fonts (Dixlaseデフォルト)
        'https://fonts.bunny.net',

        // Font Awesome
        'https://use.fontawesome.com',

        // Google reCAPTCHA
        'https://www.google.com',
        'https://www.gstatic.com',

        // YouTube 埋め込み
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
    | 管理画面専用の追加ディレクティブ。
    | 管理画面ルートでのみこれらが追加されます。
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
    | フロントエンド専用の追加ディレクティブ。
    | フロントエンドルートでのみこれらが追加されます。
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
    | 信頼済みドメインを適切なCSPディレクティブに自動振り分けするための
    | キーワード定義。ドメイン名にこれらのキーワードが含まれている場合、
    | 対応するディレクティブに追加されます。
    |
    */
    'domain_detection_keywords' => [
        // フォント関連 → font-src, style-src
        'font-src' => [
            'font', 'fonts', 'typekit', 'typography',
        ],

        // スクリプト関連 → script-src
        'script-src' => [
            'cdn', 'cdnjs', 'jsdelivr', 'unpkg', 'cloudflare',
            'ajax', 'api', 'sdk', 'js', 'script',
            'recaptcha', 'captcha', 'turnstile', 'challenges',
            'analytics', 'gtag', 'gtm', 'tag', 'tracking',
            'jquery', 'bootstrap', 'vue', 'react', 'angular',
        ],

        // スタイル関連 → style-src
        'style-src' => [
            'css', 'style', 'styles', 'theme',
            'bootstrap', 'tailwind', 'bulma', 'materialize',
        ],

        // 画像関連 → img-src
        'img-src' => [
            'img', 'image', 'images', 'photo', 'photos',
            'static', 'assets', 'media', 'upload', 'uploads',
            'gravatar', 'avatar', 'icon', 'icons',
        ],

        // iframe/フレーム関連 → frame-src
        'frame-src' => [
            'embed', 'widget', 'iframe', 'frame',
            'recaptcha', 'captcha', 'turnstile', 'challenges',
            'youtube', 'vimeo', 'player', 'video',
            'maps', 'map',
        ],

        // 接続関連（API、WebSocket等） → connect-src
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
    | 複数の用途に使われるドメインのキーワード。
    | これらのキーワードを含むドメインは、指定された複数のディレクティブに追加されます。
    |
    */
    'multi_purpose_keywords' => [
        // CDN系ドメインは複数用途
        'cdn' => ['script-src', 'style-src', 'font-src', 'img-src'],
        'cdnjs' => ['script-src', 'style-src', 'font-src', 'img-src'],
        'jsdelivr' => ['script-src', 'style-src', 'font-src', 'img-src'],

        // 汎用的なstaticドメインは複数用途
        'static' => ['script-src', 'style-src', 'img-src', 'font-src'],
        'assets' => ['script-src', 'style-src', 'img-src', 'font-src'],

        // gstatic.comは特殊（Google系の静的リソース）
        'gstatic' => ['script-src', 'style-src', 'img-src', 'font-src', 'frame-src'],

        // フォントサービスはスタイルシートとフォントファイルの両方を提供
        'fonts' => ['style-src', 'font-src'],
    ],
];
