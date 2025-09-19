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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Enums\AppearanceMode;

class AppServiceProvider extends ServiceProvider
{

    use ThemeLoaderTrait;
    use PluginLoaderTrait;
    use CustomFilesLoaderTrait;


    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */

    public function boot()
    {
        // .envファイルが存在しない場合やデータベース接続ができない場合はスキップ
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return;
        }

        try {
            //セキュリティ設定でSSLを矯正しているかどうかを判定
            //security_settingsテーブルのforce_sslの値を取得
            //テーブルが存在しているか確認

            if (Schema::hasTable('security_settings')) {
                $forceSsl = SecuritySetting::get('force_ssl', config('security.force_ssl'));
            } else {
                $forceSsl = config('security.force_ssl');
            }

            if ($forceSsl) {
                $this->app['request']->server->set('HTTPS', true);
                URL::forceRootUrl(Config::get('app.url')); // ルートURLを設定
                URL::forceScheme('https');
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合はログに記録してスキップ
            \Log::warning('AppServiceProvider boot error (likely during installation): ' . $e->getMessage());
            return;
        }

        // 言語の設定
        // インストール中はセッション/クッキーの言語を優先
        $request = $this->app['request'];
        $language = null;
        
        // 有効なロケールのリスト
        $availableLocales = array_keys(config('language.languages', ['en' => 'English']));
        
        if ($request && $request->is('install*')) {
            // セッションの値（StartSessionより前でも取得できる場合がある）、なければクッキー
            $sessionLocale = session()->get('install_locale');
            $cookieLocale = $request->cookie('install_locale');
            
            // 有効なロケールのみを許可
            if ($sessionLocale && in_array($sessionLocale, $availableLocales)) {
                $language = $sessionLocale;
            } elseif ($cookieLocale && in_array($cookieLocale, $availableLocales)) {
                $language = $cookieLocale;
            }
        }

        // それでも未設定なら環境変数→DB→configの順で決定
        if (!$language) {
            // 環境変数を直接優先（.envの設定を最優先）
            $language = env('APP_LOCALE') ?? config('app.locale', 'en');
            
            // 有効なロケールかチェック
            if (!in_array($language, $availableLocales)) {
                $language = 'en'; // デフォルトにフォールバック
            }
        }
        app()->setLocale($language);


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
        $activeThemeDirectory = $this->getActiveThemeDirectory();

        // テーマファイルの読み込み、カスタムテーマの読み込みがcustom/resources/viewsのほうを優先されるように設定
        View::addNamespace('themes', [
            base_path("{$customFilesDir}/{$themeDirectory}/{$activeThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$activeThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$defaultTheme}/resources/views"),
        ]);

        // カスタムファイルのディレクトリを追加
        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $fileTypes = config('custom.file_types');

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
