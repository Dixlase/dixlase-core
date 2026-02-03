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
    'directives' => [

        // デフォルトのフォールバック
        'default-src' => ["'self'"],

        // スクリプト
        // 'nonce' は自動的にリクエストごとのnonce値に置換される
        // 'strict-dynamic' でnonce付きスクリプトから読み込まれるスクリプトも許可
        // 'unsafe-eval' はAlpine.jsが必要とするため追加
        // CAPTCHA用: Cloudflare Turnstile, Google reCAPTCHA
        // 開発環境: Vite開発サーバー
        'script-src' => ["'self'", "'nonce'", "'strict-dynamic'", "'unsafe-eval'", 'https://challenges.cloudflare.com', 'https://www.google.com', 'https://www.gstatic.com', 'https://localhost:5173'],

        // スクリプト属性（onclick等のイベントハンドラ属性）
        // 注意: 開発モードのみ許可。標準/厳格モードでは'none'に設定される
        // Alpine.jsの@click等はscript-src-attrではなくscript-srcで制御される
        'script-src-attr' => ["'unsafe-inline'"], // 開発モード用

        // スタイル
        // 'unsafe-inline'はnonceと併用すると無視されるため、インラインスタイル（element.style）を許可するには
        // nonceを使用しないか、unsafe-inlineのみを使用する必要がある
        // Alpine.jsやJavaScriptでのスタイル操作を許可するためunsafe-inlineを使用
        // 開発環境のViteサーバーからのスタイルシート読み込みを許可
        'style-src' => ["'self'", "'unsafe-inline'", 'https://localhost:5173'],

        // 画像
        'img-src' => ["'self'", 'data:', 'blob:'],

        // フォント
        'font-src' => ["'self'", 'data:'],

        // 接続先（XHR, fetch, WebSocket等）
        // Vite開発サーバー（WebSocket）とCAPTCHA用（Cloudflare Turnstile, Google reCAPTCHA）
        'connect-src' => ["'self'", 'wss://localhost:5173', 'https://localhost:5173', 'https://challenges.cloudflare.com', 'https://www.google.com'],

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

    ],

    /*
    |--------------------------------------------------------------------------
    | Admin-specific Directives
    |--------------------------------------------------------------------------
    |
    | 管理画面専用のCSPディレクティブ。
    | これらは管理画面でのみ適用され、フロントエンドの設定を上書きします。
    |
    */
    'admin_directives' => [
        // 管理画面はiframe埋め込みを完全禁止（クリックジャッキング対策）
        'frame-ancestors' => ["'none'"],
    ],
];
