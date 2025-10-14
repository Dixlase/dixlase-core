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

use App\Models\Theme;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class ThemeServiceProvider extends ServiceProvider
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
            // Only proceed if the themes table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('themes') || 
                !\Illuminate\Support\Facades\Schema::hasTable('theme_settings')) {
                return;
            }

            // Get the active theme from theme_settings
            $themeSetting = \DB::table('theme_settings')->first();
            if (!$themeSetting || !$themeSetting->active_theme_id) {
                Log::warning('No active theme configured in theme_settings');
                return;
            }
            
            $activeTheme = Theme::find($themeSetting->active_theme_id);

            if ($activeTheme) {
                $themePath = base_path("themes/{$activeTheme->directory}");
                
                Log::info('Processing active theme', [
                    'theme' => $activeTheme->directory,
                    'slug' => $activeTheme->slug
                ]);
                
                // Load config files
                try {
                    $this->loadThemeConfigs($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme config', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                
                // Load language files
                try {
                    $this->loadThemeLanguages($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme languages', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                // Load views
                try {
                    $this->loadThemeViews($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme views', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to load theme configurations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Load theme configuration files
     */
    protected function loadThemeConfigs(Theme $theme, string $themePath): void
    {
        $configPath = "{$themePath}/config";
        
        if (!File::isDirectory($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $file) {
            if ($file->getExtension() === 'php') {
                $key = $file->getBasename('.php');
                $config = require $file->getPathname();
                
                // Set the config with theme namespace
                Config::set("themes.{$theme->slug}.{$key}", $config);
                
                // Also make the config available directly under the theme's slug
                Config::set("{$theme->slug}.{$key}", $config);
            }
        }
    }

    /**
     * Load theme language files
     */
    protected function loadThemeLanguages(Theme $theme, string $themePath): void
    {
        $langPath = "{$themePath}/lang";
        
        if (!File::isDirectory($langPath)) {
            Log::warning('Theme language directory not found', [
                'theme' => $theme->directory,
                'path' => $langPath
            ]);
            return;
        }

        // Add the language directory with 'themes' namespace
        // This allows using __('themes::theme.key') or __('themes::admin.settings.key')
        $this->loadTranslationsFrom($langPath, 'themes');
        
        Log::info('Theme language files loaded', [
            'theme' => $theme->directory,
            'namespace' => 'themes',
            'path' => $langPath
        ]);
    }

    /**
     * Load theme views
     */
    protected function loadThemeViews(Theme $theme, string $themePath): void
    {
        $viewsPath = "{$themePath}/resources/views";
        
        if (!File::isDirectory($viewsPath)) {
            return;
        }

        // Register theme views with namespace
        $this->loadViewsFrom($viewsPath, 'themes');
        
        Log::info('Theme views loaded', [
            'theme' => $theme->directory,
            'namespace' => 'themes',
            'path' => $viewsPath
        ]);
    }
}
