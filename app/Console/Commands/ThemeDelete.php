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

use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:delete {themeDirectory : The directory name of the theme to delete}
                            {--force : Force delete without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a theme\'s files and directory (uninstall it first)';

    /**
     * Whether the argument names a directory rather than a path.
     *
     * A theme directory is one segment under themes/, so anything carrying a
     * separator, a traversal, a NUL or a leading dot is not one. Rejecting
     * reports "not found", which is what a path that is not a theme directory
     * should look like.
     */
    protected function isSinglePathSegment(mixed $candidate): bool
    {
        if (! is_string($candidate)) {
            return false;
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $candidate) !== 1) {
            return false;
        }

        return ! str_contains($candidate, '..');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeDirectory = $this->argument('themeDirectory');

        // Same defect as PluginDelete: the directory name arrives from a
        // request rule that is only `required|string`, and interpolating it
        // produces a real path -- base_path('themes/../app') normalises to the
        // application's own app/ directory, which File::exists() confirms and
        // File::deleteDirectory() would then remove. Contained here because
        // this is the last point before the recursive delete.
        if (! $this->isSinglePathSegment($themeDirectory)) {
            $this->error(__('admin/command/theme-delete.not_found', ['directory' => $themeDirectory]));

            return 1;
        }

        $themePath = base_path('themes/'.$themeDirectory);

        // Check if theme directory exists
        if (! File::exists($themePath)) {
            $this->error(__('admin/command/theme-delete.not_found', ['directory' => $themeDirectory]));

            return Command::FAILURE;
        }

        // Check if registered in database (whether uninstalled or not)
        $theme = Theme::where('directory', $themeDirectory)->first();

        if ($theme) {
            // If theme is still installed
            if ($theme->isInstalled()) {
                $this->error(__('admin/command/theme-delete.still_installed', ['themeName' => $theme->name]));
                $this->warn(__('admin/command/theme-delete.uninstall_first'));

                return Command::FAILURE;
            }

            // If theme is active (just in case)
            if ($theme->isEnabled()) {
                $this->error(__('admin/command/theme-delete.still_enabled', ['themeName' => $theme->name]));
                $this->warn(__('admin/command/theme-delete.disable_first'));

                return Command::FAILURE;
            }
        }

        // Confirmation prompt
        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command/theme-delete.confirm', ['directory' => $themeDirectory]), false)) {
                $this->info(__('admin/command/theme-delete.cancelled'));

                return Command::SUCCESS;
            }
        }

        // Delete directory
        try {
            File::deleteDirectory($themePath);
            $this->info(__('admin/command/theme-delete.deleted', ['path' => $themePath]));
        } catch (\Exception $e) {
            $this->error(__('admin/command/theme-delete.failed', ['error' => $e->getMessage()]));

            return Command::FAILURE;
        }

        // Delete theme record from database as well (if exists)
        if ($theme) {
            $theme->delete();
            $this->info(__('admin/command/theme-delete.database_removed', ['themeName' => $theme->name]));
        }

        // Remove theme exclusion rule from .git/info/exclude
        if (GitExcludeHelper::removeThemeExclusion($themeDirectory)) {
            $this->info(__('console/commands/theme_delete.theme_removed_from_git_info_exclude', ['themeDirectory' => $themeDirectory]));
        }

        // Remove theme exclusion rule from .gitignore
        if (GitIgnoreHelper::removeThemeExclusion($themeDirectory)) {
            $this->info(__('console/commands/theme_delete.theme_removed_from_gitignore', ['themeDirectory' => $themeDirectory]));
        }

        // Update composer.local.json
        ComposerLocalHelper::syncAutoload();
        $this->info(__('console/commands/theme_delete.updated_composer_local_json'));

        $this->info(__('admin/command/theme-delete.completed', ['directory' => $themeDirectory]));

        return Command::SUCCESS;
    }
}
