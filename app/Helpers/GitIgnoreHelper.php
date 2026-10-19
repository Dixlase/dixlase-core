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

namespace App\Helpers;

use App\Console\Commands\SyncGitIgnore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * .gitignore file management helper
 *
 * Older versions of core wrote a `!plugins/<Name>/` or `!themes/<Name>/`
 * rule into .gitignore for every installed extension, which made core's git
 * track extension directories that it ignores on purpose. Core no longer
 * writes them. The add methods are kept as no-ops so that existing callers
 * keep working; the remove and sync methods clean up rules that are still
 * there.
 *
 * @see \App\Console\Commands\SyncGitIgnore
 */
class GitIgnoreHelper
{
    /**
     * Formerly added a re-include rule for a plugin; now does nothing
     *
     * @deprecated Core no longer re-includes extension directories in git.
     *             Kept so that existing callers keep working; it will be
     *             removed in a later release.
     *
     * @param  string  $pluginName  Plugin directory name
     * @return bool Always true
     */
    public static function addPluginExclusion(string $pluginName): bool
    {
        return true;
    }

    /**
     * Remove the re-include rule for a plugin, if there is one
     *
     * @param  string  $pluginName  Plugin directory name
     * @return bool Whether it succeeded
     */
    public static function removePluginExclusion(string $pluginName): bool
    {
        return self::run(['--remove-plugin' => $pluginName]);
    }

    /**
     * Formerly added a re-include rule for a theme; now does nothing
     *
     * @deprecated Core no longer re-includes extension directories in git.
     *             Kept so that existing callers keep working; it will be
     *             removed in a later release.
     *
     * @param  string  $themeName  Theme directory name
     * @return bool Always true
     */
    public static function addThemeExclusion(string $themeName): bool
    {
        return true;
    }

    /**
     * Remove the re-include rule for a theme, if there is one
     *
     * @param  string  $themeName  Theme directory name
     * @return bool Whether it succeeded
     */
    public static function removeThemeExclusion(string $themeName): bool
    {
        return self::run(['--remove-theme' => $themeName]);
    }

    /**
     * Remove every plugin and theme re-include rule
     *
     * @return bool Whether it succeeded
     */
    public static function syncAll(): bool
    {
        return self::run([]);
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

    /**
     * Run the cleanup command and write its result
     *
     * @param  array<string, string>  $options  Command options
     * @return bool Whether it succeeded
     */
    private static function run(array $options): bool
    {
        try {
            return Artisan::call('dls:sync-gitignore', $options + ['--confirm' => true]) === 0;
        } catch (\Exception $e) {
            Log::error('Failed to clean up .gitignore: '.$e->getMessage());

            return false;
        }
    }
}
