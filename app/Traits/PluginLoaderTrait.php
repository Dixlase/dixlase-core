<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Traits;

use App\Contracts\Admin\AdminNavigationManagerInterface;
use App\Contracts\Repositories\PluginRepositoryInterface;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Plugin resource loading mechanism
 */
trait PluginLoaderTrait
{
    use ConfigLoaderTrait;

    /**
     * Lazy resolution of PluginRepositoryInterface
     */
    protected function resolvePluginRepository(): PluginRepositoryInterface
    {
        return app(PluginRepositoryInterface::class);
    }

    /**
     * Lazy resolution of AdminNavigationManagerInterface
     */
    protected function resolveAdminNavigationManager(): AdminNavigationManagerInterface
    {
        return app(AdminNavigationManagerInterface::class);
    }

    /**
     * Load enabled plugins
     */
    public function loadEnabledPlugins()
    {
        // Check directly from command line arguments
        $pluginManagementFlag = $this->getUninstallingPluginFromArgs();

        // Skip plugin loader during plugin management command execution
        if ($pluginManagementFlag === 'PLUGIN_MANAGEMENT_COMMAND') {
            return;
        }

        // Also skip during app:uninstall command execution
        if (isset($_SERVER['argv']) && in_array('app:uninstall', $_SERVER['argv'])) {
            return;
        }

        // Get enabled plugins via repository (including table existence check)
        $plugins = $this->resolvePluginRepository()->getEnabled();

        foreach ($plugins as $plugin) {
            $pluginName = $plugin->name;
            $pluginDirectory = $plugin->directory;
            $pluginSlug = $plugin->slug;
            $pluginPath = base_path('plugins/'.$pluginDirectory);
            $customPluginPath = base_path('custom/plugins/'.$pluginDirectory);

            // Load plugin files
            $this->loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug);

            // Register service providers (after loading plugin files)
            $providerClass = $this->resolvePluginServiceProvider($pluginName, $pluginDirectory);

            if ($providerClass) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Get plugin management target from command line arguments
     */
    private function getUninstallingPluginFromArgs(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        // Skip plugin loader for plugin management commands
        if (count($argv) >= 2) {
            $pluginCommands = [
                'plugin:install',
                'plugin:uninstall',
                'plugin:enable',
                'plugin:disable',
            ];
            if (in_array($argv[1], $pluginCommands)) {
                return 'PLUGIN_MANAGEMENT_COMMAND';
            }
        }

        return null;
    }

    /**
     * Load plugin resources
     */
    protected function loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug = null)
    {
        $fileTypes = config('app.file_types', []);

        foreach ($fileTypes as $type => $settings) {
            $coreSubPath = "{$pluginPath}/{$settings['path']}";
            $customSubPath = "{$customPluginPath}/{$settings['path']}";

            $this->loadFilesByType($type, $coreSubPath, $customSubPath, $pluginSlug);
        }
    }

    /**
     * Load processing by file type
     *
     * Note: routes are excluded as they are loaded by PluginServiceProvider::loadPluginRoutes()
     */
    protected function loadFilesByType($type, $defaultPath, $customPath, $pluginSlug)
    {
        switch ($type) {
            case 'config':
                $this->loadPluginConfigs($defaultPath, $customPath, $pluginSlug);
                break;
            case 'routes':
                // Exclude routes as they are loaded by PluginServiceProvider::loadPluginRoutes()
                // $this->loadPluginRoutes($customPath, $defaultPath);
                break;
            case 'lang':
                $this->loadPluginTranslations($customPath, $defaultPath, $pluginSlug);
                break;
            case 'views':
                $this->loadPluginViews($customPath, $defaultPath, $pluginSlug);
                break;
            case 'migrations':
                $this->loadPluginMigrations($customPath, $defaultPath);
                break;
            default:
                $this->loadCustomFiles($type, $defaultPath, $customPath, $pluginSlug);
        }
    }

    /**
     * Load config
     */
    protected function loadPluginConfigs($corePath, $customPath, $pluginSlug)
    {
        // Store plugin settings in individual namespace
        $pluginConfigs = $this->loadConfigFiles($corePath);
        $customConfigs = $this->loadConfigFiles($customPath);

        foreach ($customConfigs as $key => $customConfig) {
            if (isset($pluginConfigs[$key])) {
                $pluginConfigs[$key] = array_merge_recursive($pluginConfigs[$key], $customConfig);
            } else {
                $pluginConfigs[$key] = $customConfig;
            }
        }

        // Register with prefix like `users-plugin.auth`
        foreach ($pluginConfigs as $key => $value) {
            config(["{$pluginSlug}.{$key}" => $value]);
        }

        // New structure: load config/admin/navigation.php with priority
        $adminNavConfigFile = $corePath.'/admin/navigation.php';
        if (file_exists($adminNavConfigFile)) {
            $this->mergeAdminNavigationFile($adminNavConfigFile);
        } else {
            // Legacy structure: merge navigation if admin.php file exists
            $adminConfigFile = $corePath.'/admin.php';
            if (file_exists($adminConfigFile)) {
                $this->mergeAdminNavConfig($adminConfigFile);
            }
        }

        // Also check for custom admin/navigation.php file
        $customAdminNavConfigFile = $customPath.'/admin/navigation.php';
        if (file_exists($customAdminNavConfigFile)) {
            $this->mergeAdminNavigationFile($customAdminNavConfigFile);
        } else {
            // Also check for custom admin.php file
            $customAdminConfigFile = $customPath.'/admin.php';
            if (file_exists($customAdminConfigFile)) {
                $this->mergeAdminNavConfig($customAdminConfigFile);
            }
        }
    }

