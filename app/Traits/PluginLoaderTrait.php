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


namespace App\Traits;

use App\Models\Plugin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

trait PluginLoaderTrait
{



    /**
     * 有効化されたプラグインをロードする
     */
    public function loadActivePlugins()
    {

        $activePlugins = Plugin::where('status', 1)->get();

        // 有効化されたプラグインのみオートローダーを登録
        //$this->loadPluginAutoloaders($activePlugins);

        foreach ($activePlugins as $plugin) {
            $pluginName = $plugin->name;
            $pluginDirectory = $plugin->directory;

            // サービスプロバイダの登録
            $providerClass = $this->resolvePluginServiceProvider($pluginName, $pluginDirectory);

            // プラグインのリソースをロード
            if ($providerClass) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * プラグインのリソースをロードする
     */
    protected function loadPluginFiles($pluginName, $pluginPath, $customPluginPath)
    {
        $fileTypes = config('custom.custom_file_types', []);

        foreach ($fileTypes as $type => $settings) {
            $coreSubPath = "{$pluginPath}/{$settings['path']}";
            $customSubPath = "{$customPluginPath}/{$settings['path']}";

            $this->loadFilesByType($type, $coreSubPath, $customSubPath, $settings['namespace']);
        }
    }

    /**
     * ファイルタイプごとのロード処理
     */
    protected function loadFilesByType($type, $defaultPath, $customPath, $namespace)
    {
        switch ($type) {
            case 'config':
                $this->loadPluginConfigs($defaultPath, $customPath, $namespace);
                break;
            case 'routes':
                $this->loadPluginRoutes($customPath, $defaultPath);
                break;
            case 'lang':
                $this->loadPluginTranslations($customPath, $defaultPath, $namespace);
                break;
            case 'views':
                $this->loadPluginViews($customPath, $defaultPath, $namespace);
                break;
            case 'migrations':
                $this->loadPluginMigrations($customPath, $defaultPath);
                break;
            default:
                $this->loadCustomFiles($type, $defaultPath, $customPath, $namespace);
        }
    }

    /**
     * コンフィグの読み込み
     */
    protected function loadPluginConfigs($corePath, $customPath, $namespace)
    {
        $defaultMergeMode = config('custom.default_merge_mode', 'merge');

        // プラグインのコンフィグをロード
        $pluginConfigs = $this->loadConfigFiles($corePath);

        // カスタムのプラグインのコンフィグをロード
        $customPluginConfigs = $this->loadConfigFiles($customPath);

        // デフォルトとカスタムを結合または置換し、登録
        foreach ($pluginConfigs as $key => $coreConfig) {
            if (isset($customPluginConfigs[$key])) {
                $customConfig = $customPluginConfigs[$key];
                $mergeMode = $customConfig['_merge_mode'] ?? $defaultMergeMode;
                unset($customConfig['_merge_mode']);

                if ($mergeMode === 'replace') {
                    // カスタムコンフィグで置換
                    config(["{$namespace}.{$key}" => $customConfig]);
                } else { // 'merge'
                    // デフォルトとカスタムをマージ
                    config(["{$namespace}.{$key}" => array_merge_recursive($coreConfig, $customConfig)]);
                }
            } else {
                // カスタムが存在しない場合はそのまま登録
                config(["{$namespace}.{$key}" => $coreConfig]);
            }
        }

        // カスタムディレクトリにのみ存在する新しいコンフィグを登録
        foreach ($customPluginConfigs as $key => $customConfig) {
            if (!isset($coreConfigs[$key])) {
                unset($customConfig['_merge_mode']);
                config(["{$namespace}.{$key}" => $customConfig]);
            }
        }
    }


    /**
     * コンフィグファイルを読み込む
     */
    private function loadConfigFiles($path)
    {
        $configs = [];

        if (is_dir($path)) {
            foreach (glob($path . '/*.php') as $file) {
                $key = basename($file, '.php');
                $configs[$key] = require $file;
            }
        }

        return $configs;
    }

    /**
     * ルートの読み込み
     */
    protected function loadPluginRoutes($customPath, $defaultPath)
    {
        $paths = array_filter([$customPath, $defaultPath]);
        foreach ($paths as $path) {
            if (is_dir($path)) {

                foreach (glob("{$path}/*.php") as $routeFile) {

                    Route::middleware('web')->group($routeFile);
                }
            }
        }
    }

    /**
     * ビューの読み込み
     */
    protected function loadPluginViews($customPath, $defaultPath, $namespace)
    {
        if (is_dir($customPath)) {
            View::addNamespace($namespace, $customPath);
        }

        if (is_dir($defaultPath)) {
            View::addNamespace($namespace, $defaultPath);
        }
    }

    /**
     * 言語ファイルの読み込み
     */
    protected function loadPluginTranslations($customPath, $corePath, $namespace)
    {
        $paths = array_filter([$customPath, $corePath]);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $namespace);
            }
        }
    }

    /**
     * マイグレーションファイルの読み込み
     */
    protected function loadPluginMigrations($customPath, $corePath)
    {
        $paths = array_filter([$customPath, $corePath]);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }
    /**
     * プラグインのサービスプロバイダを解決する
     */
    protected function resolvePluginServiceProvider(string $pluginName, string $pluginDirectory): ?string
    {
        $defaultProvider = "Plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginName}ServiceProvider";
        $customProvider = "custom\\plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginName}ServiceProvider";


        if (class_exists($customProvider)) {
            return $customProvider;
        } elseif (class_exists($defaultProvider)) {
            return $defaultProvider;
        }

        return null;
    }

    /**
     * プラグインのメタ情報を取得
     */
    protected function getPluginMetadata($pluginDirectory)
    {
        $composerJsonPath = $pluginDirectory . '/composer.json';

        if (file_exists($composerJsonPath)) {
            return json_decode(file_get_contents($composerJsonPath), true);
        }

        return null;
    }

    /**
     * すべてのプラグインのメタ情報を取得
     */
    public function getAllPluginsMetadata()
    {
        $pluginDirectories = glob(base_path('plugins/*'), GLOB_ONLYDIR);
        $metadata = [];

        foreach ($pluginDirectories as $pluginDirectory) {
            $meta = $this->getPluginMetadata($pluginDirectory);
            if ($meta) {
                $metadata[] = $meta;
            }
        }

        return $metadata;
    }
}
