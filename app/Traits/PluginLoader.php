<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;

trait PluginLoader
{
    /**
     * 有効化されたプラグインをロードする
     */
    public function loadActivePlugins()
    {
        $plugins = Plugin::where('status', 1)->get();

        foreach ($plugins as $plugin) {
            $pluginPath = base_path('plugins/' . $plugin->name . '/app');
            if (File::exists($pluginPath)) {
                // プラグインのServiceProviderをロード
                $provider = $plugin->namespace . '\\App\\Providers\\' . $plugin->name . 'ServiceProvider';
                if (class_exists($provider)) {
                    $this->app->register($provider);
                    logger()->info("Plugin ServiceProvider loaded: {$provider}");
                } else {
                    logger()->error("Plugin ServiceProvider class not found: {$provider}");
                }
            } else {
                logger()->warning("Plugin directory not found: {$pluginPath}");
            }
        }
    }
}
