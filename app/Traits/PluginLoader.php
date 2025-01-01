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
use Illuminate\Support\Facades\Route;

trait PluginLoader
{
    /**
     * 有効化されたプラグインをロードする
     */
    public function loadActivePlugins()
    {
        $plugins = Plugin::where('status', 1)->get();
        foreach ($plugins as $plugin) {
            $providerClass = $this->resolvePluginServiceProvider($plugin->name);
            if ($providerClass) {
                $this->app->register($providerClass);
            }
        }
    }


    /**
     * プラグインのサービスプロバイダを解決する
     */
    protected function resolvePluginServiceProvider(string $pluginName): ?string
    {
        $defaultProvider = "Plugins\\{$pluginName}\\App\\Providers\\{$pluginName}ServiceProvider";
        $customProvider = "Custom\\Plugins\\{$pluginName}\\App\\Providers\\{$pluginName}ServiceProvider";

        if (class_exists($customProvider)) {
            return $customProvider;
        } elseif (class_exists($defaultProvider)) {
            return $defaultProvider;
        }

        return null;
    }

    /**
     * プラグインのコンフィグをロードしてマージ
     */
    public function loadPluginConfigs($defaultConfigDir, $customConfigDir, $namespace)
    {
        $mergedConfigs = [];

        // デフォルトのコンフィグをロード
        if (is_dir($defaultConfigDir)) {
            foreach (glob($defaultConfigDir . '/*.php') as $file) {
                $configKey = basename($file, '.php');
                $mergedConfigs[$configKey] = require $file;
            }
        }

        // カスタムのコンフィグを優先してマージ
        if (is_dir($customConfigDir)) {
            foreach (glob($customConfigDir . '/*.php') as $file) {
                $configKey = basename($file, '.php');
                $customConfig = require $file;

                // デフォルトのコンフィグとマージ
                if (isset($mergedConfigs[$configKey])) {
                    $mergedConfigs[$configKey] = array_merge($mergedConfigs[$configKey], $customConfig);
                } else {
                    $mergedConfigs[$configKey] = $customConfig;
                }
            }
        }

        // Laravelのconfigに登録
        foreach ($mergedConfigs as $key => $value) {
            config(["{$namespace}.{$key}" => $value]);
        }

        return $mergedConfigs;
    }


    /**
     * プラグインのすべてのルートをロードする
     */
    public function loadPluginRoutes($customPath, $defaultPath)
    {

        if (is_dir(base_path($customPath))) {
            foreach (glob(base_path($customPath) . '/*.php') as $routeFile) {
                //$this->loadRoutesFrom($routeFile);
                Route::middleware('web')->group($routeFile);
            }
        } elseif (is_dir(base_path($defaultPath))) {
            foreach (glob(base_path($defaultPath) . '/*.php') as $routeFile) {
                //$this->loadRoutesFrom($routeFile);
                Route::middleware('web')->group($routeFile);
            }
        }
    }
    /**
     * プラグインのすべてのビューをロードする
     */
    public function loadPluginViews($customPath, $defaultPath, $namespace)
    {
        if (is_dir(base_path($customPath))) {
            $this->loadViewsFrom(base_path($customPath), $namespace);
        } elseif (is_dir(base_path($defaultPath))) {
            $this->loadViewsFrom(base_path($defaultPath), $namespace);
        }
    }

    /**
     * プラグインのすべてのマイグレーションをロードする
     */
    public function loadPluginMigrations($customPath, $defaultPath)
    {
        if (is_dir(base_path($customPath))) {
            $this->loadMigrationsFrom(base_path($customPath));
        } elseif (is_dir(base_path($defaultPath))) {
            $this->loadMigrationsFrom(base_path($defaultPath));
        }
    }

    /**
     * プラグインのすべての言語ファイルをロードする
     */
    public function loadPluginTranslations($customPath, $defaultPath, $namespace)
    {
        if (is_dir(base_path($customPath))) {
            $this->loadTranslationsFrom(base_path($customPath), $namespace);
        } elseif (is_dir(base_path($defaultPath))) {
            $this->loadTranslationsFrom(base_path($defaultPath), $namespace);
        }
    }
}
