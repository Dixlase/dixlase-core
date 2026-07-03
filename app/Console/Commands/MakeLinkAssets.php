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

use App\Models\Plugin;
use App\Models\Theme;
use App\Support\RelativeSymlink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MakeLinkAssets extends Command
{
    protected $signature = 'make:link-assets';

    protected $description = 'Create symbolic links for admin, plugin, and theme assets';

    public function handle()
    {
        // Admin panel assets
        $this->createLink(base_path('resources/views/admin'), public_path('assets/admin'));

        // Assets of enabled plugins
        $enabledPlugins = Plugin::whereNotNull('enabled_at')->get();
        foreach ($enabledPlugins as $plugin) {
            try {
                Artisan::call('dls:plugin:symlink', [
                    'action' => 'create',
                    'plugin' => $plugin->directory,
                ]);
                $this->info("Link created for plugin: {$plugin->name}");
            } catch (\Exception $e) {
                $this->error("Failed to create link for plugin {$plugin->name}: {$e->getMessage()}");
            }
        }

        // Assets of enabled themes
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;
        $theme = $activeThemeId ? Theme::find($activeThemeId) : null;

        if ($theme) {
            try {
                Artisan::call('dls:theme:symlink', [
                    'action' => 'create',
                    'theme' => $theme->directory,
                ]);
                $this->info("Link created for theme: {$theme->name}");
            } catch (\Exception $e) {
                $this->error("Failed to create link for theme {$theme->name}: {$e->getMessage()}");
            }
        } else {
            $this->error('Enabled theme not found.');
        }

        $this->info('All asset symbolic links have been created.');
    }

    protected function createLink($target, $link)
    {
        if (! File::exists($target)) {
            $this->error("Target does not exist: {$target}");

            return;
        }

        // Create link destination directory if it does not exist
        $linkDir = dirname($link);
        if (! File::exists($linkDir)) {
            File::makeDirectory($linkDir, 0755, true);
            $this->info("Link directory created: {$linkDir}");
        }

        if (File::exists($link)) {
            $this->info("Link already exists, deleting: {$link}");
            File::delete($link);
        }

        try {
            // Relative symlink so the recorded target resolves from any
            // container that shares the same physical path (nginx +
            // php-fpm typically mount the app at different roots).
            RelativeSymlink::create($target, $link);
            $this->info("Link created: {$link} -> {$target}");
        } catch (\Exception $e) {
            $this->error("Failed to create link: {$e->getMessage()}");
        }
    }
}
