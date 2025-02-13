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


namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\BaseSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use App\Models\SecuritySetting;
use App\Traits\ThemeLoaderTrait;
use App\Traits\PluginLoaderTrait;
use App\Traits\CustomFilesLoaderTrait;
use Illuminate\Support\Facades\Lang;
use Illuminate\Filesystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{

    use ThemeLoaderTrait;
    use PluginLoaderTrait;
    use CustomFilesLoaderTrait;


    /**
     * Register any application services.
     */
    public function register(): void
    {

        // カスタムディレクトリ全体をスキャン。ファイルが存在する場合、それを優先してバインド
        $customFilesPath = base_path(config('app.custom_files_dir', 'custom'));
        Config::set('custom_files_dir', $customFilesPath);
    }

    /**
     * Bootstrap any application services.
     */

    public function boot()
    {

        try {
            $this->loadActivePlugins();
        } catch (\Exception $e) {
            // エラーを無視（開発時のみ）
            if (app()->environment('local')) {
                report($e);
            } else {
                throw $e;
            }
        }

        //セキュリティ設定でSSLを矯正しているかどうかを判定

        $forceSsl = SecuritySetting::get('force_ssl', config('security.force_ssl'));

        if ($forceSsl) {
            $this->app['request']->server->set('HTTPS', true);
            URL::forceRootUrl(Config::get('app.url')); // ルートURLを設定
            URL::forceScheme('https');
        }


        //言語の設定
        $language = BaseSetting::where('name', 'language')->value('value');
        $lang = $language ?? config('admin.lang', 'ja');
        app()->setLocale($lang);


        // テーマの設定を読み込む
        $adminTheme = config('themes.admin_theme', 'admin'); // 管理画面テーマ
        $themeDirectory = config('themes.theme_directory', 'themes'); // テーマディレクトリ
        $activeThemeDirectory = config('themes.active_theme', 'default-theme'); // アクティブなテーマ
        $defaultTheme = config('themes.default_theme', 'default-theme'); // デフォルトテーマ


        // カスタムファイルのディレクトリを追加
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            base_path("{$customFilesDir}/resources/views/components"), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]);

        // 共通レイアウトの名前空間
        View::addNamespace('layouts', [
            base_path("{$customFilesDir}/resources/views/layouts"), // カスタムレイアウトを優先
            resource_path('views/layouts'),       // デフォルトレイアウト
        ]);



        // 現在有効化されているテーマを取得
        $activeThemeId = $this->getActiveTheme();

        // 現在使用中のテーマのディレクトリ名を取得
        $activeTheme = DB::table('themes')->where('id', $activeThemeId)->first();
        $activeThemeDirectory = $activeTheme->directory ?? 'DefaultTheme';


        // テーマファイルの読み込み、カスタムテーマの読み込みがcustom/resources/viewsのほうを優先されるように設定
        View::addNamespace('themes', [
            base_path("{$customFilesDir}/{$themeDirectory}/{$activeThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$activeThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$defaultTheme}/resources/views"),
        ]);

        // カスタムファイルのディレクトリを追加
        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $fileTypes = config('custom.custom_file_types');

        // プラグインロード後にカスタムファイルをロード
        $this->app->booted(function () use ($customFilesPath, $fileTypes) {

            // プラグインのロード
            $this->loadActivePlugins();

            // カスタムファイルのロード
            foreach ($fileTypes as $type => $typeConfig) {
                $this->loadCustomFilesForType($customFilesPath, $typeConfig);
            }
        });
    }
}
