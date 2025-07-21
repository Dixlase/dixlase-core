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
        'controller' => ['app/Http/Controllers', 'App\\Http\\Controllers'],
        'model' => ['app/Models', 'App\\Models'],
        'request' => ['app/Http/Requests', 'App\\Http\\Requests'],
        'provider' => ['app/Providers', 'App\\Providers'],
        'policy' => ['app/Policies', 'App\\Policies'],
        'listener' => ['app/Listeners', 'App\\Listeners'],
        'observer' => ['app/Observers', 'App\\Observers'],
        'job' => ['app/Jobs', 'App\\Jobs'],
        'middleware' => ['app/Http/Middleware', 'App\\Http\\Middleware'],
        'service' => ['app/Services', 'App\\Services'],
        'repository' => ['app/Repositories', 'App\\Repositories'],
        'trait' => ['app/Traits', 'App\\Traits'],
        'view' => ['resources/views', ''],
        'route' => ['routes', ''],
        'lang' => ['lang', ''],
        'config' => ['config', ''],
        'migration' => ['database/migrations', 'Database\\Migrations'],
        'seeder' => ['database/seeders', 'Database\\Seeders'],
        'factory' => ['database/factories', 'Database\\Factories'],
        'blade' => ['resources/views', ''],
    ],
    
    // ディレクトリ名をStudlyCaseにするファイルカテゴリ
    'studly_case_categories' => [
        'controller', 'model', 'provider', 'service', 'repository', 'middleware', 'request', 'listener', 'event', 'job', 
        'mail', 'notification', 'policy', 'rule'
    ],
];
