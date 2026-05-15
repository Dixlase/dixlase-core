<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;

    /**
     * Holds registered plugin commands
     */
    protected array $pluginCommands = [];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register plugin ServiceProviders (for loading settings)
        $this->registerPluginServiceProviders();
    }

    /**
     * Register plugin ServiceProviders
     * Register only installed and enabled plugins
     */
    protected function registerPluginServiceProviders(): void
    {
        // Skip if .env file does not exist or not installed
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        $pluginsPath = base_path('plugins');

        if (! File::isDirectory($pluginsPath)) {
            return;
        }

        // Get list of enabled plugins (from cache file)
        $enabledPlugins = $this->getEnabledPluginsFromCache();

        if (empty($enabledPlugins)) {
            return;
        }

        foreach ($enabledPlugins as $pluginDirectory) {
            $pluginDir = $pluginsPath.'/'.$pluginDirectory;

            if (! File::isDirectory($pluginDir)) {
                continue;
            }

            // Load provider from dixlase.json or plugin.json
            $manifestPath = $pluginDir.'/dixlase.json';
            if (! File::exists($manifestPath)) {
                $manifestPath = $pluginDir.'/plugin.json';
            }

            if (! File::exists($manifestPath)) {
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
     * Get list of enabled plugins from cache file
     * Cannot access DB during register() phase, so return empty array if no cache
     *
     * @return array Array of plugin directory names
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
                // Ignore cache loading errors
            }
        }

        // Return empty array if no cache
        // Cannot access DB during register() phase
        // Cache is created during boot() phase
        return [];
    }

    /**
     * Update cache of enabled plugins (from collection)
     *
     * @param  \Illuminate\Support\Collection  $enabledPlugins  Collection of enabled plugins
     */
    protected function updateEnabledPluginsCache($enabledPlugins): void
    {
        try {
            $directories = $enabledPlugins->pluck('directory')->toArray();

            // Save to cache file
            $cachePath = storage_path('framework/cache/enabled_plugins.php');
            $cacheDir = dirname($cachePath);

            if (! File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $content = "<?php\n\n// Generated at: ".now()->toDateTimeString()."\n\nreturn ".var_export($directories, true).";\n";
            File::put($cachePath, $content);
        } catch (\Exception $e) {
            // Failed to update cache
        }
    }

    /**
     * Update cache of enabled plugins (fetch from DB)
     *
     * @return array Array of plugin directory names
     */
    public function refreshEnabledPluginsCache(): array
    {
        try {
            // Check if DB is accessible
            if (! \Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return [];
            }

            $enabledPlugins = Plugin::enabled()->pluck('directory')->toArray();

            // Save to cache file
            $cachePath = storage_path('framework/cache/enabled_plugins.php');
            $cacheDir = dirname($cachePath);

            if (! File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $content = "<?php\n\n// Generated at: ".now()->toDateTimeString()."\n\nreturn ".var_export($enabledPlugins, true).";\n";
            File::put($cachePath, $content);

            return $enabledPlugins;
        } catch (\Exception $e) {
            // Failed to refresh cache
        }

        return [];
    }

    /**
     * Clear cache of enabled plugins
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
        // Skip if .env file does not exist or database connection is not available
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        $enabledPlugins = collect();

        try {
            // Only proceed if the plugins table exists
            if (! \Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return;
            }

            // Get all enabled plugins
            $enabledPlugins = Plugin::enabled()->get();

            // Update cache file (used in next register() phase)
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
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Load language files
                try {
                    $this->loadPluginLanguages($plugin, $pluginPath);
                } catch (\Exception $e) {
                    Log::error('Error loading plugin languages', [
                        'plugin' => $plugin->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to load plugin configurations: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Load plugin API routes
        PluginHelper::loadEnabledApiRoutes();

        // Load plugin routes
        $this->loadPluginRoutes();

        // Register plugin commands (CLI mode only, enabled plugins only)
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

        if (! File::isDirectory($configPath)) {
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

        if (! File::isDirectory($langPath)) {
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
        if (! \Illuminate\Support\Facades\Schema::hasTable('plugins')) {
            return;
        }

        // Get all enabled plugins
        $enabledPlugins = Plugin::enabled()->get();

        // Get admin URL from helper (only once)
        $adminUrl = \App\Helpers\AdminHelper::getAdminUrl();

        // Prepare router and reflection (only once)
        $router = app('router');
        $reflection = new \ReflectionClass($router);
        $groupStackProperty = $reflection->getProperty('groupStack');
        $groupStackProperty->setAccessible(true);

        foreach ($enabledPlugins as $plugin) {
            $pluginPath = base_path("plugins/{$plugin->directory}");

            // Load web routes
            $webRoutePath = "{$pluginPath}/routes/web.php";
            if (File::exists($webRoutePath)) {
                \Route::middleware('web')->group($webRoutePath);
            }

            // Load admin routes - plugin side has full control over route names
            $adminRoutePath = "{$pluginPath}/routes/admin.php";
            if (File::exists($adminRoutePath)) {
                // Save current group stack
                $originalGroupStack = $router->getGroupStack();

                // Reset group stack (avoid influence from Core's routes/admin.php)
                $groupStackProperty->setValue($router, []);

                // Register routes
                $router->group([
                    'prefix' => $adminUrl,
                    'middleware' => ['web', 'admin.ip', 'auth:member', 'verified', 'log.admin.activity'],
                ], function () use ($adminRoutePath) {
                    include $adminRoutePath;
                });

                // Restore group stack
                $groupStackProperty->setValue($router, $originalGroupStack);
            }
        }
    }

    /**
     * Register plugin commands
     * Register commands only for installed and enabled plugins
     *
     * @param  \Illuminate\Support\Collection|null  $enabledPlugins  Collection of enabled plugins
     */
    protected function registerPluginCommands($enabledPlugins = null): void
    {
        if ($enabledPlugins === null || $enabledPlugins->isEmpty()) {
            return;
        }

        try {
            foreach ($enabledPlugins as $plugin) {
                $pluginPath = base_path("plugins/{$plugin->directory}");
                $commandsPath = $pluginPath.'/app/Console/Commands';

                if (! File::isDirectory($commandsPath)) {
                    continue;
                }

                // Scan command files
                $commandFiles = File::files($commandsPath);

                foreach ($commandFiles as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $className = $file->getBasename('.php');
                    $fullClassName = "Plugins\\{$plugin->directory}\\App\\Console\\Commands\\{$className}";

                    // Check if class exists and extends Command class
                    if (class_exists($fullClassName) && is_subclass_of($fullClassName, \Illuminate\Console\Command::class)) {
                        $this->pluginCommands[] = $fullClassName;
                    }
                }
            }

            // Register command
            if (! empty($this->pluginCommands)) {
                $this->commands($this->pluginCommands);
            }
        } catch (\Exception $e) {
            // Failed to register plugin commands
        }
    }

    /**
     * Get registered plugin commands
     */
    public function getPluginCommands(): array
    {
        return $this->pluginCommands;
    }
}
