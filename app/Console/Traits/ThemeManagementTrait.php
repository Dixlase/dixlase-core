<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;

/**
 * Trait that provides common functionality for theme management
 */
trait ThemeManagementTrait
{
    /**
     * Get the path of the theme directory
     *
     * @param  string  $themeName  Theme name
     */
    protected function getThemePath(string $themeName): string
    {
        return base_path("themes/{$themeName}");
    }

    /**
     * Check if the theme exists
     *
     * @param  string  $themeName  Theme name
     */
    protected function themeExists(string $themeName): bool
    {
        $themePath = $this->getThemePath($themeName);

        return File::isDirectory($themePath) && File::exists("{$themePath}/theme.json");
    }

    /**
     * Get theme information
     *
     * @param  string  $themeName  Theme name
     */
    protected function getThemeInfo(string $themeName): ?array
    {
        $themePath = $this->getThemePath($themeName);
        $themeJsonPath = "{$themePath}/theme.json";

        if (! File::exists($themeJsonPath)) {
            return null;
        }

        $content = File::get($themeJsonPath);

        return json_decode($content, true);
    }

    /**
     * Get the namespace of the theme
     *
     * @param  string  $themeName  Theme name
     */
    protected function getThemeNamespace(string $themeName): string
    {
        $themeInfo = $this->getThemeInfo($themeName);

        if ($themeInfo && isset($themeInfo['namespace'])) {
            return $themeInfo['namespace'];
        }

        // Generate the default namespace
        return "Themes\\{$themeName}\\App";
    }

    /**
     * Get the table prefix of the theme
     *
     * @param  string  $themeName  Theme name
     */
    protected function getThemeTablePrefix(string $themeName): string
    {
        $themeInfo = $this->getThemeInfo($themeName);

        if ($themeInfo && isset($themeInfo['table_prefix'])) {
            return $themeInfo['table_prefix'];
        }

        // Generate the default prefix (e.g., thm_my_theme_)
        $slug = $themeInfo['slug'] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $themeName));

        return 'thm_'.str_replace('-', '_', $slug).'_';
    }

    /**
     * Get a list of available themes
     */
    protected function getAvailableThemes(): array
    {
        $themesPath = base_path('themes');

        if (! File::isDirectory($themesPath)) {
            return [];
        }

        $themes = [];
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $themeName = basename($directory);
            if ($this->themeExists($themeName)) {
                $themes[] = $themeName;
            }
        }

        return $themes;
    }

    /**
     * Create the directory structure of the theme
     *
     * @param  string  $themeName  Theme name
     * @param  array  $directories  List of directories to create
     */
    protected function createThemeDirectories(string $themeName, array $directories): void
    {
        $themePath = $this->getThemePath($themeName);

        foreach ($directories as $directory) {
            $fullPath = "{$themePath}/{$directory}";
            if (! File::isDirectory($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }
        }
    }

    /**
     * Delete theme file
     *
     * @param  string  $themeName  Theme name
     * @param  string  $relativePath  Relative path from the theme directory
     */
    protected function deleteThemeFile(string $themeName, string $relativePath): bool
    {
        $themePath = $this->getThemePath($themeName);
        $filePath = "{$themePath}/{$relativePath}";

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    /**
     * Delete theme directory
     *
     * @param  string  $themeName  Theme name
     * @param  string  $relativePath  Relative path from the theme directory
     */
    protected function deleteThemeDirectory(string $themeName, string $relativePath): bool
    {
        $themePath = $this->getThemePath($themeName);
        $dirPath = "{$themePath}/{$relativePath}";

        if (File::isDirectory($dirPath)) {
            return File::deleteDirectory($dirPath);
        }

        return false;
    }
}
