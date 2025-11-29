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

namespace App\Helpers;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PluginHelper
{
    /**
     * 有効化されているプラグイン一覧を取得
     *
     * @return Collection
     */
    public static function getEnabledPlugins(): Collection
    {
        try {
            if (!Schema::hasTable('plugins')) {
                return collect();
            }
            
            return Plugin::enabled()->get();
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to get enabled plugins', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * プラグインが有効化されているか確認
     *
     * @param string $slug プラグインのスラッグ
     * @return bool
     */
    public static function isEnabled(string $slug): bool
    {
        try {
            if (!Schema::hasTable('plugins')) {
                return false;
            }
            
            return Plugin::where('slug', $slug)->where('is_enabled', true)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * プラグインのパスを取得
     *
     * @param string $directory プラグインのディレクトリ名
     * @return string
     */
    public static function getPluginPath(string $directory): string
    {
        return base_path("plugins/{$directory}");
    }

    /**
     * 有効化されているプラグインの管理画面ルートを読み込む
     * 
     * このメソッドはroutes/admin.php内の認証済みルートグループ内で呼び出される
     * ことを想定しています。これにより、プラグインのルートにも認証ミドルウェアが
     * 自動的に適用されます。
     *
     * @return void
     */
    public static function loadEnabledAdminRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $adminRoutePath = self::getPluginPath($plugin->directory) . '/routes/admin.php';
                
                if (File::exists($adminRoutePath)) {
                    Log::debug('PluginHelper: Loading admin routes', [
                        'plugin' => $plugin->directory,
                        'path' => $adminRoutePath,
                    ]);
                    
                    include $adminRoutePath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin admin routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 有効化されているプラグインのWebルートを読み込む
     * 
     * このメソッドはroutes/web.php内で呼び出されることを想定しています。
     *
     * @return void
     */
    public static function loadEnabledWebRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $webRoutePath = self::getPluginPath($plugin->directory) . '/routes/web.php';
                
                if (File::exists($webRoutePath)) {
                    Log::debug('PluginHelper: Loading web routes', [
                        'plugin' => $plugin->directory,
                        'path' => $webRoutePath,
                    ]);
                    
                    include $webRoutePath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin web routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 有効化されているプラグインのAPIルートを読み込む
     * 
     * このメソッドはroutes/api.php内またはServiceProviderで呼び出されることを想定しています。
     *
     * @return void
     */
    public static function loadEnabledApiRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $apiRoutePath = self::getPluginPath($plugin->directory) . '/routes/api.php';
                
                if (File::exists($apiRoutePath)) {
                    Log::debug('PluginHelper: Loading API routes', [
                        'plugin' => $plugin->directory,
                        'path' => $apiRoutePath,
                    ]);
                    
                    include $apiRoutePath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin API routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * ショートコードを登録
     * 
     * プラグインのServiceProviderから呼び出して使用します。
     * 
     * 使用例:
     *   PluginHelper::registerShortcode('menu', MenuShortcode::class);
     *
     * @param string $name ショートコード名
     * @param string $class ショートコードクラス名
     * @return bool 登録成功したかどうか
     */
    public static function registerShortcode(string $name, string $class): bool
    {
        try {
            if (app()->bound('shortcode')) {
                $shortcode = app('shortcode');
                $shortcode->add($name, $class);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to register shortcode', [
                'name' => $name,
                'class' => $class,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 複数のショートコードを一括登録
     * 
     * 使用例:
     *   PluginHelper::registerShortcodes([
     *       'menu' => MenuShortcode::class,
     *       'submenu' => SubMenuShortcode::class,
     *   ]);
     *
     * @param array $shortcodes ['name' => 'ClassName'] の配列
     * @return int 登録成功した数
     */
    public static function registerShortcodes(array $shortcodes): int
    {
        $count = 0;
        foreach ($shortcodes as $name => $class) {
            if (self::registerShortcode($name, $class)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * リンクソースを登録（メニュープラグイン用）
     * 
     * メニュープラグインのMenuLinkSourceManagerにリンクソースを登録します。
     * 
     * 使用例:
     *   PluginHelper::registerLinkSource(new PageLinkSource());
     *
     * @param object $source リンクソースインスタンス
     * @return bool 登録成功したかどうか
     */
    public static function registerLinkSource(object $source): bool
    {
        try {
            $managerClass = 'Plugins\\DixlaseMenu\\App\\Services\\MenuLinkSourceManager';
            
            if (app()->bound($managerClass)) {
                $manager = app($managerClass);
                $manager->register($source);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to register link source', [
                'source' => get_class($source),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 複数のリンクソースを一括登録
     * 
     * 使用例:
     *   PluginHelper::registerLinkSources([
     *       new PageLinkSource(),
     *       new PostLinkSource(),
     *   ]);
     *
     * @param array $sources リンクソースインスタンスの配列
     * @return int 登録成功した数
     */
    public static function registerLinkSources(array $sources): int
    {
        $count = 0;
        foreach ($sources as $source) {
            if (self::registerLinkSource($source)) {
                $count++;
            }
        }
        return $count;
    }
}
