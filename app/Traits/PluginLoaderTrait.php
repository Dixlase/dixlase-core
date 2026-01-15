<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Traits;

use App\Models\Plugin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

trait PluginLoaderTrait
{

    use ConfigLoaderTrait;

    /**
     * 有効化されたプラグインをロードする
     */
    public function loadEnabledPlugins()
    {
        // コマンドライン引数から直接チェック
        $pluginManagementFlag = $this->getUninstallingPluginFromArgs();

        // プラグイン管理コマンド実行中はプラグインローダーをスキップ
        if ($pluginManagementFlag === 'PLUGIN_MANAGEMENT_COMMAND') {
            return;
        }

        // app:uninstall コマンド実行中もスキップ
        if (isset($_SERVER['argv']) && in_array('app:uninstall', $_SERVER['argv'])) {
            return;
        }

        //Pluginテーブルのenabled_atがnullでないレコードを取得
        //テーブルが存在しているか確認
        if (!Schema::hasTable('plugins')) {
            return;
        }

        $plugins = Plugin::whereNotNull('enabled_at')->get();

        foreach ($plugins as $plugin) {
            $pluginName = $plugin->name;
            $pluginDirectory = $plugin->directory;
            $pluginSlug = $plugin->slug;
            $pluginPath = base_path('plugins/' . $pluginDirectory);
            $customPluginPath = base_path('custom/plugins/' . $pluginDirectory);

            // プラグインのファイルをロード
            $this->loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug);

            // サービスプロバイダの登録 (プラグインのファイルをロードした後)
            $providerClass = $this->resolvePluginServiceProvider($pluginName, $pluginDirectory);

            if ($providerClass) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * コマンドライン引数からプラグイン管理対象を取得
     */
    private function getUninstallingPluginFromArgs(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];
        
        // プラグイン管理コマンドの場合はプラグインローダーをスキップ
        if (count($argv) >= 2) {
            $pluginCommands = [
                'plugin:install',
                'plugin:uninstall',
                'plugin:enable',
                'plugin:disable'
            ];
            if (in_array($argv[1], $pluginCommands)) {
                return 'PLUGIN_MANAGEMENT_COMMAND';
            }
        }
        
        return null;
    }

