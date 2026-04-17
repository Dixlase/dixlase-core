<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

return [
    // デフォルトのフォールバック
    'default-src' => ["'self'"],

    // スクリプト
    // 'nonce' は自動的にリクエストごとのnonce値に置換される
    // 'strict-dynamic' でnonce付きスクリプトから読み込まれるスクリプトも許可
    // 'unsafe-eval' はAlpine.jsが必要とするため追加
    // CAPTCHA用: Cloudflare Turnstile, Google reCAPTCHA
    // Vite開発サーバーは CspBuilder が local 環境でのみ自動追加
    'script-src' => ["'self'", "'nonce'", "'strict-dynamic'", "'unsafe-eval'", 'https://challenges.cloudflare.com', 'https://www.google.com', 'https://www.gstatic.com'],

    // スクリプト属性（onclick等のイベントハンドラ属性）
    // ベース値は'none'（ブロック）。開発モードではCspBuilderが'unsafe-inline'に上書き
    // Alpine.jsの@click等はscript-src-attrではなくscript-srcで制御される
    'script-src-attr' => ["'none'"],

    // スタイル
    // 'unsafe-inline'はnonceと併用すると無視されるため、インラインスタイル（element.style）を許可するには
    // nonceを使用しないか、unsafe-inlineのみを使用する必要がある
    // Alpine.jsやJavaScriptでのスタイル操作を許可するためunsafe-inlineを使用
    // Bunny Fonts、Font Awesome CDN
    'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', 'https://cdnjs.cloudflare.com', 'https://use.fontawesome.com'],

    // 画像
    // raw.githubusercontent.com: オンライン拡張機能追加画面のサムネイル（GitHub Source Provider）
    'img-src' => ["'self'", 'data:', 'blob:', 'https://raw.githubusercontent.com'],

    // フォント
    // Bunny Fonts、Font Awesome CDN
    // ローカルフォント（Vite build assets）
    'font-src' => ["'self'", 'data:', 'blob:', 'https://fonts.bunny.net', 'https://cdnjs.cloudflare.com', 'https://use.fontawesome.com'],

    // 接続先（XHR, fetch, WebSocket等）
    // CAPTCHA用: Cloudflare Turnstile, Google reCAPTCHA
    'connect-src' => ["'self'", 'https://challenges.cloudflare.com', 'https://www.google.com'],

    // メディア（audio, video）
    'media-src' => ["'self'"],

    // オブジェクト（plugin, embed, object）
    'object-src' => ["'none'"],

    // フレーム
    // CAPTCHA用: Cloudflare Turnstile, Google reCAPTCHA
    'frame-src' => ["'self'", 'https://challenges.cloudflare.com', 'https://www.google.com', 'https://www.gstatic.com'],

    // フレーム祖先（このページを埋め込める親）
    // 注意: 管理画面では'none'に上書きされる（クリックジャッキング対策）
    'frame-ancestors' => ["'self'"],

    // フォームの送信先
    'form-action' => ["'self'"],

    // ベースURI
    'base-uri' => ["'self'"],

    // マニフェスト
    'manifest-src' => ["'self'"],

    // ワーカー
    'worker-src' => ["'self'", 'blob:'],
];
