<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeSymlink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:symlink 
                            {action : The action to perform (create|remove)}
                            {theme : The directory name of the theme}';

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('admin/command.theme_symlink.description');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $themeDirName = $this->argument('theme');

        if (!in_array($action, ['create', 'remove'])) {
            $this->error(__('admin/command.theme_symlink.invalid_action'));
            return 1;
        }

        if ($action === 'create') {
            $this->createThemeSymlink($themeDirName);
            $this->info(__('admin/command.theme_symlink.created', ['theme' => $themeDirName]));
        } else {
            $this->removeThemeSymlink($themeDirName);
            $this->info(__('admin/command.theme_symlink.removed', ['theme' => $themeDirName]));
        }

        return 0;
    }

    /**
     * Create symlink for theme assets
     *
     * @param string $themeDirName
     * @return void
     */
    protected function createThemeSymlink(string $themeDirName)
    {
        $target = base_path("themes/{$themeDirName}/resources/assets");
        $link = public_path("assets/themes/{$themeDirName}");

        if (File::exists($target) && !File::exists($link)) {
            File::ensureDirectoryExists(dirname($link));
            File::link($target, $link);
        }
    }

    /**
     * Remove symlink for theme assets
     *
     * @param string $themeDirName
     * @return void
     */
    protected function removeThemeSymlink(string $themeDirName)
    {
        $link = public_path("assets/themes/{$themeDirName}");
        
        if (File::exists($link) || is_link($link)) {
            File::delete($link);
        }
    }
}
