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

namespace App\Console\Commands;

use App\Console\Traits\CleansExtensionReincludeRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Removes the `!plugins/<Name>` and `!themes/<Name>` rules that older
 * versions of core wrote into .git/info/exclude for every installed extension.
 *
 * Core ignores `plugins/*` and `themes/*` on purpose, and each of those
 * rules re-included one extension directory in core's git. Installing,
 * creating or deleting an extension no longer touches .git/info/exclude;
 * run this once to clean up a checkout that still carries the rules:
 *
 *     php artisan dls:sync-git-exclude            # preview
 *     php artisan dls:sync-git-exclude --confirm  # write
 */
class SyncGitExclude extends Command
{
    use CleansExtensionReincludeRules;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:sync-git-exclude
                            {--confirm : Write the cleaned file (default is a dry-run preview)}
                            {--force : Same as --confirm, kept for older callers}
                            {--dry-run : Preview only (the default); wins over --confirm}
                            {--plugins-only : Only remove !plugins/ rules}
                            {--themes-only : Only remove !themes/ rules}
                            {--remove-plugin= : Only remove the rule for this plugin directory}
                            {--remove-theme= : Only remove the rule for this theme directory}
                            {--add-plugin= : Deprecated, does nothing}
                            {--add-theme= : Deprecated, does nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the !plugins/ and !themes/ re-include rules older versions added to .git/info/exclude';

    /**
     * Path to the .git/info/exclude file
     */
    protected function rulesFilePath(): string
    {
        return base_path('.git/info/exclude');
    }

    /**
     * Name of the file as shown to the operator
     */
    protected function rulesFileLabel(): string
    {
        return '.git/info/exclude';
    }

    /**
     * Check if plugin exclusion rule exists
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        return self::fileContains("!plugins/{$pluginName}");
    }

    /**
     * Check if theme exclusion rule exists
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        return self::fileContains("!themes/{$themeName}");
    }

    /**
     * Whether the .git/info/exclude file contains the given text
     */
    private static function fileContains(string $needle): bool
    {
        $path = base_path('.git/info/exclude');

        return File::exists($path) && str_contains(File::get($path), $needle);
    }
}
