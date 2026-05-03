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

namespace App\Helpers;

use App\Console\Commands\SyncGitIgnore;
use Illuminate\Support\Facades\Artisan;

/**
 * .gitignore file management helper
 *
 * This helper is a wrapper for the SyncGitIgnore command
 * Used for calls from the admin panel or simple usage within programs
 *
 * @see \App\Console\Commands\SyncGitIgnore
 */
class GitIgnoreHelper
{
    /**
     * Add plugin exclusion rule (!plugins/PluginName/)
     *
     * @param  string  $pluginName  Plugin name (e.g., DixlaseBackup)
     * @return bool Whether it succeeded
     */
    public static function addPluginExclusion(string $pluginName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-gitignore', [
                '--add-plugin' => $pluginName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to add plugin exclusion to .gitignore: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Remove plugin exclusion rule
     *
     * @param  string  $pluginName  Plugin name
     * @return bool Whether it succeeded
     */
    public static function removePluginExclusion(string $pluginName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-gitignore', [
                '--remove-plugin' => $pluginName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to remove plugin exclusion from .gitignore: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Add theme exclusion rule (!themes/ThemeName/)
     *
     * @param  string  $themeName  Theme name
     * @return bool Whether it succeeded
     */
    public static function addThemeExclusion(string $themeName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-gitignore', [
                '--add-theme' => $themeName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to add theme exclusion to .gitignore: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Remove theme exclusion rule
     *
     * @param  string  $themeName  Theme name
     * @return bool Whether it succeeded
     */
    public static function removeThemeExclusion(string $themeName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-gitignore', [
                '--remove-theme' => $themeName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to remove theme exclusion from .gitignore: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync all plugin and theme exclusion rules
     *
     * @return bool Whether it succeeded
     */
    public static function syncAll(): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-gitignore', [
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to sync gitignore: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check if plugin exclusion rule exists
     *
     * @param  string  $pluginName  Plugin name
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        return SyncGitIgnore::hasPluginExclusion($pluginName);
    }

    /**
     * Check if theme exclusion rule exists
     *
     * @param  string  $themeName  Theme name
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        return SyncGitIgnore::hasThemeExclusion($themeName);
    }
}
