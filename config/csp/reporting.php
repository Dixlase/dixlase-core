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
        // マルウェア
        'malware' => [
            'name' => 'マルウェア',
            'name_en' => 'Malware',
            'description' => '既知のマルウェア配布サイト',
            'description_en' => 'Known malware distribution sites',
            'lists' => [
                // URLhaus Malware URLs (domains only)
                'https://urlhaus.abuse.ch/downloads/hostfile/',
            ],
        ],
        // フィッシング
        'phishing' => [
            'name' => 'フィッシング',
            'name_en' => 'Phishing',
            'description' => 'フィッシング詐欺サイト',
            'description_en' => 'Phishing scam sites',
            'lists' => [
                // OpenPhish feed
                'https://openphish.com/feed.txt',
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
