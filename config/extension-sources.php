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
        'repo_prefix' => 'dixlase-',
        'theme_repo_prefix' => 'dixlase-theme-',
    ],

    // Key ID used for official source signature verification
    'official_key_id' => env('EXTENSION_SOURCE_KEY_ID', 'dixlase-authority-2026'),
];
