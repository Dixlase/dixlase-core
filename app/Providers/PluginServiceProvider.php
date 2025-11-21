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

use App\Models\Plugin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
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

        try {
            // Only proceed if the plugins table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return;
            }

            // Get all enabled plugins
            $enabledPlugins = Plugin::enabled()->get();

            foreach ($enabledPlugins as $plugin) {
                $pluginPath = base_path("plugins/{$plugin->directory}");
                
                Log::info('Processing plugin config', ['plugin' => $plugin->directory, 'slug' => $plugin->slug]);
                
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
        
        // Load plugin routes
        $this->loadPluginRoutes();
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
                include $webRoutePath;
            }
            
            // Load admin routes within the admin route group
            $adminRoutePath = "{$pluginPath}/routes/admin.php";
            if (File::exists($adminRoutePath)) {
                // Get admin URL from helper
                $adminUrl = \App\Helpers\AdminHelper::getAdminUrl();
                
                // Load admin routes within the secure admin group
                \Route::prefix($adminUrl)->name('admin.')
                    ->middleware(['admin.ip'])
                    ->group(function () use ($adminRoutePath) {
                        \Route::middleware(['auth:member', 'verified', 'log.admin.activity'])->group(function () use ($adminRoutePath) {
                            include $adminRoutePath;
                        });
                    });
            }
        }
    }

}
