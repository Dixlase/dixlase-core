<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThemeInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:info 
                            {theme : The theme name to show information}
                            {--json : Output as JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed information about a theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = Str::studly($this->argument('theme'));
        $themePath = base_path("themes/{$themeName}");
        $outputJson = $this->option('json');

        // テーマディレクトリの存在確認
        if (!File::isDirectory($themePath)) {
            $this->error("❌ Theme directory not found: {$themePath}");
            return 1;
        }

        // 情報を収集
        $info = $this->collectThemeInfo($themeName, $themePath);

        // 出力形式に応じて表示
        if ($outputJson) {
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayThemeInfo($themeName, $info);
        }

        return 0;
    }

    /**
     * テーマ情報を収集
     */
    protected function collectThemeInfo($themeName, $themePath)
    {
        $info = [
            'basic' => $this->getBasicInfo($themeName, $themePath),
            'theme_json' => $this->getThemeJsonInfo($themePath),
            'composer' => $this->getComposerInfo($themePath),
            'license' => $this->getLicenseInfo($themePath),
            'database' => $this->getDatabaseInfo($themeName, $themePath),
            'routes' => $this->getRoutesInfo($themeName, $themePath),
            'files' => $this->getFilesInfo($themePath),
            'status' => $this->getThemeStatus($themeName),
        ];

        return $info;
    }

    /**
     * 基本情報を取得
     */
    protected function getBasicInfo($themeName, $themePath)
    {
        $readmePath = "{$themePath}/README.md";
        $readme = File::exists($readmePath) ? 'Yes' : 'No';

        return [
            'name' => $themeName,
            'path' => $themePath,
            'readme' => $readme,
        ];
    }

    /**
     * theme.json情報を取得
     */
    protected function getThemeJsonInfo($themePath)
    {
        $themeJsonPath = "{$themePath}/theme.json";

        if (!File::exists($themeJsonPath)) {
            return ['exists' => false];
        }

        $themeJson = json_decode(File::get($themeJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['exists' => true, 'valid' => false, 'error' => json_last_error_msg()];
        }

        return [
            'exists' => true,
            'valid' => true,
            'name' => $themeJson['name'] ?? 'N/A',
            'description' => $themeJson['description'] ?? 'N/A',
            'version' => $themeJson['version'] ?? 'N/A',
            'author' => $themeJson['author'] ?? 'N/A',
            'license' => $themeJson['license'] ?? 'N/A',
        ];
    }

    /**
     * composer.json情報を取得
     */
    protected function getComposerInfo($themePath)
    {
        $composerPath = "{$themePath}/composer.json";

        if (!File::exists($composerPath)) {
            return ['exists' => false];
        }

        $composer = json_decode(File::get($composerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['exists' => true, 'valid' => false, 'error' => json_last_error_msg()];
        }

        return [
            'exists' => true,
            'valid' => true,
            'name' => $composer['name'] ?? 'N/A',
            'description' => $composer['description'] ?? 'N/A',
            'type' => $composer['type'] ?? 'N/A',
            'version' => $composer['version'] ?? 'N/A',
            'license' => $composer['license'] ?? 'N/A',
            'authors' => $composer['authors'] ?? [],
        ];
    }

    /**
     * ライセンス情報を取得
     */
    protected function getLicenseInfo($themePath)
    {
        $licenseJsonPath = "{$themePath}/license-info.json";

        if (!File::exists($licenseJsonPath)) {
            return ['exists' => false];
        }

        $licenseInfo = json_decode(File::get($licenseJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['exists' => true, 'valid' => false];
        }

        return [
            'exists' => true,
            'valid' => true,
            'license_type' => $licenseInfo['license_type'] ?? 'N/A',
            'copyright_holder' => $licenseInfo['copyright_holder'] ?? 'N/A',
            'copyright_year' => $licenseInfo['copyright_year'] ?? 'N/A',
        ];
    }

    /**
     * データベース情報を取得
     */
    protected function getDatabaseInfo($themeName, $themePath)
    {
        $migrationsPath = "{$themePath}/database/migrations";
        $seedersPath = "{$themePath}/database/seeders";

        $migrations = File::isDirectory($migrationsPath) ? count(File::files($migrationsPath)) : 0;
        $seeders = File::isDirectory($seedersPath) ? count(File::files($seedersPath)) : 0;

        // マイグレーション状態を確認
        $migrationStatus = $this->getMigrationStatus($themeName);

        return [
            'migrations' => [
                'count' => $migrations,
                'status' => $migrationStatus,
            ],
            'seeders' => $seeders,
        ];
    }

    /**
     * マイグレーション状態を取得
     */
    protected function getMigrationStatus($themeName)
    {
        try {
            $theme = DB::table('theme_settings')->where('directory', $themeName)->first();
            
            if (!$theme) {
                return 'Not installed';
            }

            return $theme->is_migrated ? 'Migrated' : 'Not migrated';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * ルート情報を取得
     */
    protected function getRoutesInfo($themeName, $themePath)
    {
        $routesPath = "{$themePath}/routes";
        $routes = [];

        if (File::isDirectory($routesPath)) {
            $routeFiles = ['web.php', 'admin.php'];
            
            foreach ($routeFiles as $file) {
                $filePath = "{$routesPath}/{$file}";
                if (File::exists($filePath)) {
                    $routes[$file] = [
                        'exists' => true,
                        'size' => File::size($filePath),
                    ];
                }
            }
        }

        // 登録されているルート数を取得（概算）
        $registeredRoutes = $this->countThemeRoutes($themeName);

        return [
            'files' => $routes,
            'registered_count' => $registeredRoutes,
        ];
    }

    /**
     * テーマのルート数をカウント
     */
    protected function countThemeRoutes($themeName)
    {
        $count = 0;
        $namespace = "Themes\\{$themeName}\\";

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();
            if (isset($action['controller'])) {
                if (Str::startsWith($action['controller'], $namespace)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * ファイル情報を取得
     */
    protected function getFilesInfo($themePath)
    {
        $info = [
            'controllers' => $this->countFiles("{$themePath}/app/Http/Controllers"),
            'models' => $this->countFiles("{$themePath}/app/Models"),
            'views' => $this->countFiles("{$themePath}/resources/views"),
            'providers' => $this->countFiles("{$themePath}/app/Providers"),
        ];

        return $info;
    }

    /**
     * ディレクトリ内のファイル数をカウント
     */
    protected function countFiles($path)
    {
        if (!File::isDirectory($path)) {
            return 0;
        }

        return count(File::allFiles($path));
    }

    /**
     * テーマのステータスを取得
     */
    protected function getThemeStatus($themeName)
    {
        try {
            $theme = DB::table('theme_settings')->where('directory', $themeName)->first();

            if (!$theme) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'migrated' => false,
                ];
            }

            return [
                'installed' => !is_null($theme->installed_at),
                'enabled' => !is_null($theme->enabled_at),
                'migrated' => $theme->is_migrated ?? false,
                'installed_at' => $theme->installed_at ?? 'N/A',
            ];
        } catch (\Exception $e) {
            return [
                'installed' => false,
                'enabled' => false,
                'migrated' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * テーマ情報を表示
     */
    protected function displayThemeInfo($themeName, $info)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("🎨 Theme Information: {$themeName}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // 基本情報
        $this->line('<fg=cyan>📋 Basic Information</>');
        $this->line("  Name: {$info['basic']['name']}");
        $this->line("  Path: {$info['basic']['path']}");
        $this->line("  README: {$info['basic']['readme']}");
        $this->newLine();

        // theme.json情報
        $this->line('<fg=cyan>🎨 Theme Configuration</>');
        if ($info['theme_json']['exists']) {
            if ($info['theme_json']['valid']) {
                $this->line("  Name: {$info['theme_json']['name']}");
                $this->line("  Description: {$info['theme_json']['description']}");
                $this->line("  Version: {$info['theme_json']['version']}");
                $this->line("  Author: {$info['theme_json']['author']}");
                $this->line("  License: {$info['theme_json']['license']}");
            } else {
                $this->line("  <fg=red>Invalid JSON: {$info['theme_json']['error']}</>");
            }
        } else {
            $this->line("  <fg=yellow>theme.json not found</>");
        }
        $this->newLine();

        // Composer情報
        $this->line('<fg=cyan>📦 Composer Information</>');
        if ($info['composer']['exists']) {
            if ($info['composer']['valid']) {
                $this->line("  Package: {$info['composer']['name']}");
                $this->line("  Description: {$info['composer']['description']}");
                $this->line("  Type: {$info['composer']['type']}");
                $this->line("  Version: {$info['composer']['version']}");
                $this->line("  License: {$info['composer']['license']}");
                
                if (!empty($info['composer']['authors'])) {
                    $this->line("  Authors:");
                    foreach ($info['composer']['authors'] as $author) {
                        $name = $author['name'] ?? 'N/A';
                        $email = $author['email'] ?? '';
                        $this->line("    - {$name}" . ($email ? " <{$email}>" : ''));
                    }
                }
            } else {
                $this->line("  <fg=red>Invalid JSON: {$info['composer']['error']}</>");
            }
        } else {
            $this->line("  <fg=yellow>composer.json not found</>");
        }
        $this->newLine();

        // ライセンス情報
        $this->line('<fg=cyan>📄 License Information</>');
        if ($info['license']['exists']) {
            if ($info['license']['valid']) {
                $this->line("  License Type: {$info['license']['license_type']}");
                $this->line("  Copyright Holder: {$info['license']['copyright_holder']}");
                $this->line("  Copyright Year: {$info['license']['copyright_year']}");
            } else {
                $this->line("  <fg=yellow>Invalid license-info.json</>");
            }
        } else {
            $this->line("  <fg=yellow>license-info.json not found</>");
        }
        $this->newLine();

        // ステータス
        $this->line('<fg=cyan>🎨 Theme Status</>');
        $this->line("  Installed: " . ($info['status']['installed'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        if ($info['status']['installed']) {
            $this->line("  Enabled: " . ($info['status']['enabled'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
            $this->line("  Migrated: " . ($info['status']['migrated'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
            $this->line("  Installed At: {$info['status']['installed_at']}");
        }
        $this->newLine();

        // データベース情報
        $this->line('<fg=cyan>🗄️  Database</>');
        $this->line("  Migrations: {$info['database']['migrations']['count']} files ({$info['database']['migrations']['status']})");
        $this->line("  Seeders: {$info['database']['seeders']} files");
        $this->newLine();

        // ルート情報
        $this->line('<fg=cyan>🛣️  Routes</>');
        if (!empty($info['routes']['files'])) {
            foreach ($info['routes']['files'] as $file => $data) {
                $size = number_format($data['size'] / 1024, 2);
                $this->line("  {$file}: {$size} KB");
            }
            $this->line("  Registered Routes: {$info['routes']['registered_count']}");
        } else {
            $this->line("  <fg=yellow>No route files found</>");
        }
        $this->newLine();

        // ファイル統計
        $this->line('<fg=cyan>📁 Files Statistics</>');
        $this->line("  Controllers: {$info['files']['controllers']}");
        $this->line("  Models: {$info['files']['models']}");
        $this->line("  Views: {$info['files']['views']}");
        $this->line("  Providers: {$info['files']['providers']}");
        $this->newLine();

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
