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
        try {
            // Only proceed if the plugins table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('plugins')) {
                return;
            }

            // Get all enabled plugins
            $enabledPlugins = Plugin::active()->get();

            foreach ($enabledPlugins as $plugin) {
                $pluginPath = base_path("plugins/{$plugin->directory}");
                
                // Load config files
                $this->loadPluginConfigs($plugin, $pluginPath);
                
                // Load language files
                $this->loadPluginLanguages($plugin, $pluginPath);
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to load plugin configurations: ' . $e->getMessage());
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
        
        foreach ($locales as $localePath) {
            $locale = basename($localePath);
            
            // Load PHP language files
            foreach (File::files($localePath) as $file) {
                if ($file->getExtension() === 'php') {
                    $key = $file->getBasename('.php');
                    $translations = require $file->getPathname();
                    
                    // Add translations to Laravel's translator
                    Lang::addNamespace($plugin->slug, $localePath);
                    
                    // Also make translations available under the plugin's slug
                    $fullKey = "{$plugin->slug}::{$key}";
                    Lang::addLines($translations, $locale, $fullKey);
                }
            }
            
            // Load JSON language files if any
            $jsonFile = "{$localePath}.json";
            if (File::exists($jsonFile)) {
                $translations = json_decode(File::get($jsonFile), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Lang::addJsonPath($localePath);
                }
            }
        }
    }
}
