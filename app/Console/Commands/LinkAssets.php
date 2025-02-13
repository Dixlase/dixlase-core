<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
use Illuminate\Support\Facades\DB;
use App\Models\Theme;
use App\Models\Plugin;

class LinkAssets extends Command
{
    protected $signature = 'assets:link';
    protected $description = 'Create symbolic links for admin, plugin, and theme assets';

    public function handle()
    {
        // 管理画面アセット
        $this->createLink(base_path('resources/views/admin'), public_path('assets/admin'));

        // 有効化されたプラグインのアセット
        $enabledPlugins = Plugin::where('status', 1)->get();
        foreach ($enabledPlugins as $plugin) {
            try {
                create_plugin_symlink($plugin->directory);
            } catch (\Exception $e) {
                $this->error("Failed to create link for plugin {$plugin->name}: {$e->getMessage()}");
            }
        }

        // 有効化されたテーマのアセット
        $activeThemeId = DB::table('theme_settings')->value('active_theme_id');
        $theme = Theme::find($activeThemeId);

        if ($theme && File::exists(base_path("themes/{$theme->directory}/assets"))) {
            $themeDir = base_path("themes/{$theme->directory}/assets");
            $this->createLink($themeDir, public_path('assets/theme'));
        } else {
            $this->error("Active theme assets not found.");
        }

        $this->info('All asset symbolic links have been created.');
    }

    protected function createLink($target, $link)
    {
        if (!File::exists($target)) {
            $this->error("Target does not exist: {$target}");
            return;
        }

        // リンク先ディレクトリが存在しない場合は作成
        $linkDir = dirname($link);
        if (!File::exists($linkDir)) {
            File::makeDirectory($linkDir, 0755, true);
            $this->info("Link directory created: {$linkDir}");
        }

        if (File::exists($link)) {
            $this->info("Link already exists, deleting: {$link}");
            File::delete($link);
        }

        try {
            File::link($target, $link);
            $this->info("Link created: {$link} -> {$target}");
        } catch (\Exception $e) {
            $this->error("Failed to create link: {$e->getMessage()}");
        }
    }
}
