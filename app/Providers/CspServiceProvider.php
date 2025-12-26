<?php
/**
 * This file is part of Dixlase.
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

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\Csp\CspNonceGenerator;
use App\Services\Csp\CspPolicyRegistry;
use App\Services\Csp\CspBuilder;

class CspServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // CspNonceGenerator をシングルトンとして登録
        // リクエストごとに同じnonceを使用するため
        $this->app->singleton(CspNonceGenerator::class, function ($app) {
            return new CspNonceGenerator();
        });

        // CspPolicyRegistry をシングルトンとして登録
        // プラグイン/テーマからのポリシーを蓄積するため
        $this->app->singleton(CspPolicyRegistry::class, function ($app) {
            return new CspPolicyRegistry();
        });

        // CspBuilder をシングルトンとして登録
        $this->app->singleton(CspBuilder::class, function ($app) {
            return new CspBuilder(
                $app->make(CspNonceGenerator::class),
                $app->make(CspPolicyRegistry::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Bladeディレクティブを登録
        $this->registerBladeDirectives();

        // ヘルパー関数を登録
        $this->registerHelpers();
    }

    /**
     * Bladeディレクティブを登録
     */
    protected function registerBladeDirectives(): void
    {
        // @cspNonce - nonce属性を出力
        // 使用例: <script @cspNonce>...</script>
        Blade::directive('cspNonce', function () {
            return '<?php echo csp_nonce_attr(); ?>';
        });

        // @cspNonceValue - nonce値のみを出力
        // 使用例: <script nonce="@cspNonceValue">...</script>
        Blade::directive('cspNonceValue', function () {
            return '<?php echo csp_nonce(); ?>';
        });

        // @cspMeta - CSPをmetaタグで出力（ヘッダーが使えない場合用）
        Blade::directive('cspMeta', function () {
            return '<?php echo csp_meta(); ?>';
        });

        // @dixScript / @enddixScript - CSP対応インラインスクリプト
        // 使用例: @dixScript console.log('hello'); @enddixScript
        Blade::directive('dixScript', function ($expression) {
            $options = $expression ? ", {$expression}" : '';
            return "<?php ob_start(); ?>";
        });
        Blade::directive('enddixScript', function () {
            return "<?php echo \App\Helpers\ExtensionHelper::script(ob_get_clean()); ?>";
        });

        // @dixStyle / @enddixStyle - CSP対応インラインスタイル
        // 使用例: @dixStyle body { color: red; } @enddixStyle
        Blade::directive('dixStyle', function ($expression) {
            return "<?php ob_start(); ?>";
        });
        Blade::directive('enddixStyle', function () {
            return "<?php echo \App\Helpers\ExtensionHelper::style(ob_get_clean()); ?>";
        });

        // @dixScriptSrc - CSP対応外部スクリプト
        // 使用例: @dixScriptSrc('https://example.com/script.js', ['defer' => true])
        Blade::directive('dixScriptSrc', function ($expression) {
            return "<?php echo \App\Helpers\ExtensionHelper::scriptSrc({$expression}); ?>";
        });

        // @dixStyleSrc - CSP対応外部スタイルシート
        // 使用例: @dixStyleSrc('https://example.com/style.css')
        Blade::directive('dixStyleSrc', function ($expression) {
            return "<?php echo \App\Helpers\ExtensionHelper::styleSrc({$expression}); ?>";
        });
    }

    /**
     * ヘルパー関数を登録
     */
    protected function registerHelpers(): void
    {
        require_once app_path('Helpers/CspHelper.php');
    }
}
