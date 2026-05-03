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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Traits;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Services\Site\SettingResolver;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * 管理画面の共通インターフェース初期化トレイト
 */
trait AdminInterfaceTrait
{
    // 変数宣言
    protected $siteName;

    protected $heading = '';

    protected $viewParams = [];

    protected $routeName = '';

    protected $settings = [];

    /**
     * 初期化処理
     */
    public function initialize()
    {
        // インストール前の場合はスキップ
        if (! file_exists(base_path('.env'))) {
            return;
        }

        // インストール済みかどうかをチェック（config経由で取得することでキャッシュに対応）
        $isInstalled = config('app.installed', false) ?: env('INSTALLED', false);
        if (! $isInstalled) {
            return;
        }

        try {
            if (! Schema::hasTable('site_settings') || ! Schema::hasTable('global_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $this->getSiteName();
        $this->getSiteSettings();
        $this->setRouteName();
        $this->setHeading();
    }

    protected function getSiteName()
    {
        // env > config > resolver (PerSite via SettingResolver) > fallback
        $this->siteName = env('APP_NAME')
            ?? config('app.name')
            ?? app(SettingResolver::class)->get('site_name')
            ?? 'Dixlase';
        $this->viewParams['site_name'] = $this->siteName;
    }

    protected function getSiteSettings()
    {
        // Repository::all() iterates the SettingDefinitionRegistry and
        // resolves each value via SettingResolver. The result is a
        // flat [key => value] map (Global + PerSite + Overridable).
        $this->settings = app(SiteSettingRepositoryInterface::class)->all();
        $this->viewParams['settings'] = $this->settings;
    }

    protected function setRouteName()
    {
        $this->routeName = Route::currentRouteName();
        $this->viewParams['route_name'] = $this->routeName;
    }

    protected function setHeading()
    {
        $routeName = Route::currentRouteName();

        if ($routeName === null) {
            $this->viewParams['heading'] = '';

            return;
        }

        // プラグインのルートかどうかを判別（::が含まれている場合はプラグイン）
        if (strpos($routeName, '::') !== false) {
            $this->heading = $this->resolvePluginHeadingKey($routeName);
        } else {
            $this->heading = $this->resolveCoreHeadingKey($routeName);
        }

        $this->viewParams['heading'] = $this->heading;
    }

    /**
     * コアの翻訳キーを解決する
     * ルート名から適切な翻訳ファイルパスとキーを自動判定
     *
     * @param  string  $routeName  ルート名（例: admin.settings.security.captcha）
     * @return string 翻訳キー（例: admin/settings/security/captcha.heading）
     */
    protected function resolveCoreHeadingKey(string $routeName): string
    {
        // admin.controller.action → ['controller', 'action']
        $keys = explode('.', $routeName);
        array_shift($keys); // 'admin'を除去

        if (empty($keys)) {
            return 'admin/dashboard.heading';
        }

        // パターン1: 完全パス（例: admin/settings/security/captcha.heading）
        $fullPath = 'admin/'.implode('/', $keys).'.heading';
        if (Lang::has($fullPath)) {
            return $fullPath;
        }

        // パターン2: ディレクトリ構造の場合、index.phpを参照（例: admin/settings/systems/logs/index.heading）
        // admin.settings.systems.logs → admin/settings/systems/logs/index.heading
        $indexPath = 'admin/'.implode('/', $keys).'/index.heading';
        if (Lang::has($indexPath)) {
            return $indexPath;
        }

        // パターン3: 親ディレクトリのindex.phpを参照（例: admin.settings.systems.logs.files → admin/settings/systems/logs/index.heading）
        if (count($keys) >= 2) {
            $parentKeys = array_slice($keys, 0, -1);
            $parentIndexPath = 'admin/'.implode('/', $parentKeys).'/index.heading';
            if (Lang::has($parentIndexPath)) {
                return $parentIndexPath;
            }
        }

        // パターン4: 最後の要素がファイル内のキー（例: admin/media.index.heading）
        if (count($keys) >= 2) {
            $keysCopy = $keys;
            $lastKey = array_pop($keysCopy);
            $filePath = 'admin/'.implode('/', $keysCopy).'.'.$lastKey.'.heading';
            if (Lang::has($filePath)) {
                return $filePath;
            }
        }

        // パターン5: 単一ファイルで直接heading（例: admin/dashboard.heading）
        if (count($keys) === 1) {
            $singlePath = 'admin/'.$keys[0].'.heading';
            if (Lang::has($singlePath)) {
                return $singlePath;
            }
        }

        // フォールバック: 最初に試したパスを返す
        return $fullPath;
    }

    /**
     * プラグインの翻訳キーを解決する
     *
     * @param  string  $routeName  ルート名（例: admin.dixlase-inquiry::admin.settings.index）
     * @return string 翻訳キー
     */
    protected function resolvePluginHeadingKey(string $routeName): string
    {
        // 新しい形式: admin.plugin-name::admin.controller.action
        if (strpos($routeName, 'admin.') === 0) {
            $withoutAdminPrefix = substr($routeName, 6); // 'admin.'を除去
            [$pluginNamespace, $route] = explode('::', $withoutAdminPrefix, 2);

            // プラグイン内でも同様のロジックを適用
            $keys = explode('.', $route);

            // パターン1: 完全パス
            $fullPath = implode('/', $keys).'.heading';
            if (Lang::has($pluginNamespace.'::'.$fullPath)) {
                return $pluginNamespace.'::'.$fullPath;
            }

            // パターン2: 最後の要素がファイル内のキー
            if (count($keys) >= 2) {
                $lastKey = array_pop($keys);
                $filePath = implode('/', $keys).'.'.$lastKey.'.heading';
                if (Lang::has($pluginNamespace.'::'.$filePath)) {
                    return $pluginNamespace.'::'.$filePath;
                }
            }

            // フォールバック
            return $pluginNamespace.'::'.$fullPath;
        }

        // 旧形式: plugin-name::admin.controller.action
        [$pluginNamespace, $route] = explode('::', $routeName, 2);
        $keys = explode('.', $route);

        // パターン1: 完全パス（directory-based）
        $fullPath = implode('/', $keys).'.heading';
        if (Lang::has($pluginNamespace.'::'.$fullPath)) {
            return $pluginNamespace.'::'.$fullPath;
        }

        // パターン2: 最後の要素がファイル内のキー
        if (count($keys) >= 2) {
            $keysCopy = $keys;
            $lastKey = array_pop($keysCopy);
            $filePath = implode('/', $keysCopy).'.'.$lastKey.'.heading';
            if (Lang::has($pluginNamespace.'::'.$filePath)) {
                return $pluginNamespace.'::'.$filePath;
            }
        }

        // フォールバック: 旧形式dot notation
        array_shift($keys); // 'admin'を除去
        $headingKey = implode('.', $keys).'.heading';

        return $pluginNamespace.'::admin.'.$headingKey;
    }
}
