<?php
return [
    'pages_directory' => 'pages', // ページのマークダウンファイルを保存するディレクトリ
    'custom_files_dir' => env('CUSTOM_FILES_DIR', 'custom'), // カスタムファイルのディレクトリ
    'default_merge_mode' => env('DEFAULT_MERGE_MODE', 'merge'), // デフォルトのマージモード



    // カスタムファイルのディレクトリ
    'custom_file_types' => [
        'controllers' => [
            'path' => 'app/Http/Controllers',
            'namespace' => 'App\\Http\\Controllers\\',
        ],
        'requests' => [
            'path' => 'app/Http/Requests',
            'namespace' => 'App\\Http\\Requests\\',
        ],
        'models' => [
            'path' => 'app/Models',
            'namespace' => 'App\\Models\\',
        ],
        'middleware' => [
            'path' => 'app/Http/Middleware',
            'namespace' => 'App\\Http\\Middleware\\',
        ],
        'events' => [
            'path' => 'app/Events',
            'namespace' => 'App\\Events\\',
        ],
        'listeners' => [
            'path' => 'app/Listeners',
            'namespace' => 'App\\Listeners\\',
        ],
        'jobs' => [
            'path' => 'app/Jobs',
            'namespace' => 'App\\Jobs\\',
        ],
        'policies' => [
            'path' => 'app/Policies',
            'namespace' => 'App\\Policies\\',
        ],
        'notifications' => [
            'path' => 'app/Notifications',
            'namespace' => 'App\\Notifications\\',
        ],
        'commands' => [
            'path' => 'app/Console/Commands',
            'namespace' => 'App\\Console\\Commands\\',
        ],
        'providers' => [
            'path' => 'app/Providers',
            'namespace' => 'App\\Providers\\',
        ],
    ],
];
