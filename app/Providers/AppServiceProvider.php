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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\SettingSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use App\Models\SettingSecurity;


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

    public function boot()
    {

        //セキュリティ設定でSSLを矯正しているかどうかを判定
        $forceSsl = SettingSecurity::get('force_ssl', config('security.force_ssl'));

        if ($forceSsl) {
            $this->app['request']->server->set('HTTPS', true);
            URL::forceRootUrl(Config::get('app.url')); // ルートURLを設定
            URL::forceScheme('https');
        }

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

        //テーマ設定を読み込むヘルパーを読み込む
        require_once app_path('Helpers/ThemeHelper.php');

        // テーマの設定を読み込む
        $adminTheme = config('app.admin_theme', 'admin');
        $themeDirectory = config('app.theme_directory', 'themes');
        $activeThemeDirectory = config('app.theme', 'default');
        $defaultTheme = config('app.default_theme', 'default');


        // 現在使用中のテーマ名を取得
        $settingsTheme = DB::table('settings_theme')->first();
        $activeThemeId = $settingsTheme->active_theme_id ?? 0;

        // 現在使用中のテンプレートのディレクトリ名を取得
        $activeTheme = DB::table('themes')->where('id', $activeThemeId)->first();
        $activeThemeDirectory = $activeTheme->directory ?? 'default';

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            resource_path("views_custom/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);


        // カスタムテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('themes', [
            resource_path("views_custom/{$themeDirectory}/{$activeThemeDirectory}"),
            resource_path("views/{$themeDirectory}/{$activeThemeDirectory}"),
            resource_path("views/{$themeDirectory}/{$defaultTheme}"),
        ]);

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            resource_path('views_custom/components'), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]);


        // エラーページ用の探索順序を設定
        View::addNamespace('errors', [
            resource_path("views_custom/{$themeDirectory}/{$activeThemeDirectory}/errors"),
            resource_path("views/{$themeDirectory}/{$activeThemeDirectory}/errors"),
            resource_path("views/{$themeDirectory}/{$defaultTheme}/errors"),
        ]);
    }
}
