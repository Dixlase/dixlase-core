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

namespace Themes\DixlaseDefaultTheme\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Themes\DixlaseDefaultTheme\App\Models\ThemeSetting;
use App\Helpers\AdminHelper;

class DixlaseDefaultThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge admin navigation config
        AdminHelper::mergeAdminNavigation(
            'DixlaseDefaultTheme',
            __DIR__ . '/../../config/admin.php'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'themes');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'themes');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Auto-seed theme settings if table exists but is empty
        $this->autoSeedThemeSettings();
        
        // Share theme settings with all views
        $this->shareThemeSettings();
    }
    
    /**
     * テーマ設定を自動的にシード
     */
    protected function autoSeedThemeSettings(): void
    {
        // コンソールコマンド実行時のみ、かつマイグレーション後にチェック
        if ($this->app->runningInConsole()) {
            // データベース接続を試みる
            try {
                // テーブルが存在し、かつデータが存在しない場合のみシード
                if (DB::getSchemaBuilder()->hasTable('thm_dixlase_default_theme_settings')) {
                    if (!DB::table('thm_dixlase_default_theme_settings')->exists()) {
                        Artisan::call('db:seed', [
                            '--class' => 'Themes\\DixlaseDefaultTheme\\Database\\Seeders\\DixlaseDefaultThemeSettingsSeeder'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // マイグレーション前など、テーブルがまだ存在しない場合は無視
            }
        }
    }
    
    /**
     * テーマ設定を全ビューに共有
     */
    protected function shareThemeSettings(): void
    {
        // View Composerを使用して、テーマのすべてのビューにテーマ設定を渡す
        // '*' はすべてのビュー（フロントエンドページ含む）
        View::composer('*', function ($view) {
            try {
                // ThemeSettingモデルで全設定を取得
                $themeSettings = ThemeSetting::getAllAsObject();
                
                // 設定が空の場合はデフォルト値を使用
                if (!isset($themeSettings->hero_main_title)) {
                    $themeSettings = $this->getDefaultThemeSettings();
                }
                
                $view->with('themeSettings', $themeSettings);
            } catch (\Exception $e) {
                // エラー時はデフォルト値を使用
                $view->with('themeSettings', $this->getDefaultThemeSettings());
            }
        });
    }
    
    /**
     * デフォルトのテーマ設定を取得
     */
    protected function getDefaultThemeSettings(): object
    {
        return (object) [
            'header_logo_id' => null,
            'favicon_id' => null,
            'hero_background_image_id' => null,
            'hero_main_title' => 'Welcome to ' . config('app.name', 'Dixlase'),
            'hero_sub_title' => 'Modern CMS Platform for Building Amazing Websites',
            'hero_button_text' => 'Get Started',
            'hero_button_link' => '#',
            'hero_button_secondary_text' => 'Learn More',
            'hero_button_secondary_link' => '#features',
            'footer_description' => 'Powered by Dixlase CMS',
            'footer_links' => '[]',
            'footer_copyright' => '© ' . date('Y') . ' ' . config('app.name', 'Dixlase') . '. All rights reserved.',
            'footer_sns_instagram' => null,
            'footer_sns_x' => null,
            'footer_sns_facebook' => null,
            'footer_sns_tiktok' => null,
            'footer_sns_bluesky' => null,
            'footer_sns_threads' => null,
            'footer_sns_linkedin' => null,
            'footer_sns_youtube' => null,
            'footer_sns_pinterest' => null,
            'footer_sns_discord' => null,
            'primary_color' => '#3b82f6',
            'secondary_color' => '#6b7280',
            'accent_color' => '#10b981',
        ];
    }
}
