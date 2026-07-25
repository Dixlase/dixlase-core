<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Contracts\Repositories\ThemeRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Theme resource loading mechanism
 */
trait ThemeLoaderTrait
{
    /**
     * Lazy resolution of ThemeRepositoryInterface
     */
    protected function resolveThemeRepository(): ThemeRepositoryInterface
    {
        return app(ThemeRepositoryInterface::class);
    }

    /**
     * Load theme language files
     *
     * @param  string  $themePath  Theme base path
     * @param  string  $customThemePath  Custom theme base path
     * @param  string  $namespace  Language file namespace
     */
    protected function loadThemeTranslations(string $themePath, string $customThemePath, string $namespace): void
    {
        // Prioritize custom path
        $paths = array_filter([$customThemePath.'/lang', $themePath.'/lang']);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $namespace);
            }
        }
    }

    /**
     * Load theme views
     *
     * @param  string  $themePath  Theme base path
     * @param  string  $customThemePath  Custom theme base path
     * @param  string  $namespace  View namespace
     */
    protected function loadThemeViews(string $themePath, string $customThemePath, string $namespace): void
    {
        $paths = array_filter([$customThemePath.'/resources/views', $themePath.'/resources/views']);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                \Illuminate\Support\Facades\View::addNamespace($namespace, $path);
            }
        }
    }

    /**
     * Load theme settings file
     *
     * @param  string  $themePath  Theme base path
     * @param  string  $customThemePath  Custom theme base path
     * @param  string  $themeSlug  Theme slug
     */
    protected function loadThemeConfig(string $themePath, string $customThemePath, string $themeSlug): void
    {
        // Prioritize custom path
        $customConfigPath = $customThemePath.'/config';
        $coreConfigPath = $themePath.'/config';

        $configPaths = [];
        if (is_dir($customConfigPath)) {
            $configPaths[] = $customConfigPath;
        }
        if (is_dir($coreConfigPath)) {
            $configPaths[] = $coreConfigPath;
        }

        foreach ($configPaths as $configPath) {
            foreach (glob($configPath.'/*.php') as $configFile) {
                $configName = basename($configFile, '.php');
                $key = "theme.{$themeSlug}.{$configName}";

                // Merge if not already set
                if (! config()->has($key)) {
                    config([$key => require $configFile]);
                }
            }
        }
    }

    /**
     * Generate theme namespace
     *
     * @param  string  $themeDirectory  Theme directory name
     */
    protected function getThemeNamespace(string $themeDirectory): string
    {
        return Str::kebab($themeDirectory);
    }

    /**
     * Get active theme ID
     */
    public function getEnabledTheme(): int
    {
        return $this->resolveThemeRepository()->getEnabledThemeId();
    }

    /**
     * Get currently active theme directory name
     */
    public function getEnabledThemeDirectory(): string
    {
        return $this->resolveThemeRepository()->getEnabledThemeDirectory();
    }

    /**
     * Generate full URL for theme asset
     */
    public function themeAsset(string $path): string
    {
        $themeDirectory = $this->getEnabledThemeDirectory();

        return asset("themes/{$themeDirectory}/{$path}");
    }
}
