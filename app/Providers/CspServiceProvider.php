<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Providers;

use App\Services\Captcha\CaptchaCspProvider;
use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspNonceGenerator;
use App\Services\Csp\CspPolicyRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CspServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CspNonceGenerator as a singleton
        // To use the same nonce for each request
        $this->app->singleton(CspNonceGenerator::class, function ($app) {
            return new CspNonceGenerator();
        });

        // Register CspPolicyRegistry as a singleton
        // To accumulate policies from plugins/themes
        $this->app->singleton(CspPolicyRegistry::class, function ($app) {
            return new CspPolicyRegistry();
        });

        // Register CspBuilder as a singleton
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
        // Register Blade directives
        $this->registerBladeDirectives();

        // Register helper functions
        $this->registerHelpers();

        // Register the captcha CSP provider so that captcha origins are only
        // included in the policy when captcha is administratively enabled.
        $this->registerCaptchaCspProvider();
    }

    /**
     * Register the captcha-driven CSP policy provider.
     *
     * The provider itself is lazily evaluated: it runs only when
     * CspBuilder collects directives, and inspects the active driver and
     * captcha_enabled setting at that moment.
     */
    protected function registerCaptchaCspProvider(): void
    {
        $this->app->make(CspPolicyRegistry::class)->registerProvider(
            'captcha',
            new CaptchaCspProvider()
        );
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @cspNonce - Output nonce attribute
        // Example: <script @cspNonce>...</script>
        Blade::directive('cspNonce', function () {
            return '<?php echo csp_nonce_attr(); ?>';
        });

        // @cspNonceValue - Output nonce value only
        // Example: <script nonce="@cspNonceValue">...</script>
        Blade::directive('cspNonceValue', function () {
            return '<?php echo csp_nonce(); ?>';
        });

        // @cspMeta - Output CSP as meta tag (for when headers are unavailable)
        Blade::directive('cspMeta', function () {
            return '<?php echo csp_meta(); ?>';
        });

        // @dixScript / @enddixScript - CSP-compliant inline script
        // Example: @dixScript console.log('hello'); @enddixScript
        Blade::directive('dixScript', function ($expression) {
            $options = $expression ? ", {$expression}" : '';

            return '<?php ob_start(); ?>';
        });
        Blade::directive('enddixScript', function () {
            return "<?php echo \App\Helpers\ExtensionHelper::script(ob_get_clean()); ?>";
        });

        // @dixStyle / @enddixStyle - CSP-compliant inline style
        // Example: @dixStyle body { color: red; } @enddixStyle
        Blade::directive('dixStyle', function ($expression) {
            return '<?php ob_start(); ?>';
        });
        Blade::directive('enddixStyle', function () {
            return "<?php echo \App\Helpers\ExtensionHelper::style(ob_get_clean()); ?>";
        });

        // @dixScriptSrc - CSP-compliant external script
        // Example: @dixScriptSrc('https://example.com/script.js', ['defer' => true])
        Blade::directive('dixScriptSrc', function ($expression) {
            return "<?php echo \App\Helpers\ExtensionHelper::scriptSrc({$expression}); ?>";
        });

        // @dixStyleSrc - CSP-compliant external stylesheet
        // Example: @dixStyleSrc('https://example.com/style.css')
        Blade::directive('dixStyleSrc', function ($expression) {
            return "<?php echo \App\Helpers\ExtensionHelper::styleSrc({$expression}); ?>";
        });
    }

    /**
     * Register helper functions
     */
    protected function registerHelpers(): void
    {
        require_once app_path('Helpers/CspHelper.php');
    }
}
