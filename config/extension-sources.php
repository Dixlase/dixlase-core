<?php

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
    'default_publisher_key_id' => env('DIXLASE_DEFAULT_PUBLISHER_KEY_ID', 'dixlase-authority-2026'),

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
