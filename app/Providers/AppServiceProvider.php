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

namespace App\Providers;

use Config;
use Illuminate\Support\ServiceProvider;
use URL;
use Illuminate\Support\Facades\View;
use App\Models\SettingSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    /*
    public function boot()
    {
        URL::forceScheme('https');
    }
    */

    public function boot()
    {
        //$this->app['request']->server->set('HTTPS', true);
        //URL::forceRootUrl(Config::get('app.url'));// ルートURLを設定
        //$url->forceScheme('https');


        //言語の設定
        $language = SettingSystem::where('name', 'language')->value('value');
        $lang = $language ?? config('admin.lang', 'ja');
        app()->setLocale($lang);

        // 有効なプラグインをデータベースから取得

        $enabledPlugins = DB::table('plugins')->where('status', 'enabled')->get();

        // プラグインのServiceProviderを登録
        foreach ($enabledPlugins as $plugin) {
            $pluginPath = base_path('plugins/' . $plugin->name . '/src');
            if (File::exists($pluginPath)) {
                // プラグインのServiceProviderをロード
                $provider = $plugin->namespace . '\\' . $plugin->name . 'ServiceProvider';
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }


        // Commonコンポーネントの名前空間を設定
        //Blade::componentNamespace('App\\View\\Components', 'common');


        // 現在使用中のテーマ名を取得
        $activeTheme = DB::table('themes')->where('is_active', true)->first();

        // 現在使用中のテンプレートのスラッグ名を取得
        $templateSlug = $activeTheme ? $activeTheme->slug : 'default';

        // テーマの設定を読み込む
        $adminTheme = config('app.admin_theme', 'admin');
        $themeDirectory = config('app.theme_directory', 'themes');
        $currentTheme = $activeTheme ? $templateSlug : config('app.default_theme', 'default');

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            resource_path("views_custom/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // カスタムテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('themes', [
            resource_path("views_custom/{$themeDirectory}/{$currentTheme}"),
            resource_path("views/{$themeDirectory}/{$currentTheme}"),
        ]);

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            resource_path('views_custom/components'), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]);

        // エラーページ用の探索順序を設定
        View::addNamespace('errors', [
            resource_path("views_custom/{$themeDirectory}/{$currentTheme}/errors"),
            resource_path("views/{$themeDirectory}/{$currentTheme}/errors"),
        ]);
    }
}