    /**
     * Load routes
     *
     * Note: admin.php is excluded because it's loaded in PluginServiceProvider::loadPluginRoutes()
     */
    protected function loadPluginRoutes($customPath, $defaultPath)
    {
        $paths = array_filter([$customPath, $defaultPath]);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (glob("{$path}/*.php") as $routeFile) {
                    // Exclude admin.php as it's loaded in PluginServiceProvider
                    if (basename($routeFile) === 'admin.php') {
                        continue;
                    }
                    Route::middleware('web')->group($routeFile);
                }
            }
        }
    }

    /**
     * Load views
     */
    protected function loadPluginViews($customPath, $defaultPath, $namespace)
    {
        if (is_dir($customPath)) {
            View::addNamespace($namespace, $customPath);
        }

        if (is_dir($defaultPath)) {
            View::addNamespace($namespace, $defaultPath);
        }
    }

    /**
     * Load language files
     */
    protected function loadPluginTranslations($customPath, $corePath, $namespace)
    {
        $paths = array_filter([$customPath, $corePath]);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $namespace);
            }
        }

        // $translations = Lang::getLoader()->load(app()->getLocale(), 'admin');
    }

    /**
     * Load migration files
     */
    protected function loadPluginMigrations($customPath, $corePath)
    {
        $paths = array_filter([$customPath, $corePath]);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }

    /**
     * Resolve the plugin's service provider
     */
    protected function resolvePluginServiceProvider(string $pluginName, string $pluginDirectory): ?string
    {
        $defaultProvider = "Plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginDirectory}ServiceProvider";
        $customProvider = "custom\\plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginDirectory}ServiceProvider";

        if (class_exists($customProvider)) {
            return $customProvider;
        } elseif (class_exists($defaultProvider)) {
            return $defaultProvider;
        }

        return null;
    }

    /**
     * Get plugin meta information
     */
    protected function getPluginMetadata($pluginDirectory)
    {
        $composerJsonPath = $pluginDirectory.'/composer.json';

        if (file_exists($composerJsonPath)) {
            return json_decode(file_get_contents($composerJsonPath), true);
        }
    }

    /**
     * Get all plugin meta information
     */
    public function getAllPluginsMetadata()
    {
        $pluginDirectories = glob(base_path('plugins/*'), GLOB_ONLYDIR);
        $metadata = [];

        foreach ($pluginDirectories as $pluginDirectory) {
            $meta = $this->getPluginMetadata($pluginDirectory);
            if ($meta) {
                $metadata[] = $meta;
            }
        }

        return $metadata;
    }

    /**
     * Merge arbitrary plugin settings
     *
     * @param  string  $configFile  Path to the plugin's config file
     * @param  string  $configKey  Key to store in config() (e.g., 'auth', 'admin.nav')
     */
    public function mergePluginConfig($configFile, $configKey)
    {
        if (! file_exists($configFile)) {
            return; // Skip if settings file does not exist
        }

        $pluginConfig = require $configFile;

        if (! is_array($pluginConfig)) {
            return; // Skip if settings file is invalid
        }

        // Get existing settings
        $existingConfig = config($configKey, []);

        // Merge using custom recursive merge function
        $mergedConfig = $this->recursiveArrayMergeOverwrite($existingConfig, $pluginConfig);

        // Apply merged settings
        config([$configKey => $mergedConfig]);
    }

    /**
     * Merge new structure navigation file (config/admin/navigation.php)
     *
     * @param  string  $configFile  Plugin navigation settings file
     */
    public function mergeAdminNavigationFile($configFile)
    {
        $this->resolveAdminNavigationManager()->mergeNavigationFile($configFile);
    }

    /**
     * Merge admin panel navigation (`admin.nav`) (for old structure)
     *
     * @param  string  $configFile  Plugin navigation settings file
     */
    public function mergeAdminNavConfig($configFile)
    {
        $this->resolveAdminNavigationManager()->mergeNavConfig($configFile);
    }

    /**
     * Recursively merge arrays (overwrite if same key exists)
     *
     * @param  array  $base  Original settings
     * @param  array  $override  Additional settings
     * @return array Merged array
     */
    protected function recursiveArrayMergeOverwrite(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // Recursively merge if both are arrays
                $base[$key] = $this->recursiveArrayMergeOverwrite($base[$key], $value);
            } else {
                // Overwrite if not an array
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
