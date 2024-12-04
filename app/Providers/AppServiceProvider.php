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

        // Commonコンポーネントの名前空間を設定
        //Blade::componentNamespace('App\\View\\Components', 'common');


        // 現在使用中のテンプレート名を取得
        $activeTemplate = DB::table('themes')->where('is_active', true)->first();

        // 現在使用中のテンプレートのスラッグ名を取得
        $templateSlug = $activeTemplate ? $activeTemplate->slug : 'default';

        // テーマの設定を読み込む
        $adminTheme = config('app.admin_theme', 'admin');
        $currentTheme = $activeTemplate ? $templateSlug : config('app.default_theme', 'default_theme');
        $themeDirectory = config('app.theme_directory', 'themes');

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            resource_path("views_custom/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // カスタムテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('theme', [
            resource_path("views_custom/{$themeDirectory}/{$currentTheme}"),
            resource_path("views/{$themeDirectory}/{$currentTheme}"),
        ]);

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            resource_path('views_custom/components'), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]);
    }
}
