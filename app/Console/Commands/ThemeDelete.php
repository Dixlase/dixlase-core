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
    protected $signature = 'dls:theme:delete {themeDirectory : '.'command.theme_delete.theme_directory_prompt'.'}
                            {--force : '.'command.theme_delete.force_option'.'}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_delete.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeDirectory = $this->argument('themeDirectory');
        $themePath = base_path('themes/'.$themeDirectory);

        // Check if theme directory exists
        if (! File::exists($themePath)) {
            $this->error(__('admin/command.theme_delete.not_found', ['directory' => $themeDirectory]));

            return Command::FAILURE;
        }

        // Check if registered in database (whether uninstalled or not)
        $theme = Theme::where('directory', $themeDirectory)->first();

        if ($theme) {
            // If theme is still installed
            if ($theme->isInstalled()) {
                $this->error(__('admin/command.theme_delete.still_installed', ['themeName' => $theme->name]));
                $this->warn(__('admin/command.theme_delete.uninstall_first'));

                return Command::FAILURE;
            }

            // If theme is active (just in case)
            if ($theme->isEnabled()) {
                $this->error(__('admin/command.theme_delete.still_enabled', ['themeName' => $theme->name]));
                $this->warn(__('admin/command.theme_delete.disable_first'));

                return Command::FAILURE;
            }
        }

        // Confirmation prompt
        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command.theme_delete.confirm', ['directory' => $themeDirectory]), false)) {
                $this->info(__('admin/command.theme_delete.cancelled'));

                return Command::SUCCESS;
            }
        }

        // Delete directory
        try {
            File::deleteDirectory($themePath);
            $this->info(__('admin/command.theme_delete.deleted', ['path' => $themePath]));
        } catch (\Exception $e) {
            $this->error(__('admin/command.theme_delete.failed', ['error' => $e->getMessage()]));

            return Command::FAILURE;
        }

        // Delete theme record from database as well (if exists)
        if ($theme) {
            $theme->delete();
            $this->info(__('admin/command.theme_delete.database_removed', ['themeName' => $theme->name]));
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

        $this->info(__('admin/command.theme_delete.completed', ['directory' => $themeDirectory]));

        return Command::SUCCESS;
    }
}
