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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use App\Models\SettingSecurity;
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

        /*
        // custom/app ディレクトリのファイルが存在する場合、それを優先してバインド
        $customPath = base_path('custom/app');
        $defaultPath = app_path();

        if (File::exists($customPath)) {
            $customFiles = File::allFiles($customPath);

            foreach ($customFiles as $file) {
                // クラス名を取得
                $relativePath = Str::replaceFirst($customPath . '/', '', $file->getPathname());
                $className = Str::replaceLast('.php', '', $relativePath);
                $className = str_replace('/', '\\', $className);

                // コンテナにクラスをバインド
                if (class_exists("Custom\\$className")) {
                    app()->bind("App\\$className", "Custom\\$className");
                }
            }
        }
        */
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


        // テーマの設定を読み込む
        $adminTheme = config('themes.admin_theme', 'admin'); // 管理画面テーマ
        $themeDirectory = config('themes.theme_directory', 'themes'); // テーマディレクトリ
        $activeThemeDirectory = config('themes.theme', 'default'); // アクティブなテーマ
        $defaultTheme = config('themes.default_theme', 'default'); // デフォルトテーマ

        // カスタムファイルのディレクトリを追加
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));


        // 現在有効化されているテーマを取得
        $activeThemeId = $this->getActiveTheme();

        // 現在使用中のテーマのディレクトリ名を取得
        $activeTheme = DB::table('themes')->where('id', $activeThemeId)->first();
        $activeThemeDirectory = $activeTheme->directory ?? 'default';

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // カスタムテーマの読み込みがcustom/resouces/viewsのほうを優先されるように設定
        View::addNamespace('themes', [
            base_path("{$customFilesDir}/resources/views/{$themeDirectory}/{$activeThemeDirectory}"),
            resource_path("views/{$themeDirectory}/{$activeThemeDirectory}"),
            resource_path("views/{$themeDirectory}/{$defaultTheme}"),
        ]);


        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            base_path("{$customFilesDir}/resources/views/components"), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
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


        /*
        $this->app->booted(function () {
            $customFilesPath = Config::get('custom_files_path');

            // カスタムコンフィグの読み込み
            $customConfigPath = base_path('custom/config');

            if (File::isDirectory($customConfigPath)) {
                foreach (File::allFiles($customConfigPath) as $file) {
                    $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    $config = Config::get($filename, []);
                    $customConfig = require $file->getPathname();

                    if (!is_array($customConfig)) {
                        throw new \UnexpectedValueException("Config file {$file->getPathname()} must return an array.");
                    }

                    // 配列を再帰的にマージ
                    $mergedConfig = array_merge_recursive_custom($config, $customConfig);

                    Config::set($filename, $mergedConfig);
                }
            }

            // カスタムルートの読み込み
            $customRoutesPath = base_path('custom/routes');
            if (File::isDirectory($customRoutesPath)) {
                foreach (File::allFiles($customRoutesPath) as $file) {
                    Route::middleware('web')
                        ->group($file->getPathname());
                }
            }

            // カスタム言語ファイルの読み込み

            $customLangPath = base_path('custom/lang');

            if (File::isDirectory($customLangPath)) {
                foreach (File::directories($customLangPath) as $localePath) {
                    $locale = basename($localePath);

                    foreach (File::allFiles($localePath) as $file) {
                        $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);



                        // デフォルトの翻訳を取得（存在しない場合は空配列）
                        $existing = Lang::getLoader()->load($locale, $group) ?? [];

                        // カスタム翻訳を読み込む
                        $custom = require $file->getPathname();

                        if (!is_array($custom)) {
                            throw new \UnexpectedValueException("Language file {$file->getPathname()} must return an array.");
                        }

                        // マージ処理
                        $merged = array_replace_recursive($existing, $custom);

                        // 多次元配列をフラット化
                        $flattened = Arr::dot($merged);

                        // 新規翻訳グループとして登録
                        foreach ($flattened as $key => $value) {
                            if (is_string($key) && is_string($value)) {
                                try {
                                    app('translator')->addLines([$group . '.' . $key => $value], $locale);
                                } catch (\Exception $e) {
                                    throw new \UnexpectedValueException("Error adding translation. Key: {$key}, Value: {$value}, Group: {$group}, Locale: {$locale}. Error: {$e->getMessage()}");
                                }
                            } else {
                                throw new \UnexpectedValueException("Invalid translation key or value in {$file->getPathname()}. Key: {$key}");
                            }
                        }
                    }
                }
            }
            // カスタムビューの読み込み
            View::addLocation(base_path('custom/resources/views'));







            // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
            View::addNamespace('admin', [
                base_path("custom/resources/views/{$adminTheme}"),
                resource_path("views/{$adminTheme}"),
            ]);

            // カスタムテーマの読み込みがcustom/resouces/viewsのほうを優先されるように設定
            View::addNamespace('themes', [
                base_path("custom/resources/views/{$themeDirectory}/{$activeThemeDirectory}"),
                resource_path("views/{$themeDirectory}/{$activeThemeDirectory}"),
                resource_path("views/{$themeDirectory}/{$defaultTheme}"),
            ]);


            // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
            View::addNamespace('components', [
                base_path('custom/resources/views/components'), // カスタムコンポーネントを優先
                resource_path('views/components'),       // デフォルトコンポーネント
            ]);



            // エラーページ用の探索順序を設定
            View::addNamespace('errors', [
                base_path('custom/resources/views/{$themeDirectory}/{$activeThemeDirectory}/errors'),
                resource_path('views/{$themeDirectory}/{$activeThemeDirectory}/errors'),
                resource_path('views/{$themeDirectory}/{$defaultTheme}/errors'),
            ]);

            // カスタムマイグレーションパスを追加
            $this->loadMigrationsFrom([
                database_path('migrations'),                // デフォルトマイグレーション
                base_path('custom/database/migrations'),    // カスタムマイグレーション
            ]);

        });
        */
    }
}
