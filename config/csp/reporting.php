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
        'install',          // Installation screen (DB not configured)
        'install/*',        // Installation screen subpath
        '/csp-report',      // CSP report endpoint itself
        '/api/*',           // API（必要に応じて）
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Violations
    |--------------------------------------------------------------------------
    |
    | Whether to log CSP violations
    |
    */
    'log_violations' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | Channel to output CSP violation logs
    |
    */
    'log_channel' => 'csp',

    /*
    |--------------------------------------------------------------------------
    | Blocklist Sources
    |--------------------------------------------------------------------------
    |
    | Source for deny domain list
    | Fetch known malicious domains from external blocklists
    |
    */
    'blocklist_sources' => [
        // Tracking & ad blocking
        'tracking' => [
            'name' => 'Tracking & Advertising',
            'name_en' => 'Tracking & Ads',
            'description' => 'Ad networks, tracking services, analytics, etc.',
            'description_en' => 'Ad networks, tracking services, analytics, etc.',
            'lists' => [
                // Peter Lowe's Ad and tracking server list
                'https://pgl.yoyo.org/adservers/serverlist.php?hostformat=nohtml&showintro=0',
                // AdGuard Tracking Protection
                'https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_trackers.txt',
            ],
        ],
        // Malware
        'malware' => [
            'name' => 'Malware',
            'name_en' => 'Malware',
            'description' => 'Known malware distribution sites',
            'description_en' => 'Known malware distribution sites',
            'lists' => [
                // URLhaus Malware URLs (domains only)
                'https://urlhaus.abuse.ch/downloads/hostfile/',
            ],
        ],
        // Phishing
        'phishing' => [
            'name' => 'Phishing',
            'name_en' => 'Phishing',
            'description' => 'Phishing fraud sites',
            'description_en' => 'Phishing scam sites',
            'lists' => [
                // OpenPhish feed
                'https://openphish.com/feed.txt',
            ],
        ],
        // Cryptocurrency mining
        'cryptominer' => [
            'name' => 'Cryptocurrency mining',
            'name_en' => 'Cryptominers',
            'description' => 'Browser-based cryptocurrency mining scripts',
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
    | Blocklist cache time (seconds)
    | Default: 86400 seconds (24 hours)
    |
    */
    'blocklist_cache_ttl' => env('CSP_BLOCKLIST_CACHE_TTL', 86400),
];
