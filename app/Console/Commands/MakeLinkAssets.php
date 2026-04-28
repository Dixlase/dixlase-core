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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Theme;
use App\Models\Plugin;

class MakeLinkAssets extends Command
{
    protected $signature = 'make:link-assets';
    protected $description = 'Create symbolic links for admin, plugin, and theme assets';

    public function handle()
    {
        // 管理画面アセット
        $this->createLink(base_path('resources/views/admin'), public_path('assets/admin'));

        // 有効化されたプラグインのアセット
        $enabledPlugins = Plugin::whereNotNull('enabled_at')->get();
        foreach ($enabledPlugins as $plugin) {
            try {
                Artisan::call('dls:plugin:symlink', [
                    'action' => 'create',
                    'plugin' => $plugin->directory
                ]);
                $this->info("Link created for plugin: {$plugin->name}");
            } catch (\Exception $e) {
                $this->error("Failed to create link for plugin {$plugin->name}: {$e->getMessage()}");
            }
        }

        // 有効化されたテーマのアセット
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int)$themeSetting->value : null;
        $theme = $activeThemeId ? Theme::find($activeThemeId) : null;

        if ($theme) {
            try {
                Artisan::call('dls:theme:symlink', [
                    'action' => 'create',
                    'theme' => $theme->directory
                ]);
                $this->info("Link created for theme: {$theme->name}");
            } catch (\Exception $e) {
                $this->error("Failed to create link for theme {$theme->name}: {$e->getMessage()}");
            }
        } else {
            $this->error("Enabled theme not found.");
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
