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

namespace App\Helpers;

use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class ThemeHelper
{
    /**
     * Get the active theme
     */
    public static function getActiveTheme(): ?Theme
    {
        try {
            if (! Schema::hasTable('themes') || ! Schema::hasTable('theme_settings')) {
                return null;
            }

            // Get the active theme ID from the theme_settings table
            $themeSetting = \DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->first();

            if (! $themeSetting || ! $themeSetting->value) {
                return null;
            }

            return Theme::find((int) $themeSetting->value);
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to get active theme', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the directory path of the active theme
     */
    public static function getActiveThemePath(): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        return base_path("themes/{$theme->directory}");
    }

    /**
     * Load the admin panel routes for the active theme
     *
     * This method is expected to be called within the authenticated route group in routes/admin.php.
     */
    public static function loadEnabledThemeAdminRoutes(): void
    {
        try {
            $activeTheme = self::getActiveTheme();

            if (! $activeTheme) {
                return;
            }

            $adminRoutePath = base_path("themes/{$activeTheme->directory}/routes/admin.php");

            if (File::exists($adminRoutePath)) {
                include $adminRoutePath;
            }
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to load theme admin routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load the web routes for the active theme
     */
    public static function loadEnabledThemeWebRoutes(): void
    {
        try {
            $activeTheme = self::getActiveTheme();

            if (! $activeTheme) {
                return;
            }

            $webRoutePath = base_path("themes/{$activeTheme->directory}/routes/web.php");

            if (File::exists($webRoutePath)) {
                include $webRoutePath;
            }
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to load theme web routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the asset path for the theme
     */
    public static function asset(string $path = ''): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        $basePath = "themes/{$theme->directory}/public";

        return $path ? "{$basePath}/{$path}" : $basePath;
    }

    /**
     * Get the view path for the theme
     */
    public static function view(string $view): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        return "themes::{$view}";
    }
}
