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
    // Interval between automatic update checks (seconds)
    'check_interval' => env('EXTENSION_UPDATE_CHECK_INTERVAL', 86400),

    // Directory for temporary extension downloads
    'download_path' => storage_path('app/extension-downloads'),

    // Provider type registry
    'providers' => [
        'github' => \App\Services\Extension\GitHubSourceProvider::class,
        // 'marketplace' => \App\Services\Extension\MarketplaceSourceProvider::class,
        // 'composer' => \App\Services\Extension\ComposerSourceProvider::class,
    ],

    // GitHub provider settings
    'github' => [
        'api_base' => 'https://api.github.com',
        'default_owner' => env('EXTENSION_GITHUB_OWNER', 'Dixlase'),
        'default_token' => env('EXTENSION_GITHUB_TOKEN'),
        // Repo naming: {prefix}{plugin.json slug}
        // e.g., plugin.json slug "dixlase-seo" → repo "plugin-dixlase-seo"
        'repo_prefix' => 'plugin-',
        'theme_repo_prefix' => 'theme-',
    ],

    // Key ID used for official source signature verification
    'official_key_id' => env('EXTENSION_SOURCE_KEY_ID', 'dixlase-authority-2026'),

    // 新規プラグイン・テーマ作成時のデフォルト値（dls:make:plugin / dls:make:theme）
    'default_author_id' => env('DIXLASE_DEFAULT_AUTHOR_ID', ''),
    'default_authority_key_id' => env('DIXLASE_DEFAULT_AUTHORITY_KEY_ID', 'dixlase-authority-2026'),

    // Preset source definitions (hardcoded official sources)
    'presets' => [
        'github' => [
            'name' => 'GitHub',
            'icon' => 'fab fa-github',
            'is_official' => true,
            'description_key' => 'admin/settings/security/extensions.source.github_description',
        ],
        // 'marketplace' => [
        //     'name' => 'Dixlase Marketplace',
        //     'icon' => 'fas fa-store',
        //     'is_official' => true,
        //     'description_key' => 'admin/settings/security/extensions.source.marketplace_description',
        // ],
    ],

    // Update check interval options (seconds)
    'check_intervals' => [
        86400 => 'admin/settings/security/extensions.source.interval_daily',
        43200 => 'admin/settings/security/extensions.source.interval_12h',
        21600 => 'admin/settings/security/extensions.source.interval_6h',
        0 => 'admin/settings/security/extensions.source.interval_manual',
    ],
];
