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
use Illuminate\Support\Facades\Lang;

trait PluginLoaderTrait
{

    use ConfigLoaderTraits;

    /**
     * 有効化されたプラグインをロードする
     */
    public function loadActivePlugins()
    {

        $activePlugins = Plugin::where('status', 1)->get();


        foreach ($activePlugins as $plugin) {
            $pluginName = $plugin->name;
            $pluginDirectory = $plugin->directory;
            $pluginSlug = $plugin->slug;

            // サービスプロバイダの登録
            $providerClass = $this->resolvePluginServiceProvider($pluginName, $pluginDirectory);

            // プラグインのリソースをロード
            if ($providerClass) {
                $this->app->register($providerClass);
            }

            // プラグインのファイルをロードする
            $pluginPath = base_path("plugins/{$pluginDirectory}");
            $customPluginPath = base_path("custom/plugins/{$pluginDirectory}");

            $this->loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug);
        }
    }

    /**
     * プラグインのリソースをロードする
     */
    protected function loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug = null)
    {
        $fileTypes = config('custom.file_types', []);

        foreach ($fileTypes as $type => $settings) {

            $namespace = $settings['namespace'];
            if (($type === 'lang' || $type === 'views') && empty($namespace) && $pluginSlug) {
                $namespace = $pluginSlug;
            }

            $coreSubPath = "{$pluginPath}/{$settings['path']}";
            $customSubPath = "{$customPluginPath}/{$settings['path']}";

            $this->loadFilesByType($type, $coreSubPath, $customSubPath, $namespace);
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

        // プラグインのデフォルト設定
        $pluginConfigs = $this->loadConfigFiles($corePath);
        // カスタム上書き設定
        $customConfigs = $this->loadConfigFiles($customPath);

        // デフォルトとカスタムを結合または置換し、登録
        foreach ($customConfigs as $key => $customConfig) {
            if (isset($pluginConfigs[$key])) {
                // core + custom をマージ
                $mergeMode = $customConfig['_merge_mode'] ?? $defaultMergeMode;
                unset($customConfig['_merge_mode']);

                if ($mergeMode === 'replace') {
                    $pluginConfigs[$key] = $customConfig;
                } else {
                    // 再帰マージ
                    $pluginConfigs[$key] = array_merge_recursive($pluginConfigs[$key], $customConfig);
                }
            } else {
                // 新規キー
                $pluginConfigs[$key] = $customConfig;
            }
        }

        // 2) マージ後の $pluginConfigs を config() に書き込む
        //    「namespace が空ならトップレベルに設定」「namespace があればサブキーに設定」

        if ($namespace === '') {
            // -------------------------------
            // トップレベルにマージする場合
            // -------------------------------
            foreach ($pluginConfigs as $topKey => $value) {
                // 既存の設定を取得
                $existingValue = config($topKey, []);

                // 値が配列同士なら再帰マージ
                if (is_array($existingValue) && is_array($value)) {
                    config([$topKey => array_merge_recursive($existingValue, $value)]);
                } else {
                    // 配列でない or 置き換えの場合はそのままセット
                    config([$topKey => $value]);
                }
            }
        } else {
            // -------------------------------
            // 従来どおりサブキーとして設定
            // -------------------------------
            foreach ($pluginConfigs as $key => $value) {
                $existingValue = config("{$namespace}.{$key}", []);

                if (is_array($existingValue) && is_array($value)) {
                    config(["{$namespace}.{$key}" => array_merge_recursive($existingValue, $value)]);
                } else {
                    config(["{$namespace}.{$key}" => $value]);
                }
            }
        }

        // コンフィグの再配置
        $this->reorderAllConfig();
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

        $translations = Lang::getLoader()->load(app()->getLocale(), 'admin');
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
