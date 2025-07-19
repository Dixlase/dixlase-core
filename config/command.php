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
    // 例: スタブのデフォルト格納先
    'default_stub_directory' => base_path('vendor/laravel/framework/src/Illuminate/Routing/Console/stubs'),

    // 独自のカスタムスタブファイルの格納先
    'custom_stub_directory' => base_path('stubs/custom'),

    // 他にも繰り返し使うような定数など
    'license_txt' => base_path('license.txt'),
    'license_json' => base_path('license-info.json'),

    // ファイルカテゴリごとのベースディレクトリと名前空間設定
    // 形式: 'category' => [path, namespace]
    'category_paths' => [
        // App directory files
        'controllers' => ['app/Http/Controllers', 'App\\Http\\Controllers'],
        'models' => ['app/Models', 'App\\Models'],
        'requests' => ['app/Http/Requests', 'App\\Http\\Requests'],
        'providers' => ['app/Providers', 'App\\Providers'],
        'policies' => ['app/Policies', 'App\\Policies'],
        'listeners' => ['app/Listeners', 'App\\Listeners'],
        'observers' => ['app/Observers', 'App\\Observers'],
        'jobs' => ['app/Jobs', 'App\\Jobs'],
        'middleware' => ['app/Http/Middleware', 'App\\Http\\Middleware'],
        'services' => ['app/Services', 'App\\Services'],
        'repositories' => ['app/Repositories', 'App\\Repositories'],
        'traits' => ['app/Traits', 'App\\Traits'],
        'views' => ['resources/views', ''],
        'routes' => ['routes', ''],
        'lang' => ['lang', ''],
        'config' => ['config', ''],
        'migrations' => ['database/migrations', 'Database\\Migrations'],
        'seeders' => ['database/seeders', 'Database\\Seeders'],
        'factories' => ['database/factories', 'Database\\Factories'],
    ],
];
