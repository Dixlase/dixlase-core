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
    'pages_directory' => 'pages', // ページのマークダウンファイルを保存するディレクトリ
    'custom_files_dir' => env('CUSTOM_FILES_DIR', 'custom'), // カスタムファイルのディレクトリ
    'default_merge_mode' => env('DEFAULT_MERGE_MODE', 'merge'), // デフォルトのマージモード



    // カスタムファイルのディレクトリ
    'file_types' => [
        'routes' => [
            'path' => 'routes',
            'namespace' => '',
            'naming_convention' => 'snake_case', // スネークケース
        ],
        'config' => [
            'path' => 'config',
            'namespace' => '',
            'naming_convention' => 'snake_case', // スネークケース
        ],
        'lang' => [
            'path' => 'lang',
            'namespace' => '',
            'naming_convention' => 'snake_case', // スネークケース
        ],
        'controllers' => [
            'path' => 'app/Http/Controllers',
            'namespace' => 'App\\Http\\Controllers\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'models' => [
            'path' => 'app/Models',
            'namespace' => 'App\\Models\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'middleware' => [
            'path' => 'app/Http/Middleware',
            'namespace' => 'App\\Http\\Middleware\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'events' => [
            'path' => 'app/Events',
            'namespace' => 'App\\Events\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'jobs' => [
            'path' => 'app/Jobs',
            'namespace' => 'App\\Jobs\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'policies' => [
            'path' => 'app/Policies',
            'namespace' => 'App\\Policies\\',
            'naming_convention' => 'studly_case', // キャメルケース
        ],
        'migrations' => [
            'path' => 'database/migrations',
            'namespace' => '',
            'naming_convention' => 'snake_case_with_timestamp', // スネークケース + タイムスタンプ
        ],
        'views' => [
            'path' => 'resources/views',
            'namespace' => '',
            'naming_convention' => 'kebab_case', // ケバブケース
        ],
    ],
    //
    'custom' => [
        'default_license' =>  'agpl',
    ],

];
