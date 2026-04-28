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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Contracts\Admin\AdminNavigationManagerInterface;
use App\Models\Theme;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        try {
            // Only proceed if the themes table exists
            if (! \Illuminate\Support\Facades\Schema::hasTable('themes') ||
                ! \Illuminate\Support\Facades\Schema::hasTable('theme_settings')) {
                return;
            }

            // Get the active theme from theme_settings
            $themeSetting = \DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->first();

            if (! $themeSetting || ! $themeSetting->value) {
                Log::warning('No active theme configured in theme_settings');

                return;
            }

            $activeTheme = Theme::find($themeSetting->value);

            if ($activeTheme) {
                $themePath = base_path("themes/{$activeTheme->directory}");

                // Load config files
                try {
                    $this->loadThemeConfigs($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme config', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Load language files
                try {
                    $this->loadThemeLanguages($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme languages', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Load views
                try {
                    $this->loadThemeViews($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error loading theme views', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // テーマのServiceProviderを登録
                try {
                    $this->registerThemeServiceProviders($activeTheme, $themePath);
                } catch (\Exception $e) {
                    Log::error('Error registering theme service providers', [
                        'theme' => $activeTheme->directory,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to load theme configurations: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Load theme routes
        $this->loadThemeRoutes();
    }

    /**
     * テーマのServiceProviderを登録
     * theme.jsonにprovidersが定義されていれば登録する
     */
    protected function registerThemeServiceProviders(Theme $theme, string $themePath): void
    {
        $themeJsonPath = "{$themePath}/theme.json";

        if (! File::exists($themeJsonPath)) {
            return;
        }

        $themeJson = json_decode(File::get($themeJsonPath), true);

        if (! isset($themeJson['providers']) || ! is_array($themeJson['providers'])) {
            return;
        }

        foreach ($themeJson['providers'] as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * テーマの設定ファイルを読み込む
     */
    protected function loadThemeConfigs(Theme $theme, string $themePath): void
    {
        $configPath = "{$themePath}/config";

        if (! File::isDirectory($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $file) {
            if ($file->getExtension() === 'php') {
                $key = $file->getBasename('.php');
                $config = require $file->getPathname();

                Config::set("themes.{$theme->slug}.{$key}", $config);
                Config::set("{$theme->slug}.{$key}", $config);
            }
        }

        // config/admin/navigation.php が存在すればナビゲーションにマージ
        $navConfigFile = "{$configPath}/admin/navigation.php";
        if (File::exists($navConfigFile)) {
            app(AdminNavigationManagerInterface::class)->mergeNavigationFile($navConfigFile);
        }
    }

    /**
     * Load theme language files
     */
    protected function loadThemeLanguages(Theme $theme, string $themePath): void
    {
        $langPath = "{$themePath}/lang";

        if (! File::isDirectory($langPath)) {
            Log::warning('Theme language directory not found', [
                'theme' => $theme->directory,
                'path' => $langPath,
            ]);

            return;
        }

        // Add the language directory with 'themes' namespace
        // This allows using __('themes::theme.key') or __('themes::admin.settings.key')
        $this->loadTranslationsFrom($langPath, 'themes');
    }

    /**
     * Load theme views
     */
    protected function loadThemeViews(Theme $theme, string $themePath): void
    {
        $viewsPath = "{$themePath}/resources/views";

        if (! File::isDirectory($viewsPath)) {
            return;
        }

        // Register theme views with namespace
        $this->loadViewsFrom($viewsPath, 'themes');
    }

    /**
     * Load theme routes
     */
    protected function loadThemeRoutes(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('themes') ||
            ! \Illuminate\Support\Facades\Schema::hasTable('theme_settings')) {
            return;
        }

        // Get the active theme
        $themeSetting = \DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();

        if (! $themeSetting || ! $themeSetting->value) {
            return;
        }

        $activeTheme = Theme::find($themeSetting->value);
        if (! $activeTheme) {
            return;
        }

        $themePath = base_path("themes/{$activeTheme->directory}");

        // Load web routes
        $webRoutePath = "{$themePath}/routes/web.php";
        if (File::exists($webRoutePath)) {
            include $webRoutePath;
        }

        // Load admin routes within the admin route group
        $adminRoutePath = "{$themePath}/routes/admin.php";
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
