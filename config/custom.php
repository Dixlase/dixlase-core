<?php

/**
 * This file is part of MySoftware.
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
    'pages_directory' => 'pages', // ページのマークダウンファイルを保存するディレクトリ
    'custom_files_dir' => env('CUSTOM_FILES_DIR', 'custom'), // カスタムファイルのディレクトリ
    'default_merge_mode' => env('DEFAULT_MERGE_MODE', 'merge'), // デフォルトのマージモード



    // カスタムファイルのディレクトリ
    'file_types' => [
        'routes' => [
            'path' => 'routes',
            'namespace' => '', // ルートファイルにはネームスペースは不要
        ],
        'config' => [
            'path' => 'config',
            'namespace' => '', // コンフィグにはネームスペースは不要
        ],
        'lang' => [
            'path' => 'lang',
            'namespace' => '', // 言語ファイルにはネームスペースは不要
        ],
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
        'views' => [
            'path' => 'resources/views',
            'namespace' => '',
        ],
        'helpers' => [
            'path' => 'helpers',
            'namespace' => '',
        ],
    ],
];
