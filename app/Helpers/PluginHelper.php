<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
     */
    public static function getEnabledPlugins(): Collection
    {
        try {
            if (! Schema::hasTable('plugins')) {
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
     * @param  string  $slug  プラグインのスラッグ
     */
    public static function isEnabled(string $slug): bool
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return false;
            }

            return Plugin::where('slug', $slug)->whereNotNull('enabled_at')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 有効化プラグインの capability 情報のランタイムキャッシュ
     *
     * @var array<string, array<int, string>>|null [slug => [capability, ...]]
     */
    private static ?array $enabledCapabilityCache = null;

    /**
     * ファイル存在プラグイン（有効化問わず）の capability 情報のランタイムキャッシュ
     *
     * @var array<string, array<int, string>>|null [directory => [capability, ...]]
     */
    private static ?array $installedCapabilityCache = null;

    /**
     * 有効化されているプラグインのうち、指定 capability を宣言するものがあるか
     *
     * plugin.json の `capabilities` 配列（例: ["seo", "backup"]）を走査します。
     * capabilities 未定義のプラグインは無視されます。
     *
     * @param  string  $capability  capability 識別子（例: 'seo'）
     */
    public static function hasCapability(string $capability): bool
    {
        $map = self::getEnabledCapabilityMap();

        foreach ($map as $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ファイルが追加されている（ディレクトリ存在）プラグインに、
     * 指定 capability を宣言するものがあるか
     *
     * 有効化されていなくても plugin.json さえあれば検出します。
     * 「追加済みだが未有効化」のプラグインを案内したい場合などに使用。
     *
     * @param  string  $capability  capability 識別子
     */
    public static function hasCapabilityInAnyInstalled(string $capability): bool
    {
        $map = self::getInstalledCapabilityMap();

        foreach ($map as $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 指定スラッグの有効化プラグインが指定 capability を宣言しているか
     *
     * 「プラグインごとに機能対応を確認したい」用途で使用します。
     * 例: SEOプラグインが「dixlase-pages が seo-meta に対応宣言しているか」確認
     *
     * @param  string  $slug  プラグインスラッグ（例: 'dixlase-pages'）
     * @param  string  $capability  capability 識別子（例: 'seo-meta'）
     */
    public static function pluginHasCapability(string $slug, string $capability): bool
    {
        $map = self::getEnabledCapabilityMap();

        return in_array($capability, $map[$slug] ?? [], true);
    }

    /**
     * 指定 capability を宣言している有効化プラグインのスラッグ一覧を取得
     *
     * SEOプラグインが「SEOメタに対応しているプラグイン一覧」を取得して
     * 連携設定 UI を自動生成する、といった用途で使用します。
     *
     * @param  string  $capability  capability 識別子（例: 'seo-meta'）
     * @return array<int, string> プラグインスラッグの配列
     */
    public static function getEnabledPluginSlugsByCapability(string $capability): array
    {
        $map = self::getEnabledCapabilityMap();
        $slugs = [];
        foreach ($map as $slug => $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * 指定ディレクトリのプラグイン/テーマが宣言する capabilities を取得
     *
     * plugins/{directory}/plugin.json または themes/{directory}/plugin.json の
     * `capabilities` 配列を読み取って返します。
     * 未宣言や plugin.json 自体がない場合は空配列。
     *
     * @return array<int, string>
     */
    public static function getCapabilitiesForDirectory(string $directory): array
    {
        $map = self::getInstalledCapabilityMap();
        if (isset($map[$directory])) {
            return $map[$directory];
        }

        // プラグインマップになければテーマディレクトリも確認
        $themePath = base_path("themes/{$directory}");
        $json = self::readPluginJson($themePath);

        return self::extractCapabilities($json);
    }

    /**
     * ランタイムキャッシュを破棄（主にテスト用）
     */
    public static function clearCapabilityCache(): void
    {
        self::$enabledCapabilityCache = null;
        self::$installedCapabilityCache = null;
    }

    /**
     * 有効化プラグインの capability マップを取得
     *
     * @return array<string, array<int, string>>
     */
    private static function getEnabledCapabilityMap(): array
    {
        if (self::$enabledCapabilityCache !== null) {
            return self::$enabledCapabilityCache;
        }

        $map = [];
        foreach (self::getEnabledPlugins() as $plugin) {
            $json = self::readPluginJson(self::getPluginPath($plugin->directory));
            $map[$plugin->slug] = self::extractCapabilities($json);
        }

        return self::$enabledCapabilityCache = $map;
    }

    /**
     * ファイル存在プラグインの capability マップを取得
     *
     * @return array<string, array<int, string>>
     */
    private static function getInstalledCapabilityMap(): array
    {
        if (self::$installedCapabilityCache !== null) {
            return self::$installedCapabilityCache;
        }

        $map = [];
        $pluginsDir = base_path('plugins');
        if (! File::isDirectory($pluginsDir)) {
            return self::$installedCapabilityCache = $map;
        }

        foreach (File::directories($pluginsDir) as $dir) {
            $json = self::readPluginJson($dir);
            if ($json === null) {
                continue;
            }
            $map[basename($dir)] = self::extractCapabilities($json);
        }

        return self::$installedCapabilityCache = $map;
    }

    /**
     * plugin.json を読み取る
     *
     * @return array<string, mixed>|null
     */
    private static function readPluginJson(string $pluginPath): ?array
    {
        $jsonPath = $pluginPath.'/plugin.json';
        if (! File::exists($jsonPath)) {
            return null;
        }

        try {
            $data = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (\JsonException $e) {
            Log::warning('PluginHelper: Failed to parse plugin.json', [
                'path' => $jsonPath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * plugin.json から capabilities を抽出
     *
     * @param  array<string, mixed>|null  $json
     * @return array<int, string>
     */
    private static function extractCapabilities(?array $json): array
    {
        if ($json === null) {
            return [];
        }

        $capabilities = $json['capabilities'] ?? [];
        if (! is_array($capabilities)) {
            return [];
        }

        return array_values(array_filter($capabilities, 'is_string'));
    }

    /**
     * プラグインのパスを取得
     *
     * @param  string  $directory  プラグインのディレクトリ名
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
     */
    public static function loadEnabledAdminRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $adminRoutePath = self::getPluginPath($plugin->directory).'/routes/admin.php';

                if (File::exists($adminRoutePath)) {
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
     */
    public static function loadEnabledWebRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $webRoutePath = self::getPluginPath($plugin->directory).'/routes/web.php';

                if (File::exists($webRoutePath)) {
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
     */
    public static function loadEnabledApiRoutes(): void
    {
        // インストール前やテーブルが存在しない場合はスキップ
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $apiRoutePath = self::getPluginPath($plugin->directory).'/routes/api.php';

                if (File::exists($apiRoutePath)) {
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
     * @param  string  $name  ショートコード名
     * @param  string  $class  ショートコードクラス名
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
     * @param  array  $shortcodes  ['name' => 'ClassName'] の配列
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
     * @param  object  $source  リンクソースインスタンス
     * @return bool 登録成功したかどうか
     */
    public static function registerLinkSource(object $source): bool
    {
        try {
            $managerClass = 'Plugins\\DixlaseMenus\\App\\Services\\MenuLinkSourceManager';

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
     * @param  array  $sources  リンクソースインスタンスの配列
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
