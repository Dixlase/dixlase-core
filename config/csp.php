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
    |
    | - 'development': 開発モード
    |   - Report-Only（ブロックせず記録のみ）
    |   - インラインJS/CSS許可（unsafe-inline）
    |   - eval許可（unsafe-eval）
    |   - 拒否ドメインのみブロック可能
    |   - プラグイン互換性：最大
    |
    | - 'standard': 標準モード（本番推奨）
    |   - CSP強制（ブロック）
    |   - インライン実行コード：ヘルパー経由（nonce付き）のみ許可
    |   - onclick等属性イベント：警告（移行期は動作許可）
    |   - unsafe-eval禁止
    |   - strict-dynamic推奨（任意）
    |   - プラグイン互換性：高
    |
    | - 'strict': 厳格モード（最大セキュリティ）
    |   - CSP強制（ブロック）
    |   - インライン実行コード：完全禁止（nonceでも不可）
    |   - データ受け渡し：type="application/json"、data-*のみ許可
    |   - 外部JSのみ（dixlase-boot.js経由で初期化）
    |   - onclick等属性イベント：禁止
    |   - unsafe-eval禁止
    |   - strict-dynamic推奨（ON）
    |   - requires_inline_js: trueのプラグイン：有効化不可
    |   - プラグイン互換性：CSP Readyのみ
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
        // 開発モード: 最大互換性、Report-Onlyで違反を記録
        'development' => [
            'header' => 'Content-Security-Policy-Report-Only',
            'allow_inline_scripts' => true,
            'allow_inline_styles' => true,
            'allow_eval' => true,
            'allow_unsafe_inline' => true,
            'require_nonce' => false,
            'block_inline_plugins' => false,
            'enforce_deny_domains' => false, // 拒否ドメインも警告のみ
            'strict_dynamic' => false,
            'description' => 'テーマ/プラグイン開発用。すべて動作するが違反を記録。',
            'description_en' => 'For theme/plugin development. Everything works but violations are logged.',
        ],
        
        // 標準モード: 本番推奨、nonce付きインラインのみ許可
        'standard' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false, // unsafe-inline禁止
            'allow_inline_styles' => false,  // unsafe-inline禁止
            'allow_eval' => true,            // Alpine.jsが必要とするため許可
            'allow_unsafe_inline' => false,
            'require_nonce' => true,         // ヘルパー経由でnonceを要求
            'allow_nonce_inline_execution' => true, // nonce付き実行コードは許可
            'block_inline_plugins' => true,
            'enforce_deny_domains' => true,  // 拒否ドメインを強制ブロック
            'strict_dynamic' => false,       // 任意（互換性のためデフォルトOFF）
            'warn_onclick' => true,          // onclick等を警告（ブロックはしない）
            'description' => '本番運用推奨。ヘルパー経由のインラインは許可。',
            'description_en' => 'Recommended for production. Inline via helpers allowed.',
        ],
        
        // 厳格モード: 最大セキュリティ、外部JSのみ
        'strict' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false,
            'allow_inline_styles' => false,
            'allow_eval' => false,
            'allow_unsafe_inline' => false,
            'require_nonce' => false,        // nonceも使用しない（外部JSのみ）
            'allow_nonce_inline_execution' => false, // nonce付きでも実行コード禁止
            'allow_json_script' => true,     // type="application/json"は許可
            'allow_data_attributes' => true, // data-*属性は許可
            'block_inline_plugins' => true,  // requires_inline_js: trueを拒否
            'enforce_deny_domains' => true,
            'strict_dynamic' => true,        // 推奨ON
            'block_onclick' => true,         // onclick等を完全ブロック
            'require_bootloader' => true,    // dixlase-boot.js必須
            'description' => '最大セキュリティ。CSP Readyプラグインのみ動作。',
            'description_en' => 'Maximum security. Only CSP Ready plugins work.',
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
        // CAPTCHA用: Cloudflare Turnstile, Google reCAPTCHA
        // 開発環境: Vite開発サーバー
        'script-src' => ["'self'", "'nonce'", "'strict-dynamic'", "'unsafe-eval'", 'https://challenges.cloudflare.com', 'https://www.google.com', 'https://www.gstatic.com', 'https://localhost:5173'],

        // スクリプト属性（onclick等のイベントハンドラ属性）
        // Alpine.jsの@click等のディレクティブはイベントハンドラ属性として展開されるため必要
        'script-src-attr' => ["'unsafe-inline'"],

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
        'install',          // インストール画面（DB未設定のため）
        'install/*',        // インストール画面のサブパス
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
