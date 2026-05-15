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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ComposerLocalHelper
{
    /**
     * Path to composer.local.json file
     */
    protected static function getComposerLocalPath(): string
    {
        return base_path('composer.local.json');
    }

    /**
     * Automatically generate composer.local.json from plugins/ and themes/ directories
     *
     * @return bool Whether it succeeded
     */
    public static function syncAutoload(): bool
    {
        try {
            $composerLocalPath = self::getComposerLocalPath();

            // Detect plugin directories
            $plugins = self::detectPluginDirectories();

            // Detect theme directories
            $themes = self::detectThemeDirectories();

            // Generate autoload settings
            $autoload = [];

            // Autoload settings for plugins
            foreach ($plugins as $pluginName) {
                $autoload["Plugins\\{$pluginName}\\App\\"] = "plugins/{$pluginName}/app";
                $autoload["Plugins\\{$pluginName}\\Database\\Factories\\"] = "plugins/{$pluginName}/database/factories";
                $autoload["Plugins\\{$pluginName}\\Database\\Seeders\\"] = "plugins/{$pluginName}/database/seeders";
                $autoload["Plugins\\{$pluginName}\\Tests\\"] = "plugins/{$pluginName}/tests";
            }

            // Autoload settings for themes
            foreach ($themes as $themeName) {
                $autoload["Themes\\{$themeName}\\App\\"] = "themes/{$themeName}/app";
                $autoload["Themes\\{$themeName}\\Database\\Factories\\"] = "themes/{$themeName}/database/factories";
                $autoload["Themes\\{$themeName}\\Database\\Seeders\\"] = "themes/{$themeName}/database/seeders";
            }

            // Generate composer.local.json content
            $composerLocal = [
                '_comment' => 'This file is auto-generated. Do not edit manually.',
                '_generated_at' => date('Y-m-d H:i:s'),
                'autoload' => [
                    'psr-4' => $autoload,
                ],
            ];

            // Save as JSON file
            $json = json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            file_put_contents($composerLocalPath, json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Regenerate autoload to reflect composer.local.json
            // Continue with only a warning even if it fails, since plugin placement itself is already complete
            self::regenerateAutoload();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync composer.local.json: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Execute composer dump-autoload to regenerate autoload classmap / psr-4
     *
     * Call immediately after adding/removing plugins/themes to reflect new PSR-4 mappings
     * to the Laravel runtime. In environments where the composer binary is not available,
     * continue with a warning log (do not treat as a fatal error)
     */
    protected static function regenerateAutoload(): void
    {
        try {
            $process = new Process(
                ['composer', 'dump-autoload', '--optimize', '--no-scripts'],
                base_path(),
                null,
                null,
                60
            );
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('composer dump-autoload failed after composer.local.json sync', [
                    'exit_code' => $process->getExitCode(),
                    'stderr' => $process->getErrorOutput(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to regenerate autoload', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect plugin directories in the plugins/ directory
     *
     * @return array Array of plugin directory names
     */
    protected static function detectPluginDirectories(): array
    {
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return [];
        }

        $directories = File::directories($pluginsPath);
        $pluginNames = [];

        foreach ($directories as $directory) {
            $pluginName = basename($directory);

            // Exclude directories starting with .
            if (str_starts_with($pluginName, '.')) {
                continue;
            }

            // Check if app/ directory exists
            if (File::exists($directory.'/app')) {
                $pluginNames[] = $pluginName;
            }
        }

        return $pluginNames;
    }

    /**
     * Detect theme directories in the themes/ directory
     *
     * @return array Array of theme directory names
     */
    protected static function detectThemeDirectories(): array
    {
        $themesPath = base_path('themes');

        if (! File::exists($themesPath)) {
            return [];
        }

        $directories = File::directories($themesPath);
        $themeNames = [];

        foreach ($directories as $directory) {
            $themeName = basename($directory);

            // Exclude directories starting with .
            if (str_starts_with($themeName, '.')) {
                continue;
            }

            // Check if app/ directory exists
            if (File::exists($directory.'/app')) {
                $themeNames[] = $themeName;
            }
        }

        return $themeNames;
    }

    /**
     * Get the contents of composer.local.json
     */
    public static function getComposerLocalContent(): ?array
    {
        $composerLocalPath = self::getComposerLocalPath();

        if (! File::exists($composerLocalPath)) {
            return null;
        }

        $content = File::get($composerLocalPath);

        return json_decode($content, true);
    }

    /**
     * Check if composer.local.json exists
     */
    public static function exists(): bool
    {
        return File::exists(self::getComposerLocalPath());
    }

    /**
     * Check if a specific plugin is included in composer.local.json
     *
     * @param  string  $pluginName  Plugin name
     */
    public static function hasPlugin(string $pluginName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Plugins\\{$pluginName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }

    /**
     * Check if a specific theme is included in composer.local.json
     *
     * @param  string  $themeName  Theme name
     */
    public static function hasTheme(string $themeName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Themes\\{$themeName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }
}
