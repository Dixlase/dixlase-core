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

namespace App\Providers;

use App\Helpers\PluginHelper;
use App\Models\Plugin;
use App\Traits\PluginLoaderTrait;
use Illuminate\Console\Application as Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class PluginServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;

    /**
     * 登録されたプラグインコマンドを保持
     */
    protected array $pluginCommands = [];

    /**
     * Register services.
     */
    public function register(): void
    {
        // プラグインのServiceProviderを登録（設定読み込みのため）
        $this->registerPluginServiceProviders();
    }

    /**
     * プラグインのServiceProviderを登録
     * インストール済み・有効化済みのプラグインのみ登録
     */
    protected function registerPluginServiceProviders(): void
    {
        // .envファイルが存在しない場合やインストールされていない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        $pluginsPath = base_path('plugins');
        
        if (!File::isDirectory($pluginsPath)) {
            return;
        }

        // 有効化されたプラグインのリストを取得（キャッシュファイルから）
        $enabledPlugins = $this->getEnabledPluginsFromCache();
        
        if (empty($enabledPlugins)) {
            return;
        }

        foreach ($enabledPlugins as $pluginDirectory) {
            $pluginDir = $pluginsPath . '/' . $pluginDirectory;
            
            if (!File::isDirectory($pluginDir)) {
                continue;
            }
            
            // dixlase.json または plugin.json からプロバイダーを読み込む
            $manifestPath = $pluginDir . '/dixlase.json';
            if (!File::exists($manifestPath)) {
                $manifestPath = $pluginDir . '/plugin.json';
            }
            
            if (!File::exists($manifestPath)) {
                continue;
            }

            try {
                $manifest = json_decode(File::get($manifestPath), true);
                
                if (isset($manifest['providers']) && is_array($manifest['providers'])) {
                    foreach ($manifest['providers'] as $provider) {
                        if (class_exists($provider)) {
                            try {
                                $this->app->register($provider);
                            } catch (\Exception $e) {
                                // Provider registration failed, skip
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Failed to load plugin manifest
            }
        }
    }

    /**
     * キャッシュファイルから有効化されたプラグインのリストを取得
     * register()フェーズではDBにアクセスできないため、キャッシュがない場合は空配列を返す
     * 
     * @return array プラグインディレクトリ名の配列
     */
    protected function getEnabledPluginsFromCache(): array
    {
        $cachePath = storage_path('framework/cache/enabled_plugins.php');
        
        if (File::exists($cachePath)) {
            try {
                $cached = require $cachePath;
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (\Exception $e) {
                // キャッシュ読み込みエラーは無視
            }
        }
        
        // キャッシュがない場合は空配列を返す
        // register()フェーズではDBにアクセスできないため
        // boot()フェーズでキャッシュが作成される
        return [];
    }

    /**
     * 有効化されたプラグインのキャッシュを更新（コレクションから）
     * 
     * @param \Illuminate\Support\Collection $enabledPlugins 有効化されたプラグインのコレクション
     */
    protected function updateEnabledPluginsCache($enabledPlugins): void
    {
        try {
            $directories = $enabledPlugins->pluck('directory')->toArray();
            
            // キャッシュファイルに保存
            $cachePath = storage_path('framework/cache/enabled_plugins.php');
            $cacheDir = dirname($cachePath);
            
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            
            $content = "<?php\n\n// Generated at: " . now()->toDateTimeString() . "\n\nreturn " . var_export($directories, true) . ";\n";
            File::put($cachePath, $content);
        } catch (\Exception $e) {
            // Failed to update cache
        }
    }

    /**
     * 有効化されたプラグインのキャッシュを更新（DBから取得）
     * 
     * @return array プラグインディレクトリ名の配列
     */
    public function refreshEnabledPluginsCache(): array
    {
        try {
            // DBにアクセスできるか確認
            if (!\Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return [];
            }
            
            $enabledPlugins = Plugin::enabled()->pluck('directory')->toArray();
            
            // キャッシュファイルに保存
            $cachePath = storage_path('framework/cache/enabled_plugins.php');
            $cacheDir = dirname($cachePath);
            
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            
            $content = "<?php\n\n// Generated at: " . now()->toDateTimeString() . "\n\nreturn " . var_export($enabledPlugins, true) . ";\n";
            File::put($cachePath, $content);
            
            return $enabledPlugins;
        } catch (\Exception $e) {
            // Failed to refresh cache
        }
        return [];
    }

    /**
     * 有効化されたプラグインのキャッシュをクリア
     */
    public static function clearEnabledPluginsCache(): void
    {
        $cachePath = storage_path('framework/cache/enabled_plugins.php');
        
        if (File::exists($cachePath)) {
            File::delete($cachePath);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // .envファイルが存在しない場合やデータベース接続ができない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        $enabledPlugins = collect();

        try {
            // Only proceed if the plugins table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return;
            }

            // Get all enabled plugins
            $enabledPlugins = Plugin::enabled()->get();

            // キャッシュファイルを更新（次回のregister()フェーズで使用）
            $this->updateEnabledPluginsCache($enabledPlugins);

            foreach ($enabledPlugins as $plugin) {
                $pluginPath = base_path("plugins/{$plugin->directory}");
                
                // Load config files
                try {
                    $this->loadPluginConfigs($plugin, $pluginPath);
                } catch (\Exception $e) {
                    Log::error('Error loading plugin config', [
                        'plugin' => $plugin->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                
                // Load language files
                try {
                    $this->loadPluginLanguages($plugin, $pluginPath);
                } catch (\Exception $e) {
                    Log::error('Error loading plugin languages', [
                        'plugin' => $plugin->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to load plugin configurations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        
        // プラグインのAPIルートを読み込む
        PluginHelper::loadEnabledApiRoutes();
        
        // プラグインのルートを読み込む
        $this->loadPluginRoutes();
        
        // プラグインのコマンドを登録（CLIモードのみ、有効化されたプラグインのみ）
        if ($this->app->runningInConsole()) {
            $this->registerPluginCommands($enabledPlugins);
        }
    }

    /**
     * Load plugin configuration files
     */
    protected function loadPluginConfigs(Plugin $plugin, string $pluginPath): void
    {
        $configPath = "{$pluginPath}/config";
        
        if (!File::isDirectory($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $file) {
            if ($file->getExtension() === 'php') {
                $key = $file->getBasename('.php');
                $config = require $file->getPathname();
                
                // Set the config with plugin namespace
                Config::set("plugins.{$plugin->slug}.{$key}", $config);
                
                // Also make the config available directly under the plugin's slug
                Config::set("{$plugin->slug}.{$key}", $config);
            }
        }
    }

    /**
     * Load plugin language files
     */
    protected function loadPluginLanguages(Plugin $plugin, string $pluginPath): void
    {
        $langPath = "{$pluginPath}/lang";
        
        if (!File::isDirectory($langPath)) {
            return;
        }

        // Get all locale directories
        $locales = File::directories($langPath);
        
        // Add the entire language directory as a namespace
        Lang::addNamespace($plugin->slug, $langPath);
    }

    /**
     * Load plugin routes
     */
    protected function loadPluginRoutes(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('plugins')) {
            return;
        }

        // Get all enabled plugins
        $enabledPlugins = Plugin::enabled()->get();

        foreach ($enabledPlugins as $plugin) {
            $pluginPath = base_path("plugins/{$plugin->directory}");
            
            // Load web routes
            $webRoutePath = "{$pluginPath}/routes/web.php";
            if (File::exists($webRoutePath)) {
                \Route::middleware('web')->group($webRoutePath);
            }
            
            // DixlaseUsersは独自のServiceProviderでルートを登録するためスキップ
            if ($plugin->directory === 'DixlaseUsers') {
                continue;
            }
            
            // Load admin routes within the admin route group
            $adminRoutePath = "{$pluginPath}/routes/admin.php";
            if (File::exists($adminRoutePath)) {
                Log::info("[PluginServiceProvider] Loading admin routes", [
                    'plugin' => $plugin->directory,
                    'path' => $adminRoutePath
                ]);
                
                // Get admin URL from helper
                $adminUrl = \App\Helpers\AdminHelper::getAdminUrl();
                
                // Load admin routes - プラグイン側でルート名を完全に制御
                // ルートグループスタックをリセットしてからルートを登録
                $router = app('router');
                
                // 現在のグループスタックを保存
                $originalGroupStack = $router->getGroupStack();
                
                // グループスタックをリセット（リフレクションを使用）
                $reflection = new \ReflectionClass($router);
                $property = $reflection->getProperty('groupStack');
                $property->setAccessible(true);
                $property->setValue($router, []);
                
                // ルートを登録
                $router->group([
                    'prefix' => $adminUrl,
                    'middleware' => ['web', 'admin.ip', 'auth:member', 'verified', 'log.admin.activity'],
                ], function () use ($adminRoutePath, $plugin) {
                    Log::info("[PluginServiceProvider] Including admin route file", [
                        'plugin' => $plugin->directory,
                        'file' => $adminRoutePath
                    ]);
                    include $adminRoutePath;
                });
                
                // グループスタックを復元
                $property->setValue($router, $originalGroupStack);
            }
        }
    }

    /**
     * プラグインのコマンドを登録
     * インストール済み・有効化済みのプラグインのみコマンドを登録
     * 
     * @param \Illuminate\Support\Collection|null $enabledPlugins 有効化されたプラグインのコレクション
     */
    protected function registerPluginCommands($enabledPlugins = null): void
    {
        if ($enabledPlugins === null || $enabledPlugins->isEmpty()) {
            return;
        }

        try {
            foreach ($enabledPlugins as $plugin) {
                $pluginPath = base_path("plugins/{$plugin->directory}");
                $commandsPath = $pluginPath . '/app/Console/Commands';
                
                if (!File::isDirectory($commandsPath)) {
                    continue;
                }

                // コマンドファイルをスキャン
                $commandFiles = File::files($commandsPath);
                
                foreach ($commandFiles as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $className = $file->getBasename('.php');
                    $fullClassName = "Plugins\\{$plugin->directory}\\App\\Console\\Commands\\{$className}";
                    
                    // クラスが存在し、Commandクラスを継承しているか確認
                    if (class_exists($fullClassName) && is_subclass_of($fullClassName, \Illuminate\Console\Command::class)) {
                        $this->pluginCommands[] = $fullClassName;
                    }
                }
            }

            // コマンドを登録
            if (!empty($this->pluginCommands)) {
                $this->commands($this->pluginCommands);
            }
        } catch (\Exception $e) {
            // Failed to register plugin commands
        }
    }

    /**
     * 登録されたプラグインコマンドを取得
     */
    public function getPluginCommands(): array
    {
        return $this->pluginCommands;
    }

}

