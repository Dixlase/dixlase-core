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

    /*
    |--------------------------------------------------------------------------
    | CSP Enabled
    |--------------------------------------------------------------------------
    |
    | CSP機能の有効/無効を制御します。
    | 実際の設定はデータベース（SecuritySetting）から読み込まれます。
    |
    */
    'enabled' => env('CSP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | CSP Mode
    |--------------------------------------------------------------------------
    |
    | CSPの動作モードを指定します。
    | - 'enforce': Content-Security-Policy ヘッダーを使用（違反をブロック）
    | - 'report-only': Content-Security-Policy-Report-Only ヘッダーを使用（違反を報告のみ）
    |
    | 実際の設定はデータベース（SecuritySetting）から読み込まれます。
    |
    */
    'mode' => env('CSP_MODE', 'report-only'),

    /*
    |--------------------------------------------------------------------------
    | Report URI
    |--------------------------------------------------------------------------
    |
    | CSP違反レポートを送信するエンドポイントのパス。
    | このパスはCSPミドルウェアから除外されます。
    |
    */
    'report_uri' => '/csp-report',

    /*
    |--------------------------------------------------------------------------
    | Nonce Length
    |--------------------------------------------------------------------------
    |
    | 生成するnonceの長さ（バイト数）。
    | 推奨: 16バイト以上（Base64エンコード後は約22文字）
    |
    */
    'nonce_length' => 16,

    /*
    |--------------------------------------------------------------------------
    | Default Directives
    |--------------------------------------------------------------------------
    |
    | デフォルトのCSPディレクティブ設定。
    | プラグイン・テーマからの追加ポリシーはこれにマージされます。
    |
    | 特殊値:
    | - 'self': 同一オリジンのみ許可
    | - 'none': すべて拒否
    | - 'unsafe-inline': インラインスクリプト/スタイルを許可（非推奨）
    | - 'unsafe-eval': eval()等を許可（非推奨）
    | - 'strict-dynamic': nonce付きスクリプトから読み込まれるスクリプトを許可
    | - 'nonce': 自動的にリクエストごとのnonceに置換される
    |
    */
    'directives' => [

        // デフォルトのフォールバック
        'default-src' => ["'self'"],

        // スクリプト
        // 'nonce' は自動的にリクエストごとのnonce値に置換される
        // 'strict-dynamic' でnonce付きスクリプトから読み込まれるスクリプトも許可
        'script-src' => ["'self'", "'nonce'", "'strict-dynamic'"],

        // スタイル
        'style-src' => ["'self'", "'nonce'", "'unsafe-inline'"],

        // 画像
        'img-src' => ["'self'", 'data:', 'blob:'],

        // フォント
        'font-src' => ["'self'", 'data:'],

        // 接続先（XHR, fetch, WebSocket等）
        'connect-src' => ["'self'"],

        // メディア（audio, video）
        'media-src' => ["'self'"],

        // オブジェクト（plugin, embed, object）
        'object-src' => ["'none'"],

        // フレーム
        'frame-src' => ["'self'"],

        // フレーム祖先（このページを埋め込める親）
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
    | Trusted Domains
    |--------------------------------------------------------------------------
    |
    | 信頼済みドメインのリスト。
    | これらのドメインは複数のディレクティブに自動的に追加されます。
    |
    */
    'trusted_domains' => [
        // Google Fonts
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',

        // Bunny Fonts (Dixlaseデフォルト)
        'https://fonts.bunny.net',

        // CDN (必要に応じて追加)
        // 'https://cdn.jsdelivr.net',
        // 'https://cdnjs.cloudflare.com',
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
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | CSPヘッダーを付与しないパスのリスト。
    | 正規表現パターンで指定可能。
    |
    */
    'excluded_paths' => [
        '/csp-report',      // CSPレポートエンドポイント自体
        '/api/*',           // API（必要に応じて）
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Violations
    |--------------------------------------------------------------------------
    |
    | CSP違反をログに記録するかどうか。
    |
    */
    'log_violations' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | CSP違反ログを出力するチャンネル。
    |
    */
    'log_channel' => 'csp',

];