    /**
     * プラグインのリソースをロードする
     */
    protected function loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug = null)
    {

        $fileTypes = config('custom.file_types', []);


        foreach ($fileTypes as $type => $settings) {

            $coreSubPath = "{$pluginPath}/{$settings['path']}";
            $customSubPath = "{$customPluginPath}/{$settings['path']}";

            $this->loadFilesByType($type, $coreSubPath, $customSubPath, $pluginSlug);
        }
    }

    /**
     * ファイルタイプごとのロード処理
     */
    protected function loadFilesByType($type, $defaultPath, $customPath, $pluginSlug)
    {

        switch ($type) {
            case 'config':
                $this->loadPluginConfigs($defaultPath, $customPath, $pluginSlug);
                break;
            case 'routes':
                $this->loadPluginRoutes($customPath, $defaultPath);
                break;
            case 'lang':
                $this->loadPluginTranslations($customPath, $defaultPath, $pluginSlug);
                break;
            case 'views':
                $this->loadPluginViews($customPath, $defaultPath, $pluginSlug);
                break;
            case 'migrations':
                $this->loadPluginMigrations($customPath, $defaultPath);
                break;
            default:
                $this->loadCustomFiles($type, $defaultPath, $customPath, $pluginSlug);
        }
    }

    /**
     * コンフィグの読み込み
     */
    protected function loadPluginConfigs($corePath, $customPath, $pluginSlug)
    {

        // プラグインの設定を個別の名前空間に格納
        $pluginConfigs = $this->loadConfigFiles($corePath);
        $customConfigs = $this->loadConfigFiles($customPath);

        foreach ($customConfigs as $key => $customConfig) {
            if (isset($pluginConfigs[$key])) {
                $pluginConfigs[$key] = array_merge_recursive($pluginConfigs[$key], $customConfig);
            } else {
                $pluginConfigs[$key] = $customConfig;
            }
        }

        // `users-plugin.auth` のようにプレフィックス付きで登録
        foreach ($pluginConfigs as $key => $value) {
            config(["{$pluginSlug}.{$key}" => $value]);
        }

        // admin.php ファイルが存在する場合、ナビゲーションをマージ
        $adminConfigFile = $corePath . '/admin.php';
        if (file_exists($adminConfigFile)) {
            $this->mergeAdminNavConfig($adminConfigFile);
        }

        // カスタムのadmin.phpファイルも確認
        $customAdminConfigFile = $customPath . '/admin.php';
        if (file_exists($customAdminConfigFile)) {
            $this->mergeAdminNavConfig($customAdminConfigFile);
        }
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

        //$translations = Lang::getLoader()->load(app()->getLocale(), 'admin');
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
        $defaultProvider = "Plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginDirectory}ServiceProvider";
        $customProvider = "custom\\plugins\\{$pluginDirectory}\\App\\Providers\\{$pluginDirectory}ServiceProvider";

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

    /**
     * 任意のプラグイン設定をマージする
     *
     * @param string $configFile プラグインの config ファイルのパス
     * @param string $configKey config() に格納するキー (例: 'auth', 'admin.nav')
     */
    public function mergePluginConfig($configFile, $configKey)
    {
        if (!file_exists($configFile)) {
            return; // 設定ファイルが存在しない場合はスキップ
        }

        $pluginConfig = require $configFile;

        if (!is_array($pluginConfig)) {
            return; // 無効な設定ファイルの場合はスキップ
        }

        // 既存の設定を取得
        $existingConfig = config($configKey, []);

        // カスタムの再帰マージ関数を使って統合
        $mergedConfig = $this->recursiveArrayMergeOverwrite($existingConfig, $pluginConfig);

        // マージした設定を適用
        config([$configKey => $mergedConfig]);
    }

    /**
     * 管理画面のナビゲーション (`admin.nav`) をマージする
     *
     * @param string $configFile プラグインのナビゲーション設定ファイル
     */
    public function mergeAdminNavConfig($configFile)
    {
        if (!file_exists($configFile)) {
            return; // 設定ファイルが存在しない場合はスキップ
        }

        $pluginConfig = require $configFile;

        if (!isset($pluginConfig['nav']) || !is_array($pluginConfig['nav'])) {
            return; // 無効な設定ファイルの場合はスキップ
        }

        foreach ($pluginConfig['nav'] as $key => $value) {
            if (isset($value['_insert_before'])) {
                $this->insertOrderedConfig('admin.nav', $key, $value, $value['_insert_before'], 'before');
            } elseif (isset($value['_insert_after'])) {
                $this->insertOrderedConfig('admin.nav', $key, $value, $value['_insert_after'], 'after');
            } else {
                // 既存のキーがある場合はマージ、ない場合は追加
                $existingValue = config("admin.nav.{$key}");
                if ($existingValue !== null && is_array($existingValue)) {
                    // 既存の設定がある場合、childrenのみをマージし、他のプロパティは保持
                    if (isset($value['children']) && is_array($value['children'])) {
                        $existingChildren = $existingValue['children'] ?? [];
                        $existingValue['children'] = array_merge($existingChildren, $value['children']);
                    }
                    // 他のプロパティ（text, iconなど）は既存の設定を保持
                    config(["admin.nav.{$key}" => $existingValue]);
                } else {
                    // 新規追加
                    config(["admin.nav.{$key}" => $value]);
                }
            }
        }
    }


    /**
     * 配列を再帰的にマージする（同じキーがある場合は上書き）
     *
     * @param array $base 元の設定
     * @param array $override 追加の設定
     * @return array マージ後の配列
     */
    protected function recursiveArrayMergeOverwrite(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // 配列同士なら再帰的にマージ
                $base[$key] = $this->recursiveArrayMergeOverwrite($base[$key], $value);
            } else {
                // 配列でない場合は上書き
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * 指定されたキーの前後に要素を挿入する（ナビゲーション専用）
     *
     * @param string $configKey config() に格納するキー
     * @param string $insertKey 挿入するキー
     * @param array $insertValue 挿入するデータ
     * @param string $targetKey どのキーの前後に挿入するか
     * @param string $position 'before' or 'after'
     */
    protected function insertOrderedConfig($configKey, $insertKey, $insertValue, $targetKey, $position = 'before')
    {
        unset($insertValue['_insert_before'], $insertValue['_insert_after']); // `_insert_before` や `_insert_after` を削除

        $existingConfig = config($configKey, []);

        // 新しい配列を作成し、適切な位置に要素を挿入
        $newConfig = [];
        $inserted = false;

        foreach ($existingConfig as $key => $value) {
            if ($position === 'before' && $key === $targetKey) {
                // 指定されたキーの前に挿入
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }

            $newConfig[$key] = $value;

            if ($position === 'after' && $key === $targetKey) {
                // 指定されたキーの後に挿入
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }
        }

        // `_insert_before` や `_insert_after` に該当するキーがない場合は最後に追加
        if (!$inserted) {
            $newConfig[$insertKey] = $insertValue;
        }

        config([$configKey => $newConfig]);
    }

    /**
     * 管理画面のナビゲーション設定をマージ
     * 
     * @param string $pluginName プラグイン名（デバッグ用）
     * @param string $configPath 設定ファイルのパス
     */
    protected function mergeAdminNavigation(string $pluginName = 'Unknown', string $configPath = null): void
    {
        // AdminHelperの共通メソッドを使用
        \App\Helpers\AdminHelper::mergeAdminNavigation($pluginName, $configPath);
    }
}
