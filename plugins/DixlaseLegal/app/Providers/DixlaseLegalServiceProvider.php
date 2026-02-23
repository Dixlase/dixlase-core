<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Plugins\DixlaseLegal\App\Providers;

use App\Contracts\CspPolicyProvider;
use App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface;
use Illuminate\Support\ServiceProvider;
use Plugins\DixlaseLegal\App\Services\DixlaseLegalPrivacyPolicyProvider;

/**
 * プラグインのServiceProvider
 * 
 * CspPolicyProviderを実装することで、プラグインが必要とする
 * 外部リソースのCSPディレクティブを宣言できます。
 * 外部リソースが不要な場合は、implements CspPolicyProvider と
 * getCspDirectives() メソッドを削除してください。
 */
class DixlaseLegalServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 設定ファイルをマージ
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dixlase_legal.php',
            'dixlase_legal'
        );

        // PrivacyPolicyProviderInterface のバインド
        $this->app->singleton(
            PrivacyPolicyProviderInterface::class,
            DixlaseLegalPrivacyPolicyProvider::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // CSPポリシーの登録（外部リソースが必要な場合）
        $this->registerCspPolicy();

        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dixlase-legal');

        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-legal');

        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 注: ルート（routes/web.php, routes/admin.php）はPluginServiceProviderが自動読み込み

        // 公開可能なアセット
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/dixlase_legal.php' => config_path('dixlase_legal.php'),
            ], 'dixlase-legal-config');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/dixlase-legal'),
            ], 'dixlase-legal-views');
        }
    }

    /**
     * CSPポリシーを登録
     * 
     * 外部リソースが不要な場合は、このメソッドと
     * getCspDirectives() メソッドを削除してください。
     */
    protected function registerCspPolicy(): void
    {
        if (app()->bound(\App\Services\Csp\CspPolicyRegistry::class)) {
            app(\App\Services\Csp\CspPolicyRegistry::class)
                ->registerProvider('dixlase-legal', $this);
        }
    }

    /**
     * CSPディレクティブを取得
     * 
     * プラグインが必要とする外部リソースのドメインを指定します。
     * 不要な場合は空の配列を返すか、このメソッドを削除してください。
     * 
     * @return array<string, array<string>>
     */
    public function getCspDirectives(): array
    {
        return [
            // 例: 外部スクリプトが必要な場合
            // 'script-src' => ['https://cdn.example.com'],
            // 
            // 例: 外部スタイルシートが必要な場合
            // 'style-src' => ['https://fonts.googleapis.com'],
            // 
            // 例: APIへの接続が必要な場合
            // 'connect-src' => ['https://api.example.com'],
            // 
            // 例: 外部画像が必要な場合
            // 'img-src' => ['https://images.example.com'],
            // 
            // 例: iframeの埋め込みが必要な場合
            // 'frame-src' => ['https://youtube.com', 'https://vimeo.com'],
        ];
    }

}