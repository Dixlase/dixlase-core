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
    | Admin CSP Mode (管理画面専用CSPモード)
    |--------------------------------------------------------------------------
    |
    | 管理画面とフロントエンドで異なるCSPモードを使用できます。
    |
    | - null: フロントエンドと同じモードを使用（デフォルト）
    | - 'development' / 'standard' / 'strict': 管理画面専用のモード
    |
    | 推奨設定：
    | - フロント: strict（最大セキュリティ）
    | - 管理画面: standard（実用性とセキュリティのバランス）
    |
    | これにより、管理画面が壊れるリスクを最小化しつつ、
    | フロントエンドで最大限のセキュリティを実現できます。
    |
    */
    'admin_mode' => env('CSP_ADMIN_MODE', null),

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
            'allow_inline_scripts' => false, // 違反を記録するためfalse（Report-Onlyなので動作する）
            'allow_inline_styles' => true,
            'allow_eval' => true,
            'allow_unsafe_inline' => false,  // 違反を記録するためfalse（Report-Onlyなので動作する）
            'require_nonce' => true,         // nonce付きスクリプトを推奨
            'block_inline_plugins' => false,
            'enforce_deny_domains' => false, // 拒否ドメインも警告のみ
            'strict_dynamic' => false,       // Vite互換性のためfalse
            'block_script_attr' => false,    // Report-Onlyなので動作する
            'description' => 'テーマ/プラグイン開発用。すべて動作するが違反を記録。',
            'description_en' => 'For theme/plugin development. Everything works but violations are logged.',
        ],
        
        // 標準モード: 本番推奨、nonce付きインラインのみ許可
        'standard' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false, // unsafe-inline禁止
            'allow_inline_styles' => true,   // Alpine.jsのインラインスタイル用に許可
            'allow_eval' => true,            // Alpine.jsが必要とするため許可
            'allow_unsafe_inline' => false,
            'require_nonce' => true,         // ヘルパー経由でnonceを要求
            'allow_nonce_inline_execution' => true, // nonce付き実行コードは許可
            'block_inline_plugins' => true,
            'enforce_deny_domains' => true,  // 拒否ドメインを強制ブロック
            'strict_dynamic' => true,        // 推奨ON（入口を絞る）
            'warn_onclick' => true,          // onclick等を警告（ブロックはしない）
            'block_script_attr' => true,     // script-src-attrでunsafe-inline禁止
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
    'nonce_length' => 16,];
