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
    | - 'development': 開発モード（Report-Only、インラインJS許可）
    | - 'standard': 標準モード（nonce付きインラインのみ許可）
    | - 'strict': 厳格モード（インライン一切禁止）
    |
    | 実際の設定はデータベース（SecuritySetting）から読み込まれます。
    |
    */
    'mode' => env('CSP_MODE', 'development'),

    /*
    |--------------------------------------------------------------------------
    | CSP Mode Definitions
    |--------------------------------------------------------------------------
    |
    | 各モードの詳細設定。
    |
    */
    'modes' => [
        // 開発モード: インラインJS/CSS許可、Report-Onlyで違反を記録
        'development' => [
            'header' => 'Content-Security-Policy-Report-Only',
            'allow_inline_scripts' => true,
            'allow_inline_styles' => true,
            'allow_eval' => true,
            'require_nonce' => false,
            'block_inline_plugins' => false,
        ],
        // 標準モード: nonce付きインラインのみ許可
        'standard' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false,
            'allow_inline_styles' => false,
            'allow_eval' => false,
            'require_nonce' => true,
            'block_inline_plugins' => false,
        ],
        // 厳格モード: インライン一切禁止、requires_inline_jsプラグインをブロック
        'strict' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false,
            'allow_inline_styles' => false,
            'allow_eval' => false,
            'require_nonce' => true,
            'block_inline_plugins' => true,
        ],
    ],

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
        // 'unsafe-eval' はAlpine.jsが必要とするため追加
        'script-src' => ["'self'", "'nonce'", "'strict-dynamic'", "'unsafe-eval'"],

        // スタイル
        // 'unsafe-inline'はnonceと併用すると無視されるため、インラインスタイル（element.style）を許可するには
        // nonceを使用しないか、unsafe-inlineのみを使用する必要がある
        // Alpine.jsやJavaScriptでのスタイル操作を許可するためunsafe-inlineを使用
        'style-src' => ["'self'", "'unsafe-inline'"],

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

    /*
    |--------------------------------------------------------------------------
    | Blocklist Sources
    |--------------------------------------------------------------------------
    |
    | 拒否ドメインリストの取得元。
    | 外部のブロックリストから既知の悪意あるドメインを取得します。
    |
    */
    'blocklist_sources' => [
        // トラッキング・広告ブロック
        'tracking' => [
            'name' => 'トラッキング・広告',
            'name_en' => 'Tracking & Ads',
            'description' => '広告ネットワーク、トラッキングサービス、アナリティクス等',
            'description_en' => 'Ad networks, tracking services, analytics, etc.',
            'lists' => [
                // Peter Lowe's Ad and tracking server list
                'https://pgl.yoyo.org/adservers/serverlist.php?hostformat=nohtml&showintro=0',
                // AdGuard Tracking Protection
                'https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_trackers.txt',
            ],
        ],
        // マルウェア・フィッシング
        'malware' => [
            'name' => 'マルウェア・フィッシング',
            'name_en' => 'Malware & Phishing',
            'description' => '既知のマルウェア配布サイト、フィッシングサイト',
            'description_en' => 'Known malware distribution sites, phishing sites',
            'lists' => [
                // URLhaus Malware URLs (domains only)
                'https://urlhaus.abuse.ch/downloads/hostfile/',
            ],
        ],
        // 暗号通貨マイニング
        'cryptominer' => [
            'name' => '暗号通貨マイニング',
            'name_en' => 'Cryptominers',
            'description' => 'ブラウザベースの暗号通貨マイニングスクリプト',
            'description_en' => 'Browser-based cryptocurrency mining scripts',
            'lists' => [
                // NoCoin list (hoshsadiq/adblock-nocoin-list)
                'https://raw.githubusercontent.com/hoshsadiq/adblock-nocoin-list/master/hosts.txt',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocklist Cache TTL
    |--------------------------------------------------------------------------
    |
    | ブロックリストのキャッシュ時間（秒）。
    | デフォルト: 86400秒（24時間）
    |
    */
    'blocklist_cache_ttl' => env('CSP_BLOCKLIST_CACHE_TTL', 86400),

];
