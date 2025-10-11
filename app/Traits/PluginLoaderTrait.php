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
    public function loadActivePlugins()
    {
        // コマンドライン引数から直接チェック
        $pluginManagementFlag = $this->getUninstallingPluginFromArgs();
        \Log::info("PluginLoaderTrait: loadActivePlugins called", [
            'plugin_management_flag' => $pluginManagementFlag,
            'argv' => $_SERVER['argv'] ?? 'not_available'
        ]);

        // プラグイン管理コマンド実行中はプラグインローダーをスキップ
        if ($pluginManagementFlag === 'PLUGIN_MANAGEMENT_COMMAND') {
            \Log::info("PluginLoaderTrait: Skipping plugin loading during plugin management command");
            return;
        }

        //Pluginテーブルのstatusが1のレコードを取得
        //テーブルが存在しているか確認
        if (Schema::hasTable('plugins')) {
        } else {
            $activePlugins = [];
        }

        $plugins = Plugin::where('status', 1)->get();
        \Log::info("PluginLoaderTrait: Found active plugins", [
            'count' => $plugins->count(),
            'plugins' => $plugins->pluck('name')->toArray()
        ]);

        foreach ($plugins as $plugin) {
            $pluginName = $plugin->name;
            $pluginDirectory = $plugin->directory;
            $pluginSlug = $plugin->slug;
            $pluginPath = base_path('plugins/' . $pluginDirectory);
            $customPluginPath = base_path('custom/plugins/' . $pluginDirectory);

            \Log::info("PluginLoaderTrait: Processing plugin", [
                'plugin_name' => $pluginName
            ]);

            // プラグインのファイルをロード
            $this->loadPluginFiles($pluginName, $pluginPath, $customPluginPath, $pluginSlug);


            // サービスプロバイダの登録 (プラグインのファイルをロードした後)
            $providerClass = $this->resolvePluginServiceProvider($pluginName, $pluginDirectory);

            \Log::info("PluginLoaderTrait: ServiceProvider resolution", [
                'plugin_name' => $pluginName,
                'provider_class' => $providerClass,
                'will_register' => !empty($providerClass)
            ]);

            if ($providerClass) {
                \Log::info("PluginLoaderTrait: Registering ServiceProvider", [
                    'plugin_name' => $pluginName,
                    'provider_class' => $providerClass
                ]);
                $this->app->register($providerClass);
                \Log::info("PluginLoaderTrait: ServiceProvider registered successfully", [
                    'plugin_name' => $pluginName
                ]);
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
                // 直接追加
                config(["admin.nav.{$key}" => $value]);
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
        // デバッグ: 旧実装と新実装を比較
        $useNewImplementation = true; // falseにすると旧実装を使用
        
        if ($useNewImplementation) {
            // AdminHelperの共通メソッドを使用
            \App\Helpers\AdminHelper::mergeAdminNavigation($pluginName, $configPath);
            return;
        }
        
        // 以下は旧実装（デバッグ用）
        /*
        \Log::info("=== {$pluginName}: mergeAdminNavigation START ===", [
            'timestamp' => now()->toDateTimeString(),
            'config_path' => $configPath
        ]);
        
        $configFile = $configPath ?? __DIR__ . '/../../config/admin.php';
        
        if (!file_exists($configFile)) {
            \Log::info("{$pluginName}: Config file not found", ['path' => $configFile]);
            return;
        }

        $pluginConfig = require $configFile;
        
        if (!isset($pluginConfig['nav']) || !is_array($pluginConfig['nav'])) {
            \Log::info("{$pluginName}: No nav config found");
            return;
        }

        // 既存のナビゲーション設定を取得
        $existingNav = config('admin.nav', []);
        
        // コアの設定が正しく読み込まれているかチェック
        if (!isset($existingNav['settings']['children']) || 
            !isset($existingNav['settings']['children']['base']) ||
            !isset($existingNav['settings']['children']['security'])) {
            
            \Log::warning("{$pluginName}: Core admin.nav settings missing, forcing reload from config file");
            
            // コアの設定ファイルを直接読み込み
            $coreConfigPath = config_path('admin.php');
            if (file_exists($coreConfigPath)) {
                $coreConfig = require $coreConfigPath;
                if (isset($coreConfig['nav'])) {
                    // コアの設定で初期化
                    $existingNav = $coreConfig['nav'];
                    config(['admin.nav' => $existingNav]);
                    \Log::info("{$pluginName}: Core admin.nav settings reloaded", [
                        'core_settings_children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'none'
                    ]);
                }
            }
        }
        
        \Log::info("{$pluginName}: Before merge", [
            'existing_nav_keys' => array_keys($existingNav),
            'plugin_nav_keys' => array_keys($pluginConfig['nav']),
            'existing_nav_full' => $existingNav,
            'plugin_nav_full' => $pluginConfig['nav'],
            'existing_settings' => isset($existingNav['settings']) ? [
                'full_structure' => $existingNav['settings'],
                'keys' => array_keys($existingNav['settings']),
                'children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'no_children'
            ] : 'not_exists'
        ]);
        
        // プラグインのナビゲーション設定をマージ
        foreach ($pluginConfig['nav'] as $key => $value) {
            \Log::info("{$pluginName}: Processing nav key '{$key}'", [
                'has_children' => isset($value['children']),
                'existing_key_exists' => isset($existingNav[$key])
            ]);
            
            // _insert_after や _insert_before は無視して直接追加
            unset($value['_insert_after'], $value['_insert_before']);
            
            // 既存の設定がある場合は子項目をマージ
            if (isset($existingNav[$key])) {
                \Log::info("{$pluginName}: Merging with existing key '{$key}'", [
                    'existing_structure' => $existingNav[$key],
                    'plugin_structure' => $value
                ]);
                
                // 既存の設定を保持しつつ、子項目をマージ
                if (isset($value['children']) && isset($existingNav[$key]['children'])) {
                    \Log::info("{$pluginName}: Merging children for '{$key}'", [
                        'existing_children_keys' => array_keys($existingNav[$key]['children']),
                        'existing_children_full' => $existingNav[$key]['children'],
                        'new_children_keys' => array_keys($value['children']),
                        'new_children_full' => $value['children']
                    ]);
                    $existingNav[$key]['children'] = array_merge($existingNav[$key]['children'], $value['children']);
                    \Log::info("{$pluginName}: After children merge for '{$key}'", [
                        'merged_children' => $existingNav[$key]['children']
                    ]);
                } elseif (isset($value['children'])) {
                    \Log::info("{$pluginName}: Adding children to existing '{$key}' (no existing children)", [
                        'new_children' => array_keys($value['children']),
                        'existing_had_children' => isset($existingNav[$key]['children'])
                    ]);
                    $existingNav[$key]['children'] = $value['children'];
                }
                
                // その他のプロパティは既存の設定を優先（プラグインでは上書きしない）
                foreach ($value as $prop => $propValue) {
                    if ($prop !== 'children') {
                        \Log::info("{$pluginName}: Skipping property '{$prop}' for '{$key}' (existing setting preserved)");
                    }
                }
            } else {
                \Log::info("{$pluginName}: Adding new nav key '{$key}'");
                // 新しい項目として追加
                $existingNav[$key] = $value;
            }
        }
        
        \Log::info("{$pluginName}: After merge", [
            'final_nav_keys' => array_keys($existingNav),
            'final_nav_full' => $existingNav,
            'final_settings' => isset($existingNav['settings']) ? [
                'full_structure' => $existingNav['settings'],
                'keys' => array_keys($existingNav['settings']),
                'children' => isset($existingNav['settings']['children']) ? [
                    'keys' => array_keys($existingNav['settings']['children']),
                    'full' => $existingNav['settings']['children']
                ] : 'no_children'
            ] : 'not_exists'
        ]);
        
        // 設定を更新
        config(['admin.nav' => $existingNav]);
        
        // マージ後の全体構造を詳細出力
        \Log::info("=== {$pluginName}: COMPLETE NAVIGATION STRUCTURE AFTER MERGE ===", [
            'timestamp' => now()->toDateTimeString(),
            'complete_structure' => $existingNav
        ]);
        
        // 特に設定項目の詳細を出力
        if (isset($existingNav['settings'])) {
            \Log::info("=== {$pluginName}: SETTINGS SECTION DETAIL ===", [
                'settings_full' => $existingNav['settings'],
                'settings_children_count' => isset($existingNav['settings']['children']) ? count($existingNav['settings']['children']) : 0,
                'settings_children_keys' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : []
            ]);
        }
        
        \Log::info("=== {$pluginName}: mergeAdminNavigation END ===", [
            'timestamp' => now()->toDateTimeString()
        ]);
        */
    }
}
