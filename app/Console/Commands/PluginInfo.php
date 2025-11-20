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
use Illuminate\Support\Str;
use App\Models\Plugin;

class PluginInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:info 
                            {plugin : The plugin name to show information}
                            {--json : Output as JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed information about a plugin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $pluginPath = base_path("plugins/{$pluginName}");
        $outputJson = $this->option('json');

        // プラグインディレクトリの存在確認
        if (!File::isDirectory($pluginPath)) {
            $this->error("❌ Plugin directory not found: {$pluginPath}");
            return 1;
        }

        // 情報を収集
        $info = $this->collectPluginInfo($pluginName, $pluginPath);

        // 出力形式に応じて表示
        if ($outputJson) {
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayPluginInfo($pluginName, $info);
        }

        return 0;
    }

    /**
     * プラグイン情報を収集
     */
    protected function collectPluginInfo($pluginName, $pluginPath)
    {
        $info = [
            'basic' => $this->getBasicInfo($pluginName, $pluginPath),
            'composer' => $this->getComposerInfo($pluginPath),
            'license' => $this->getLicenseInfo($pluginPath),
            'database' => $this->getDatabaseInfo($pluginName, $pluginPath),
            'routes' => $this->getRoutesInfo($pluginName, $pluginPath),
            'files' => $this->getFilesInfo($pluginPath),
            'status' => $this->getPluginStatus($pluginName),
        ];

        return $info;
    }

    /**
     * 基本情報を取得
     */
    protected function getBasicInfo($pluginName, $pluginPath)
    {
        $readmePath = "{$pluginPath}/README.md";
        $readme = File::exists($readmePath) ? 'Yes' : 'No';

        return [
            'name' => $pluginName,
            'path' => $pluginPath,
            'readme' => $readme,
        ];
    }

    /**
     * composer.json情報を取得
     */
    protected function getComposerInfo($pluginPath)
    {
        $composerPath = "{$pluginPath}/composer.json";

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
            'dependencies' => $composer['require'] ?? [],
            'autoload' => $composer['autoload'] ?? [],
        ];
    }

    /**
     * ライセンス情報を取得
     */
    protected function getLicenseInfo($pluginPath)
    {
        $licenseJsonPath = "{$pluginPath}/license-info.json";

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
    protected function getDatabaseInfo($pluginName, $pluginPath)
    {
        $migrationsPath = "{$pluginPath}/database/migrations";
        $seedersPath = "{$pluginPath}/database/seeders";
        $factoriesPath = "{$pluginPath}/database/factories";

        $migrations = File::isDirectory($migrationsPath) ? count(File::files($migrationsPath)) : 0;
        $seeders = File::isDirectory($seedersPath) ? count(File::files($seedersPath)) : 0;
        $factories = File::isDirectory($factoriesPath) ? count(File::files($factoriesPath)) : 0;

        // マイグレーション状態を確認
        $migrationStatus = $this->getMigrationStatus($pluginName);

        return [
            'migrations' => [
                'count' => $migrations,
                'status' => $migrationStatus,
            ],
            'seeders' => $seeders,
            'factories' => $factories,
        ];
    }

    /**
     * マイグレーション状態を取得
     */
    protected function getMigrationStatus($pluginName)
    {
        try {
            $plugin = Plugin::where('name', $pluginName)->first();
            
            if (!$plugin) {
                return 'Not installed';
            }

            return $plugin->is_migrated ? 'Migrated' : 'Not migrated';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * ルート情報を取得
     */
    protected function getRoutesInfo($pluginName, $pluginPath)
    {
        $routesPath = "{$pluginPath}/routes";
        $routes = [];

        if (File::isDirectory($routesPath)) {
            $routeFiles = ['web.php', 'api.php'];
            
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
        $registeredRoutes = $this->countPluginRoutes($pluginName);

        return [
            'files' => $routes,
            'registered_count' => $registeredRoutes,
        ];
    }

    /**
     * プラグインのルート数をカウント
     */
    protected function countPluginRoutes($pluginName)
    {
        $count = 0;
        $namespace = "Plugins\\{$pluginName}\\";

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
    protected function getFilesInfo($pluginPath)
    {
        $info = [
            'controllers' => $this->countFiles("{$pluginPath}/app/Http/Controllers"),
            'models' => $this->countFiles("{$pluginPath}/app/Models"),
            'views' => $this->countFiles("{$pluginPath}/resources/views"),
            'tests' => $this->countFiles("{$pluginPath}/tests"),
            'commands' => $this->countFiles("{$pluginPath}/app/Console/Commands"),
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
     * プラグインのステータスを取得
     */
    protected function getPluginStatus($pluginName)
    {
        try {
            $plugin = Plugin::where('name', $pluginName)->first();

            if (!$plugin) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'migrated' => false,
                ];
            }

            return [
                'installed' => !is_null($plugin->installed_at),
                'enabled' => !is_null($plugin->activated_at),
                'migrated' => $plugin->is_migrated ?? false,
                'installed_at' => $plugin->installed_at?->format('Y-m-d H:i:s') ?? 'N/A',
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
     * プラグイン情報を表示
     */
    protected function displayPluginInfo($pluginName, $info)
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📦 Plugin Information: {$pluginName}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // 基本情報
        $this->line('<fg=cyan>📋 Basic Information</>');
        $this->line("  Name: {$info['basic']['name']}");
        $this->line("  Path: {$info['basic']['path']}");
        $this->line("  README: {$info['basic']['readme']}");
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
        $this->line('<fg=cyan>🔌 Plugin Status</>');
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
        $this->line("  Factories: {$info['database']['factories']} files");
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
        $this->line("  Tests: {$info['files']['tests']}");
        $this->line("  Commands: {$info['files']['commands']}");
        $this->newLine();

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
